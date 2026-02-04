<?php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sessão expirada']);
    exit;
}

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
    $tipo_compra = $cabecalho['tipo_compra'] ?? ''; 

    $total_decimal = (float)str_replace(['.', ','], ['', '.'], ($cabecalho['total'] ?? '0'));
    $solicitante   = !empty($cabecalho['solicitante']) ? $cabecalho['solicitante'] : 'Administrador';

    if ($modo === 'compra') {
        $mapa_campos = [
            'solicitante'     => $solicitante,
            'fornecedor'      => ($tipo_compra === 'interno') ? 'COMERCIAL SOUZA ATACADO' : ($cabecalho['fornecedor'] ?? 'INTERNO'),
            'cnpj'            => $cabecalho['cnpj'] ?? null,
            'valor_total'     => $total_decimal,
            'status'          => ($tipo_compra === 'cotacao') ? 'EM COTACAO' : 'PENDENTE',
            'forma_pagamento' => $cabecalho['pgto'] ?? 'N/A',
            'pix_favorecido'  => $cabecalho['pix_favorecido'] ?? null,
            'pix_tipo_chave'  => $cabecalho['pix_tipo_chave'] ?? null,
            'pix_chave'       => $cabecalho['pix_chave'] ?? null,
            'parcelamento'    => $cabecalho['parcelas'] ?? 'A Vista'
        ];

        $colunas = implode(", ", array_keys($mapa_campos)) . ", data_abertura";
        $placeholders = implode(", ", array_fill(0, count($mapa_campos), "?")) . ", NOW()";
        
        $stmt = $conn->prepare("INSERT INTO pedidos_compra ($colunas) VALUES ($placeholders)");

        $tipos = "";
        $valores = [];
        foreach ($mapa_campos as $valor) {
            $tipos .= (is_numeric($valor) && !is_string($valor)) ? "d" : "s";
            $valores[] = $valor;
        }

        $stmt->bind_param($tipos, ...$valores);
        if (!$stmt->execute()) throw new Exception("Erro no Pedido: " . $stmt->error);
        $pedido_id = $conn->insert_id;

        // --- INTEGRAÇÃO COM REQUISIÇÕES EXTERNAS ---
        if (!empty($cabecalho['req_id'])) {
            $req_id = (int)$cabecalho['req_id'];
            $conn->query("UPDATE requisicoes_externas SET status = 'FINALIZADA' WHERE id = $req_id");
        }
    }

    foreach ($dados['itens'] as $item) {
        $produto_id = $item['id'];
        
        if ($produto_id == 0 || (is_string($produto_id) && strpos($produto_id, 'NOVO_') !== false)) {
            $nome_limpo = str_replace('NOVO_', '', $item['nome']);
            $stmt_new = $conn->prepare("INSERT INTO produtos (codigo_referencia, descricao, unidade_medida, categoria) VALUES ('MANUAL', ?, ?, 'OPERACIONAL')");
            $stmt_new->bind_param("ss", $nome_limpo, $item['unid']);
            $stmt_new->execute();
            $produto_id = $conn->insert_id;
        }

        if ($modo === 'compra') {
            $tipo_mov = ($tipo_compra === 'interno') ? 'saida' : 'entrada';
        } else {
            $tipo_mov = ($cabecalho['tipo_estoque'] ?? 'entrada') === 'avaria' ? 'saida' : 'entrada';
        }

        $sql_mov = "INSERT INTO movimentacoes (produto_id, pedido_id, tipo, quantidade, observacao, destino_estoque, lote_vencimento) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt_mov = $conn->prepare($sql_mov);
        $destino = ($modo === 'compra') ? 'uso_consumo' : ($cabecalho['destino_estoque'] ?? 'uso_consumo');
        $lote = $item['lote'] ?? '';
        $obs  = $item['obs'] ?? '';
        
        $stmt_mov->bind_param("iidssss", $produto_id, $pedido_id, $tipo_mov, $item['qtd'], $obs, $destino, $lote);
        $stmt_mov->execute();
    }

    $conn->commit();

    // --- LOG CORRIGIDO (ississ) ---
    // A string 'ississ' requer 6 variáveis na ordem exata:
    $u_id     = $_SESSION['usuario_id'];
    $u_nome   = $_SESSION['usuario_nome'];
    $tabela   = 'pedidos_compra';
    $log_id   = (int)($pedido_id ?? 0);
    $acao_log = 'CRIAR_PEDIDO';
    $desc_log = "Registro concluído (Modo: $modo, Tipo: $tipo_compra, Solicitante: $solicitante)";

    $stmt_log = $conn->prepare("INSERT INTO logs_sistema (usuario_id, usuario_nome, tabela_afetada, registro_id, acao, descricao_log) VALUES (?, ?, ?, ?, ?, ?)");
    
    // Agora passamos os 6 parâmetros correspondentes à string 'ississ'
    $stmt_log->bind_param("ississ", 
        $u_id,      // i
        $u_nome,    // s
        $tabela,    // s
        $log_id,    // i
        $acao_log,  // s
        $desc_log   // s
    );
    $stmt_log->execute();
    
    echo json_encode(['success' => true, 'pedido_id' => $pedido_id]);

} catch (Exception $e) {
    if ($conn->inTransaction) $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}