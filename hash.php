<?php
// hash.php - Utilitário para gerar hashes de senha seguros
$senha_desejada = '@123mudar'; // Altere aqui para a senha que quiser gerar
$hash = password_hash($senha_desejada, PASSWORD_DEFAULT);

echo "<h3>Gerador de Hash</h3>";
echo "<strong>Senha Plana:</strong> " . $senha_desejada . "<br>";
echo "<strong>Hash para o Banco:</strong> <code>" . $hash . "</code>";
echo "<hr><p>Copie o código acima e cole na coluna 'senha' do seu SQL de INSERT.</p>";
?>