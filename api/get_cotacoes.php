<?php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID do pedido não informado']);
    exit;
}

$pedido_id = (int)$_GET['id'];

try {
    // Busca todas as opções vinculadas ao pedido
    $sql = "SELECT * FROM cotacoes_opcoes WHERE pedido_id = ? ORDER BY valor_unitario ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $pedido_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $cotacoes = [];
    while ($row = $result->fetch_assoc()) {
        $cotacoes[] = $row;
    }

    echo json_encode(['success' => true, 'cotacoes' => $cotacoes]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}