<?php
// config/carregar_env.php

function carregarEnv($caminho) {
    if (!file_exists($caminho)) {
        return false;
    }

    $linhas = file($caminho, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($linhas as $linha) {
        // Ignora linhas que são comentários
        if (strpos(trim($linha), '#') === 0) {
            continue;
        }

        // Divide a linha no primeiro sinal de '='
        if (strpos($linha, '=') !== false) {
            list($nome, $valor) = explode('=', $linha, 2);
            putenv(trim($nome) . '=' . trim($valor));
        }
    }
}

// O arquivo .env está duas pastas para cima em relação a este arquivo
carregarEnv(__DIR__ . '/../.env');