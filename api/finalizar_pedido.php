<?php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

if (!isset($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID não fornecido']);
    exit;
}

$id = (int)$_POST['id'];
$conn->begin_transaction();

try {
    // 1. Busca os dados do pedido para identificar o fornecedor
    $pedido_query = $conn->query("SELECT fornecedor FROM pedidos_compra WHERE id = $id");
    $pedido_dados = $pedido_query->fetch_assoc();
    $eh_interno = ($pedido_dados['fornecedor'] === 'COMERCIAL SOUZA ATACADO');

    // 2. Atualiza o status do pedido para FINALIZADO
    $stmt = $conn->prepare("UPDATE pedidos_compra SET status = 'FINALIZADO', data_finalizacao = NOW() WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    // 3. Busca os itens da movimentação
    $sql_itens = "SELECT produto_id, quantidade, observacao, lote_vencimento FROM movimentacoes WHERE pedido_id = $id";
    $itens = $conn->query($sql_itens);

    while ($row = $itens->fetch_assoc()) {
        $prod_id = $row['produto_id'];
        $qtd = $row['quantidade'];
        $lote_info = $row['lote_vencimento'] ?: 'LOTE-PED-'.$id;

        if ($eh_interno) {
            // --- LÓGICA DE BAIXA (SAÍDA) ---
            // Para retirada interna, não criamos um lote novo com saldo positivo, 
            // registramos a saída nos lotes existentes ou apenas marcamos a movimentação.
            // Aqui, vamos inserir um registro no estoque com quantidade NEGATIVA para abater o saldo
            $sql_baixa = "INSERT INTO lotes (produto_id, numero_lote, quantidade_inicial, quantidade_atual, data_entrada) 
                          VALUES (?, ?, ?, ?, NOW())";
            
            $qtd_negativa = $qtd * -1; // Transforma 10 em -10
            $stmt_baixa = $conn->prepare($sql_baixa);
            // quantidade_inicial e quantidade_atual ficam negativas para o cálculo de saldo bater
            $stmt_baixa->bind_param("isdd", $prod_id, $lote_info, $qtd_negativa, $qtd_negativa);
            $stmt_baixa->execute();

        } else {
            // --- LÓGICA DE ENTRADA (COMPRA NORMAL) ---
            $sql_lote = "INSERT INTO lotes (produto_id, numero_lote, quantidade_inicial, quantidade_atual, data_entrada) 
                         VALUES (?, ?, ?, ?, NOW())";
            
            $stmt_lote = $conn->prepare($sql_lote);
            $stmt_lote->bind_param("isdd", $prod_id, $lote_info, $qtd, $qtd);
            $stmt_lote->execute();
        }
    }

    // 4. REGISTRO DE LOG PERSONALIZADO
    $acao_txt = $eh_interno ? "Entregue (Baixa)" : "Recebido (Entrada)";
    $log_desc = "Pedido #$id $acao_txt. Estoque atualizado automaticamente.";
    
    $stmt_log = $conn->prepare("INSERT INTO logs_sistema (usuario_id, usuario_nome, tabela_afetada, registro_id, acao, descricao_log) VALUES (?, ?, 'pedidos_compra', ?, 'FINALIZAR', ?)");
    $u_id = $_SESSION['usuario_id'];
    $u_nome = $_SESSION['usuario_nome'];
    $stmt_log->bind_param("isis", $u_id, $u_nome, $id, $log_desc);
    $stmt_log->execute();

    $conn->commit();
    echo json_encode(['success' => true, 'is_interno' => $eh_interno]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => "Erro ao finalizar: " . $e->getMessage()]);
}