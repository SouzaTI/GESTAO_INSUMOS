<?php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

// 1. Proteção básica: verifica se os dados foram enviados
if (!isset($_POST['id']) || !isset($_POST['status'])) {
    echo json_encode(['success' => false, 'message' => 'Dados incompletos']);
    exit;
}

$id = (int)$_POST['id'];
$novo_status = $_POST['status']; // Aceita 'EM PROCESSAMENTO', 'FINALIZADA' ou 'DESCARTADA'

try {
    // 2. Executa a atualização do status
    $sql = "UPDATE requisicoes_externas SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $novo_status, $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar banco']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}