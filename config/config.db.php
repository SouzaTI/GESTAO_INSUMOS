<?php
// config/config.db.php

// Inclui o leitor do arquivo .env
require_once __DIR__ . '/carregar_env.php';

// Pega os dados do cofre com segurança
$host = getenv('GLPI_HOST');
$port = getenv('GLPI_PORT');
$db   = getenv('GLPI_DB');
$user = getenv('GLPI_USER');
$pass = getenv('GLPI_PASS'); 

try {
    // Criamos uma única instância do PDO para o projeto todo
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4", $user, $pass);
    
    // Configuramos o PDO para lançar exceções em caso de erro
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch (PDOException $e) {
    die("Erro ao conectar com o banco de dados central: " . $e->getMessage());
}