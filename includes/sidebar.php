<?php
$pagina = basename($_SERVER['PHP_SELF']);

// 1. Busca as permissões do usuário logado diretamente do banco
$id_user_sidebar = $_SESSION['usuario_id'];
$query_sidebar = $conn->query("SELECT privilegios FROM usuarios WHERE id = $id_user_sidebar");
$dados_sidebar = $query_sidebar->fetch_assoc();

// 2. Decodifica o mapa de permissões
$perm = json_decode($dados_sidebar['privilegios'] ?? '{}', true);

// 3. Define quem pode ver o quê (Chaves do seu JSON)
$pode_gerenciar_usuarios = isset($perm['usuarios']) && $perm['usuarios'] === true;
?>
<div class="sidebar">
    <div class="sidebar-header">
        <h4><i class="fas fa-boxes-packing me-2"></i>GESTÃO DIGITAL</h4>
    </div>
    <div class="py-3">
        <nav class="nav flex-column">
            <a href="dashboard.php" class="nav-link <?= ($pagina == 'dashboard.php') ? 'active' : '' ?>">
                <i class="fas fa-chart-line"></i> Painel
            </a>
            <a href="produtos.php" class="nav-link <?= ($pagina == 'produtos.php') ? 'active' : '' ?>">
                <i class="fas fa-box"></i> Produtos
            </a>
            <a href="registrar.php" class="nav-link <?= ($pagina == 'registrar.php') ? 'active' : '' ?>">
                <i class="fas fa-exchange-alt"></i> Movimentação
            </a>
            <a href="requisicoes.php" class="nav-link <?= ($pagina == 'requisicoes.php') ? 'active' : '' ?>">
                <i class="fas fa-inbox"></i> Requisições 
            </a>
            <a href="acompanhamento.php" class="nav-link <?= ($pagina == 'acompanhamento.php') ? 'active' : '' ?>">
                <i class="fas fa-tasks"></i> Acompanhamento
            </a>

            <?php if ($pode_gerenciar_usuarios): ?>
                <div class="mt-4 border-top border-secondary pt-3 opacity-75">
                    <small class="text-uppercase ms-3" style="font-size: 0.65rem; color: #ffffff;">Controle de Acessos</small>
                    <a href="usuarios_gestao.php" class="nav-link <?= ($pagina == 'usuarios_gestao.php') ? 'active' : '' ?>" style="color: #ff8e8e;">
                        <i class="fas fa-users-cog"></i> Gestão de Usuários
                    </a>
                    <a href="logs.php" class="nav-link <?= ($pagina == 'logs.php') ? 'active' : '' ?>" style="color: #a5d8ff;">
                        <i class="fas fa-history"></i> Logs do Sistema
                    </a>
                </div>
            <?php endif; ?>

            <div class="mt-5 border-top border-secondary pt-3">
                <a href="logout.php" class="nav-link text-warning">
                    <i class="fas fa-power-off"></i> Sair
                </a>
            </div>
        </nav>
    </div>
</div>