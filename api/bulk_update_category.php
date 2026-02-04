<?php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

$ids = $_POST['ids'] ?? [];
$categoria = $_POST['categoria'] ?? '';

if (empty($ids) || empty($categoria)) {
    echo json_encode(['success' => false, 'message' => 'Dados incompletos']);
    exit;
}

// Proteção: Transforma o array em uma string separada por vírgulas segura
$ids_sanitizados = array_map('intval', $ids);
$ids_string = implode(',', $ids_sanitizados);

$sql = "UPDATE produtos SET categoria = ? WHERE id IN ($ids_string)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $categoria);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}