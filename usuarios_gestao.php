<?php
// 1. CONEXÕES
require_once __DIR__ . '/config/db.php'; // Sua conexão original $conn

// Conexão PDO temporária para o GLPI (Porta 3307)
try {
    $glpi_pdo = new PDO("mysql:host=127.0.0.1;port=3307;dbname=glpidb_att;charset=utf8mb4", 'root', '');
} catch (PDOException $e) {
    die("Erro ao conectar no banco do GLPI: " . $e->getMessage());
}

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['usuario_id'])) { header("Location: login.php"); exit(); }

// 2. VALIDAÇÃO DE SUPER-ADMIN (Baseado no nível definido no login via GLPI)
$perfil_logado = $_SESSION['usuario_nivel'] ?? ''; 
if ($perfil_logado !== 'admin') {
    die("Acesso negado. Apenas Super-Admins do GLPI gerenciam acessos.");
}

// 3. LÓGICA PARA SALVAR PERMISSÕES (No banco Gestão)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['glpi_user_id'])) {
    $id_edit = $_POST['glpi_user_id'];
    
    $novas_perms = [
        "comprar"    => isset($_POST['p_comprar']),
        "estoque"    => isset($_POST['p_estoque']),
        "financeiro" => isset($_POST['p_financeiro']),
        "usuarios"   => isset($_POST['p_usuarios'])
    ];
    $json_perms = json_encode($novas_perms);
    
    // REPLACE INTO na sua nova tabela de permissões local
    $stmt = $conn->prepare("REPLACE INTO permissoes_app (glpi_user_id, privilegios) VALUES (?, ?)");
    $stmt->bind_param("is", $id_edit, $json_perms);
    
    if ($stmt->execute()) {
        $msg = "Acessos de " . $_POST['nome_user'] . " atualizados!";
    }
}

// 4. CONSULTA UNIFICADA (Lista todos os usuários do GLPI)
$query_usuarios = "SELECT id, name, realname, firstname FROM glpi_users ORDER BY name ASC";
$res_usuarios = $glpi_pdo->query($query_usuarios);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gestão de Acessos via GLPI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Estilos para a Rolagem Interna e Cabeçalho Fixo */
        .table-scroll-container {
            max-height: 65vh; 
            overflow-y: auto;
            border: 1px solid #dee2e6;
        }
        .table thead th {
            position: sticky;
            top: 0;
            z-index: 10;
            background-color: #212529 !important;
            color: white;
        }
        .search-box {
            max-width: 400px;
        }
    </style>
</head>
<body class="bg-light">

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold"><i class="fas fa-user-shield me-2 text-primary"></i>Gestão de Acessos (GLPI)</h2>
            <p class="text-muted">Autorize usuários do GLPI a acessarem módulos do sistema.</p>
        </div>
        <div class="d-flex gap-3">
            <div class="input-group search-box shadow-sm">
                <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                <input type="text" id="inputBusca" class="form-control" placeholder="Procurar colaborador...">
            </div>
            <a href="dashboard.php" class="btn btn-secondary shadow-sm"><i class="fas fa-arrow-left me-2"></i>Voltar</a>
        </div>
    </div>

    <?php if(isset($msg)): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?php echo $msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="table-scroll-container">
            <table class="table table-hover align-middle mb-0" id="tabelaUsuarios">
                <thead class="table-dark">
                    <tr>
                        <th class="ps-4">Usuário GLPI</th>
                        <th>Login</th>
                        <th class="text-center">Compras</th>
                        <th class="text-center">Estoque</th>
                        <th class="text-center">Financeiro</th>
                        <th class="text-center">Admin App</th>
                        <th class="text-end pe-4">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $res_usuarios->fetch(PDO::FETCH_ASSOC)): 
                        // Busca permissão atual na sua tabela local permissoes_app
                        $check = $conn->query("SELECT privilegios FROM permissoes_app WHERE glpi_user_id = " . $row['id']);
                        $p_local = $check->fetch_assoc();
                        $p = json_decode($p_local['privilegios'] ?? '{}', true);
                    ?>
                    <tr>
                        <form method="POST">
                            <input type="hidden" name="glpi_user_id" value="<?php echo $row['id']; ?>">
                            <input type="hidden" name="nome_user" value="<?php echo $row['name']; ?>">
                            
                            <td class="ps-4">
                                <span class="fw-bold text-dark nome-usuario"><?php echo $row['firstname'] . " " . $row['realname']; ?></span>
                            </td>
                            <td><code class="text-primary login-usuario"><?php echo $row['name']; ?></code></td>
                            
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
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Lógica de Busca em Tempo Real
    document.getElementById('inputBusca').addEventListener('keyup', function() {
        let busca = this.value.toLowerCase();
        let linhas = document.querySelectorAll('#tabelaUsuarios tbody tr');

        linhas.forEach(linha => {
            let nome = linha.querySelector('.nome-usuario').textContent.toLowerCase();
            let login = linha.querySelector('.login-usuario').textContent.toLowerCase();
            
            if (nome.includes(busca) || login.includes(busca)) {
                linha.style.display = "";
            } else {
                linha.style.display = "none";
            }
        });
    });
</script>
</body>
</html>