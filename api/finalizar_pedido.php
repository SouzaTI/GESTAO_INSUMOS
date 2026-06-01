<?php
session_start();
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sessão expirada.']);
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$status_acao = $_POST['status'] ?? '';
$retirado_por = $_POST['retirado_por'] ?? 'Almoxarifado';
$operador_id = $_SESSION['usuario_id'];
$operador_nome = $_SESSION['usuario_nome'];

$conn->begin_transaction();

try {
    // 1. Atualiza a Requisição principal
    $stmt = $conn->prepare("UPDATE requisicoes SET status_pedido = ?, retirado_por = ?, data_fechamento = NOW() WHERE id = ?");
    $stmt->bind_param("ssi", $status_acao, $retirado_por, $id);
    $stmt->execute();

    if ($status_acao === 'FINALIZADO') {
        // 2. BUSCA O ID REAL DO PRODUTO: Cruza o nome que está na requisição com a tabela de produtos
        $sql_itens = "SELECT ri.produto_nome, ri.quantidade, p.id as produto_id_real 
                      FROM requisicao_itens ri
                      LEFT JOIN produtos p ON ri.produto_nome = p.nome_produto 
                      WHERE ri.requisicao_id = ?";
        
        $stmt_itens = $conn->prepare($sql_itens);
        $stmt_itens->bind_param("i", $id);
        $stmt_itens->execute();
        $itens = $stmt_itens->get_result();

        while ($row = $itens->fetch_assoc()) {
            $prod_id = $row['produto_id_real'];
            $qtd_negativa = $row['quantidade'] * -1;
            $lote_nome = 'REQ-'.$id;

            // Se o produto não for encontrado na tabela 'produtos', gera erro de integridade
            if (empty($prod_id)) {
                throw new Exception("Produto '".$row['produto_nome']."' não cadastrado no estoque central.");
            }

            // 3. INSERE NO ESTOQUE (LOTES): Agora com o produto_id correto
            $stmt_baixa = $conn->prepare("INSERT INTO lotes (produto_id, numero_lote, quantidade_atual, data_entrada) VALUES (?, ?, ?, NOW())");
            $stmt_baixa->bind_param("isd", $prod_id, $lote_nome, $qtd_negativa);
            $stmt_baixa->execute();
        }
    }

    $conn->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}