<?php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

// Proteção: Só processa se houver um usuário logado no sistema
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sessão expirada. Autentique-se novamente.']);
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$origem = $_POST['origem'] ?? ''; // 'ESTOQUE' ou 'COMPRA'
$retirado_por = $_POST['retirado_por'] ?? 'NÃO INFORMADO';

$operador_id = $_SESSION['usuario_id']; // O "Selo" de quem está no PC
$operador_nome = $_SESSION['usuario_nome'];

$conn->begin_transaction();

try {
    if ($origem === 'ESTOQUE') {
        // 1. Atualiza a Requisição para FINALIZADO
        $stmt = $conn->prepare("UPDATE requisicoes SET status_pedido = 'FINALIZADO', retirado_por = ?, operador_finalizador_id = ?, data_fechamento = NOW() WHERE id = ?");
        $stmt->bind_param("sii", $retirado_por, $operador_id, $id);
        $stmt->execute();

        // 2. Lógica de Baixa no Estoque (Similar ao seu sistema de lotes)
        // Buscamos os itens desta requisição para abater do saldo
        $itens = $conn->query("SELECT produto_nome, quantidade FROM requisicao_itens WHERE requisicao_id = $id");
        
        while ($row = $itens->fetch_assoc()) {
            $prod_nome = $row['produto_nome'];
            $qtd_negativa = $row['quantidade'] * -1;
            
            // Aqui você deve adaptar para buscar o produto_id correto pelo nome
            // ou usar o produto_id se já o tiver na tabela requisicao_itens
            $sql_baixa = "INSERT INTO lotes (produto_id, numero_lote, quantidade_inicial, quantidade_atual, data_entrada) 
                          SELECT id, 'SAIDA-REQ-$id', ?, ?, NOW() FROM produtos WHERE nome = ? LIMIT 1";
            
            $stmt_baixa = $conn->prepare($sql_baixa);
            $stmt_baixa->bind_param("dds", $qtd_negativa, $qtd_negativa, $prod_nome);
            $stmt_baixa->execute();
        }

    } else {
        // 3. LÓGICA DE COMPRA: Envia para sua fila de Compras (pedidos_compra)
        $stmt = $conn->prepare("UPDATE requisicoes SET status_pedido = 'SOLICITAR_COMPRA', data_fechamento = NOW() WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        // Aqui você inseriria na tabela pedidos_compra para o setor de compras visualizar
    }

    // 4. Registro de Log de Auditoria (O Selo Final)
    $log_desc = "Requisição #$id finalizada por $operador_nome. Material entregue para: $retirado_por.";
    $stmt_log = $conn->prepare("INSERT INTO logs_sistema (usuario_id, usuario_nome, tabela_afetada, registro_id, acao, descricao_log) VALUES (?, ?, 'requisicoes', ?, 'FINALIZAR', ?)");
    $stmt_log->bind_param("isis", $operador_id, $operador_nome, $id, $log_desc);
    $stmt_log->execute();

    $conn->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}