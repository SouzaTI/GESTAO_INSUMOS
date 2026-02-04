<?php
require_once __DIR__ . '/../config/db.php';

$busca = $_GET['busca'] ?? '';

// Busca por código de referência OU pela descrição exata
$sql = "SELECT id, descricao, unidade_medida FROM produtos 
        WHERE codigo_referencia = ? OR descricao = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $busca, $busca);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

// Se não achar nada, retorna id 0 para manter como item manual
echo json_encode($res ?: ['id' => 0]);