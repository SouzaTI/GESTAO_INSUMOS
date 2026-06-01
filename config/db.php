<?php
// config/db.php

// Inicia a sessão em todas as páginas que incluírem este arquivo
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclui o leitor do arquivo .env
require_once __DIR__ . '/carregar_env.php';

// Pega os dados do cofre com segurança
$host    = getenv('INSUMOS_HOST');
$db_user = getenv('INSUMOS_USER'); 
$db_pass = getenv('INSUMOS_PASS');     
$db_name = getenv('INSUMOS_DB');

$conn = new mysqli($host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}
?>