<?php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

$sql = "SELECT p.descricao, SUM(l.quantidade_atual) as saldo 
        FROM produtos p 
        JOIN lotes l ON p.id = l.produto_id 
        GROUP BY p.id 
        HAVING saldo <= 0 
        ORDER BY saldo ASC";

$res = $conn->query($sql);
$dados = [];
while($row = $res->fetch_assoc()) { $dados[] = $row; }
echo json_encode($dados);