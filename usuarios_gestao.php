<?php
require_once __DIR__ . '/config/db.php';
if (!isset($_SESSION['usuario_id'])) { header("Location: login.php"); exit(); }

// 1. Busca as permissões do usuário logado para garantir que ele é um Admin
$id_logado = $_SESSION['usuario_id'];
$user_logado = $conn->query("SELECT privilegios FROM usuarios WHERE id = $id_logado")->fetch_assoc();
$perm_logado = json_decode($user_logado['privilegios'] ?? '{}', true);

// Trava de segurança: se não tiver a chave 'usuarios', não acessa esta tela
if (!isset($perm_logado['usuarios']) || $perm_logado['usuarios'] !== true) {
    die("Acesso negado. Você não tem permissão de administrador.");
}

// 2. Lógica para salvar as alterações via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['usuario_id'])) {
    $id_edit = $_POST['usuario_id'];
    
    // Monta o novo mapa de privilégios em JSON
    $novas_perms = [
        "comprar" => isset($_POST['p_comprar']),
        "estoque" => isset($_POST['p_estoque']),
        "financeiro" => isset($_POST['p_financeiro']),
        "usuarios" => isset($_POST['p_usuarios'])
    ];
    $json_perms = json_encode($novas_perms);
    
    $stmt = $conn->prepare("UPDATE usuarios SET privilegios = ? WHERE id = ?");
    $stmt->bind_param("si", $json_perms, $id_edit);
    
    if ($stmt->execute()) {
        $msg = "Permissões de " . $_POST['nome_user'] . " atualizadas com sucesso!";
    }
}

// 3. Consulta ajustada para as colunas reais: 'id', 'nome', 'login', 'nivel', 'privilegios'
$usuarios = $conn->query("SELECT id, nome, login, nivel, privilegios FROM usuarios ORDER BY nome ASC");
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Acessos Dinâmicos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">

</head>
<body class="bg-light">

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold"><i class="fas fa-user-shield me-2 text-primary"></i>Gestão de Acessos</h2>
            <p class="text-muted">Controle o que cada funcionário pode visualizar e operar.</p>
        </div>
        <a href="registrar.php" class="btn btn-secondary shadow-sm"><i class="fas fa-arrow-left me-2"></i>Voltar</a>
    </div>

    <?php if(isset($msg)): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?php echo $msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0 table-admin">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-dark">
                <tr>
                    <th class="ps-4">Nome do Colaborador</th>
                    <th>Login (Acesso)</th>
                    <th class="text-center">Compras</th>
                    <th class="text-center">Estoque</th>
                    <th class="text-center">Financeiro</th>
                    <th class="text-center">Admin (Acessos)</th>
                    <th class="text-end pe-4">Ação</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $usuarios->fetch_assoc()): 
                    $p = json_decode($row['privilegios'] ?? '{}', true);
                ?>
                <tr>
                    <form method="POST">
                        <input type="hidden" name="usuario_id" value="<?php echo $row['id']; ?>">
                        <input type="hidden" name="nome_user" value="<?php echo $row['nome']; ?>">
                        
                        <td class="ps-4">
                            <span class="fw-bold text-dark"><?php echo $row['nome']; ?></span>
                            <br><small class="text-muted uppercase"><?php echo $row['nivel']; ?></small>
                        </td>
                        <td><code class="text-primary"><?php echo $row['login']; ?></code></td>
                        
                        <td class="text-center">
                            <input type="checkbox" class="form-check-input" name="p_comprar" <?php echo (isset($p['comprar']) && $p['comprar']) ? 'checked' : ''; ?>>
                        </td>
                        <td class="text-center">
                            <input type="checkbox" class="form-check-input" name="p_estoque" <?php echo (isset($p['estoque']) && $p['estoque']) ? 'checked' : ''; ?>>
                        </td>
                        <td class="text-center">
                            <input type="checkbox" class="form-check-input" name="p_financeiro" <?php echo (isset($p['financeiro']) && $p['financeiro']) ? 'checked' : ''; ?>>
                        </td>
                        <td class="text-center">
                            <input type="checkbox" class="form-check-input" name="p_usuarios" <?php echo (isset($p['usuarios']) && $p['usuarios']) ? 'checked' : ''; ?>>
                        </td>
                        <td class="text-end pe-4">
                            <button type="submit" class="btn btn-primary btn-sm px-3 shadow-sm">
                                <i class="fas fa-save me-1"></i>Salvar
                            </button>
                        </td>
                    </form>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>