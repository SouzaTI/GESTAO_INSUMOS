<?php
require_once __DIR__ . '/../config/db.php';
$id = (int)$_GET['id'];
$p = $conn->query("SELECT id, fornecedor, valor_total FROM pedidos_compra WHERE id = $id")->fetch_assoc();
echo json_encode($p);