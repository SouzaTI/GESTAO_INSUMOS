<?php
require_once __DIR__ . '/config/db.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nova_senha = $_POST['nova_senha'];
    $confirmar = $_POST['confirmar_senha'];

    if (strlen($nova_senha) < 6) {
        $error = "A senha deve ter pelo menos 6 caracteres.";
    } elseif ($nova_senha !== $confirmar) {
        $error = "As senhas não coincidem.";
    } else {
        $user_id = $_SESSION['usuario_id'];
        $hash = password_hash($nova_senha, PASSWORD_DEFAULT);

        // Atualiza a senha e marca que não é mais o primeiro acesso
        $stmt = $conn->prepare("UPDATE usuarios SET senha = ?, primeiro_acesso = 0 WHERE id = ?");
        $stmt->bind_param("si", $hash, $user_id);

        if ($stmt->execute()) {
            $success = "Senha alterada com sucesso! Redirecionando...";
            header("refresh:2;url=dashboard.php");
        } else {
            $error = "Erro ao atualizar senha no banco.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Trocar Senha - Primeiro Acesso</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #254c90; display: flex; align-items: center; height: 100vh; }
        .card { width: 400px; margin: auto; border-radius: 15px; }
    </style>
</head>
<body>
    <div class="card shadow-lg p-4 text-center">
        <h3 class="fw-bold text-primary">Primeiro Acesso</h3>
        <p class="text-muted">Por segurança, você deve alterar sua senha inicial.</p>

        <?php if($error): ?> <div class="alert alert-danger small"><?= $error ?></div> <?php endif; ?>
        <?php if($success): ?> <div class="alert alert-success small"><?= $success ?></div> <?php endif; ?>

        <form method="POST">
            <input type="password" name="nova_senha" class="form-control mb-3" placeholder="Nova Senha" required>
            <input type="password" name="confirmar_senha" class="form-control mb-3" placeholder="Confirmar Nova Senha" required>
            <button type="submit" class="btn btn-primary w-100 fw-bold">ATUALIZAR SENHA E ENTRAR</button>
        </form>
    </div>
</body>
</html>