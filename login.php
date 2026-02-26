<?php
session_start(); // Garante que a sessão comece antes de qualquer saída
require_once __DIR__ . '/config/db.php'; // Sua conexão original (MySQLi) com gestao_insumos

$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $login = $_POST['login'];
    $senha = $_POST['senha'];

    if (empty($login) || empty($senha)) {
        $error_message = "Por favor, preencha o login e a senha.";
    } else {
        try {
            // 1. CONEXÃO TEMPORÁRIA COM O GLPI (PDO)
            // Usando os dados do seu print: 127.0.0.1:3307 e banco glpidb_att
            $glpi_pdo = new PDO("mysql:host=127.0.0.1;port=3307;dbname=glpidb_att;charset=utf8mb4", 'root', '');
            $glpi_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // 2. QUERY "MONSTRA" (Busca Usuário + Perfil + Setor/Entidade)
            $sql_glpi = "SELECT u.id, u.name, u.password, 
                               p.name AS perfil_nome, 
                               e.completename AS setor_nome
                        FROM glpi_users u
                        INNER JOIN glpi_profiles_users pu ON u.id = pu.users_id
                        INNER JOIN glpi_profiles p ON pu.profiles_id = p.id
                        LEFT JOIN glpi_entities e ON pu.entities_id = e.id
                        WHERE u.name = :login";
            
            $stmt = $glpi_pdo->prepare($sql_glpi);
            $stmt->execute(['login' => $login]);
            $user_glpi = $stmt->fetch(PDO::FETCH_ASSOC);

            // 3. VALIDAÇÃO DE SENHA (BCRYPT do GLPI)
            if ($user_glpi && password_verify($senha, $user_glpi['password'])) {
                
                // Definindo variáveis de sessão baseadas no GLPI
                $_SESSION['usuario_id'] = $user_glpi['id'];
                $_SESSION['usuario_nome'] = $user_glpi['name'];
                $_SESSION['usuario_setor'] = $user_glpi['setor_nome'] ?? 'Geral';
                
                // Lógica de Nível: Super-Admin vira admin, o resto vira usuario
                $_SESSION['usuario_nivel'] = ($user_glpi['perfil_nome'] === 'Super-Admin') ? 'admin' : 'usuario';

                // --- REGISTRO DE LOG NO BANCO LOCAL (Gestão de Insumos) ---
                // Usamos a variável $conn que vem do seu db.php original
                $ip = $_SERVER['REMOTE_ADDR'];
                $log_desc = "Login via GLPI. Setor: " . $_SESSION['usuario_setor'] . " | IP: $ip";
                $sql_log = "INSERT INTO logs_sistema (usuario_id, usuario_nome, tabela_afetada, registro_id, acao, descricao_log) 
                            VALUES (?, ?, 'usuarios', ?, 'LOGIN', ?)";
                
                $st_log = $conn->prepare($sql_log);
                $st_log->bind_param("isis", $user_glpi['id'], $user_glpi['name'], $user_glpi['id'], $log_desc);
                $st_log->execute();

                header("Location: dashboard.php");
                exit();
            } else {
                $error_message = "Login ou senha inválidos (Base GLPI).";
            }
        } catch (PDOException $e) {
            $error_message = "Erro ao conectar na base de autenticação: " . $e->getMessage();
        }
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