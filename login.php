<?php
require_once __DIR__ . '/config/db.php';

$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $login = $_POST['login'];
    $senha = $_POST['senha'];

    if (empty($login) || empty($senha)) {
        $error_message = "Por favor, preencha o login e a senha.";
    } else {
        // Ajustado: Agora buscamos também o campo 'primeiro_acesso'
        $stmt = $conn->prepare("SELECT id, nome, senha, nivel, primeiro_acesso FROM usuarios WHERE login = ?");
        $stmt->bind_param("s", $login);
        $stmt->execute();
        $result = $stmt->get_result();

        

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($senha, $user['senha'])) {
                // Iniciar sessão
                $_SESSION['usuario_id'] = $user['id'];
                $_SESSION['usuario_nome'] = $user['nome'];
                $_SESSION['usuario_nivel'] = $user['nivel'];

                // --- REGISTRO DE LOG DE ACESSO ---
                $ip = $_SERVER['REMOTE_ADDR']; // Captura o IP do usuário
                $log_desc = "Usuário realizou login no sistema. IP: $ip";
                $sql_log = "INSERT INTO logs_sistema (usuario_id, usuario_nome, tabela_afetada, registro_id, acao, descricao_log) 
                            VALUES (?, ?, 'usuarios', ?, 'LOGIN', ?)";
                $st_log = $conn->prepare($sql_log);
                $user_id = $user['id'];
                $user_nome = $user['nome'];
                $st_log->bind_param("isis", $user_id, $user_nome, $user_id, $log_desc);
                $st_log->execute();
                
                // Lógica de Primeiro Acesso: Redireciona se for 1
                if ($user['primeiro_acesso'] == 1) {
                    header("Location: trocar_senha.php");
                    exit();
                }

                header("Location: dashboard.php");
                exit();
            } else {
                $error_message = "Login ou senha inválidos.";
            }
        } else {
            $error_message = "Login ou senha inválidos.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Gestão de Insumos</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: url('img/background.png') no-repeat center center fixed;
            background-size: cover;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            color: #254c90;
        }
        .login-container {
            text-align: center;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
            width: 350px;
        }
        .login-container img { width: 200px; margin-bottom: 20px; }
        .login-container h2 { color: #0052a5; margin-bottom: 20px; font-size: 24px; }
        .login-container input {
            width: calc(100% - 20px);
            padding: 15px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-sizing: border-box;
        }
        .login-container button {
            width: 100%;
            padding: 12px;
            background: #0052a5;
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 18px;
            font-weight: bold;
            margin-top: 10px;
        }
        .error { color: red; margin-bottom: 10px; font-size: 14px; }
    </style>
</head>
<body>
    <div class="login-container">
        <img src="img/logo.svg" alt="Logo">
        <h2>Entrar na Conta</h2>
        <?php if (!empty($error_message)): ?>
            <div class="error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        <form action="login.php" method="post">
            <input type="text" name="login" placeholder="Nome de Usuário" required>
            <input type="password" name="senha" placeholder="Senha" required>
            <button type="submit">ENTRAR</button>
        </form>
    </div>
</body>
</html>