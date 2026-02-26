<?php
// config.db.php

// Dados de conexão baseados no seu ambiente GLPI
$host = '127.0.0.1';
$port = '3307';
$db   = 'glpidb_att';
$user = 'root';
$pass = ''; // Senha vazia como vimos no VS Code

try {
    // Criamos uma única instância do PDO para o projeto todo
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4", $user, $pass);
    
    // Configuramos o PDO para lançar exceções em caso de erro (bom para debug)
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch (PDOException $e) {
    // Se der erro, para tudo e avisa. Na lanchonete da faculdade a gente chama isso de "Panic Mode"
    die("Erro ao conectar com o banco de dados central: " . $e->getMessage());
}