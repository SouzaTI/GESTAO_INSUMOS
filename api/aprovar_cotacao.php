<?php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método inválido']);
    exit;
}

$opcao_id = (int)$_POST['opcao_id'];
$pedido_id = (int)$_POST['pedido_id'];

$conn->begin_transaction();

try {
    // 1. Busca os dados da opção vencedora escolhida pelo Admin
    $stmt_op = $conn->prepare("SELECT fornecedor_nome, valor_unitario, valor_frete FROM cotacoes_opcoes WHERE id = ?");
    $stmt_op->bind_param("i", $opcao_id);
    $stmt_op->execute();
    $dados_opcao = $stmt_op->get_result()->fetch_assoc();

    if (!$dados_opcao) throw new Exception("Opção de cotação não encontrada.");

    // 2. Calcula o valor total com base na escolha do Admin
    $res_qtd = $conn->query("SELECT SUM(quantidade) as total_qtd FROM movimentacoes WHERE pedido_id = $pedido_id")->fetch_assoc();
    $valor_final = ($dados_opcao['valor_unitario'] * $res_qtd['total_qtd']) + $dados_opcao['valor_frete'];

    // 3. ATUALIZAÇÃO CIRÚRGICA: O status vira 'APROVACAO' (Aguardando complemento do comprador)
    // O pedido ainda não está 'APROVADO' totalmente porque faltam dados fiscais e financeiros.
    $sql_update = "UPDATE pedidos_compra SET 
                    status = 'APROVACAO', 
                    fornecedor = ?, 
                    valor_total = ?, 
                    aprovador_id = ?, 
                    data_aprovacao = NOW() 
                   WHERE id = ?";
    $stmt_up = $conn->prepare($sql_update);
    $stmt_up->bind_param("sdii", 
        $dados_opcao['fornecedor_nome'], 
        $valor_final, 
        $_SESSION['usuario_id'], 
        $pedido_id
    );
    $stmt_up->execute();

    // 4. Marca a opção vencedora na tabela de cotações
    $conn->query("UPDATE cotacoes_opcoes SET selecionada = 0 WHERE pedido_id = $pedido_id");
    $conn->query("UPDATE cotacoes_opcoes SET selecionada = 1 WHERE id = $opcao_id");

    // 5. Log da Decisão do Admin
    $log_desc = "Admin selecionou fornecedor " . $dados_opcao['fornecedor_nome'] . ". Pedido aguarda complemento de dados.";
    $stmt_log = $conn->prepare("INSERT INTO logs_sistema (usuario_id, usuario_nome, tabela_afetada, registro_id, acao, descricao_log) VALUES (?, ?, 'pedidos_compra', ?, 'DECISAO_ADMIN', ?)");
    $stmt_log->bind_param("isis", $_SESSION['usuario_id'], $_SESSION['usuario_nome'], $pedido_id, $log_desc);
    $stmt_log->execute();

    $conn->commit();
    
    $desc = "Aprovou fornecedor ".$dados_opcao['fornecedor_nome']." para o pedido #$pedido_id";
    $stmt_log = $conn->prepare("INSERT INTO logs_sistema (usuario_id, usuario_nome, tabela_afetada, registro_id, acao, descricao_log) VALUES (?, ?, 'pedidos_compra', ?, 'APROVAR', ?)");
    $stmt_log->bind_param("isis", $_SESSION['usuario_id'], $_SESSION['usuario_nome'], $pedido_id, $desc);
    $stmt_log->execute();

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}