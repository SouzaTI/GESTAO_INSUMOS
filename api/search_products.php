<?php
// api/search_products.php
require_once __DIR__ . '/../config/db.php';

// Verifica se há um termo de busca
$searchTerm = isset($_GET['q']) ? trim($_GET['q']) : '';

// Query para buscar por descrição ou código de referência
$sql = "SELECT id, codigo_referencia, descricao, unidade_medida 
        FROM produtos 
        WHERE descricao LIKE ? OR codigo_referencia LIKE ? 
        ORDER BY descricao ASC 
        LIMIT 20";

$stmt = $conn->prepare($sql);
$likeTerm = "%$searchTerm%";
$stmt->bind_param("ss", $likeTerm, $likeTerm);
$stmt->execute();
$result = $stmt->get_result();

$json = [];
while ($row = $result->fetch_assoc()) {
    // O Select2 exige os campos 'id' e 'text'
    $json[] = [
        'id'   => $row['id'],
        'text' => $row['codigo_referencia'] . " - " . $row['descricao'],
        'unid' => $row['unidade_medida'] // Campo extra para usarmos no JS
    ];
}

header('Content-Type: application/json');
echo json_encode($json);