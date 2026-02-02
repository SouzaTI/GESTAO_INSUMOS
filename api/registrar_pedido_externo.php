<?php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

$ip = $_SERVER['REMOTE_ADDR'];
$user_agent = $_SERVER['HTTP_USER_AGENT'];

// 1. Rate Limit: Máximo 3 por hora por IP
$check = $conn->prepare("SELECT COUNT(*) as total FROM requisicoes_externas WHERE ip_origem = ? AND data_envio > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
$check->bind_param("s", $ip);
$check->execute();
if ($check->get_result()->fetch_assoc()['total'] >= 3) {
    echo json_encode(['success' => false, 'message' => 'Limite excedido. Tente mais tarde.']);
    exit;
}

// 2. Formatação dos Itens
$nomes = $_POST['item_nome'] ?? [];
$qtds = $_POST['item_qtd'] ?? [];
$desc_final = "";
foreach($nomes as $i => $n) {
    $desc_final .= "- (" . $qtds[$i] . ") " . strtoupper($n) . "\n";
}

try {
    $sql = "INSERT INTO requisicoes_externas (solicitante, setor, descricao_itens, ip_origem, dispositivo_info) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $_POST['solicitante'], $_POST['setor'], $desc_final, $ip, $user_agent);
    $stmt->execute();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro no servidor.']);
}