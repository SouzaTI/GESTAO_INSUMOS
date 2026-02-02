<?php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

// 1. Verificação de Sessão
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sessão expirada']);
    exit;
}

// 2. Recebimento dos Dados JSON
$dados = json_decode(file_get_contents('php://input'), true);

if (!$dados || empty($dados['itens'])) {
    echo json_encode(['success' => false, 'message' => 'Nenhum item na lista']);
    exit;
}

$conn->begin_transaction();

try {
    $cabecalho = $dados['cabecalho'];
    $modo = $dados['modo'];
    $pedido_id = null;

    // --- NORMALIZAÇÃO DE VARIÁVEIS (A chave para evitar o erro de contagem) ---
    
    // Converte total para decimal do MySQL
    $total_decimal  = (float)str_replace(['.', ','], ['', '.'], ($cabecalho['total'] ?? '0'));
    
    // Tratamento de campos nulos/vazios para o bind_param
    $solicitante    = !empty($cabecalho['solicitante']) ? $cabecalho['solicitante'] : 'Administrador';
    $fornecedor     = !empty($cabecalho['fornecedor']) ? $cabecalho['fornecedor'] : 'INTERNO';
    $cnpj           = !empty($cabecalho['cnpj']) ? $cabecalho['cnpj'] : null;
    $forma_pgto     = !empty($cabecalho['pgto']) ? $cabecalho['pgto'] : 'N/A';
    $pix_favorecido = !empty($cabecalho['pix_favorecido']) ? $cabecalho['pix_favorecido'] : null;
    $pix_tipo       = !empty($cabecalho['pix_tipo_chave']) ? $cabecalho['pix_tipo_chave'] : null;
    $pix_chave      = !empty($cabecalho['pix_chave']) ? $cabecalho['pix_chave'] : null;
    $parcelamento   = !empty($cabecalho['parcelas']) ? $cabecalho['parcelas'] : 'A Vista';

    // 3. Gravação do Cabeçalho (Somente se for modo Compra)
    if ($modo === 'compra') {
        // 1. Criamos um mapa de campos que batem com sua tabela pedidos_compra
        $mapa_campos = [
            'solicitante'     => $cabecalho['solicitante'] ?? 'Administrador',
            'fornecedor'      => $cabecalho['fornecedor'] ?? null,
            'cnpj'            => $cabecalho['cnpj'] ?? null,
            'valor_total'     => $total_decimal,
            'status'          => ($cabecalho['tipo_compra'] === 'cotacao') ? 'EM COTACAO' : 'PENDENTE',
            'forma_pagamento' => $cabecalho['pgto'] ?? null,
            'pix_favorecido'  => $cabecalho['pix_favorecido'] ?? null,
            'pix_tipo_chave'  => $cabecalho['pix_tipo_chave'] ?? null,
            'pix_chave'       => $cabecalho['pix_chave'] ?? null,
            'parcelamento'    => $cabecalho['parcelas'] ?? 'A Vista'
        ];

        // 2. Montamos a Query de forma dinâmica
        $colunas = implode(", ", array_keys($mapa_campos)) . ", data_abertura";
        $placeholders = implode(", ", array_fill(0, count($mapa_campos), "?")) . ", NOW()";
        
        $sql_pedido = "INSERT INTO pedidos_compra ($colunas) VALUES ($placeholders)";
        $stmt = $conn->prepare($sql_pedido);

        // 3. Geramos a string de tipos e os valores dinamicamente
        $tipos = "";
        $valores = [];
        foreach ($mapa_campos as $valor) {
            if (is_numeric($valor) && !is_string($valor)) {
                $tipos .= "d"; // Decimal/Double
            } else {
                $tipos .= "s"; // String
            }
            $valores[] = $valor;
        }

        // 4. O segredo: usamos o espalhamento (...) para passar o array de valores
        $stmt->bind_param($tipos, ...$valores);
        
        if (!$stmt->execute()) {
            throw new Exception("Erro no banco: " . $stmt->error);
        }
        $pedido_id = $conn->insert_id;
    }

    // 4. Processamento dos Itens da Lista
    foreach ($dados['itens'] as $item) {
        $produto_id = $item['id'];
        
        // Cadastro automático de novo produto
        if ($produto_id == 0 || strpos($produto_id, 'NOVO_') !== false) {
            $nome_limpo = str_replace('NOVO_', '', $item['nome']);
            $stmt_new = $conn->prepare("INSERT INTO produtos (codigo_referencia, descricao, unidade_medida, categoria) VALUES ('MANUAL', ?, ?, 'OPERACIONAL')");
            $stmt_new->bind_param("ss", $nome_limpo, $item['unid']);
            $stmt_new->execute();
            $produto_id = $conn->insert_id;
        }

        // Gravação de Cotações Comparativas (se houver)
        if (!empty($item['cotacoes']) && $modo === 'compra') {
            foreach ($item['cotacoes'] as $cot) {
                $val_u = (float)str_replace(['.', ','], ['', '.'], $cot['valor']);
                $val_f = (float)str_replace(['.', ','], ['', '.'], ($cot['frete'] ?? '0'));
                
                $sql_cot = "INSERT INTO cotacoes_opcoes (pedido_id, produto_id, fornecedor_nome, valor_unitario, valor_frete, prazo_entrega, link_produto) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt_cot = $conn->prepare($sql_cot);
                $stmt_cot->bind_param("iisddss", $pedido_id, $produto_id, $cot['fornecedor'], $val_u, $val_f, $cot['entrega'], $cot['link']);
                $stmt_cot->execute();
            }
        }

        // Registro da Movimentação / Reserva de Estoque
        $sql_mov = "INSERT INTO movimentacoes (produto_id, pedido_id, tipo, quantidade, observacao, destino_estoque, lote_vencimento) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt_mov = $conn->prepare($sql_mov);
        
        $tipo_mov = ($modo === 'compra') ? 'entrada' : ($cabecalho['tipo_estoque'] ?? 'entrada');
        $destino  = ($modo === 'compra') ? 'uso_consumo' : ($cabecalho['destino_estoque'] ?? 'uso_consumo');
        $lote     = $item['lote'] ?? '';
        $obs      = $item['obs'] ?? '';
        
        $stmt_mov->bind_param("iidssss", $produto_id, $pedido_id, $tipo_mov, $item['qtd'], $obs, $destino, $lote);
        $stmt_mov->execute();
    }

    $conn->commit();

    // 5. Auditoria de Log Blindada (ississ = 6 parâmetros)
    $u_id = $_SESSION['usuario_id'];
    $u_nome = $_SESSION['usuario_nome'];
    $tabela = 'pedidos_compra';
    $acao = 'CRIAR_PEDIDO';
    $log_id = $pedido_id ?? 0;
    $desc = "Operação concluída por $u_nome (Solicitante: $solicitante)";

    $stmt_log = $conn->prepare("INSERT INTO logs_sistema (usuario_id, usuario_nome, tabela_afetada, registro_id, acao, descricao_log) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt_log->bind_param("ississ", $u_id, $u_nome, $tabela, $log_id, $acao, $desc);
    $stmt_log->execute();
    
    echo json_encode(['success' => true, 'pedido_id' => $pedido_id]);

} catch (Exception $e) {
    if ($conn->inTransaction) $conn->rollback();
    echo json_encode(['success' => false, 'message' => "Erro crítico: " . $e->getMessage()]);
}