<?php
require_once __DIR__ . '/verificar_permissoes.php'; 
$pagina = basename($_SERVER['PHP_SELF']);

$pode_gerenciar_usuarios = temAcesso('usuarios'); 
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

            <?php if (temAcesso('estoque')): ?>
                <a href="produtos.php" class="nav-link <?= ($pagina == 'produtos.php') ? 'active' : '' ?>">
                    <i class="fas fa-box"></i> Produtos
                </a>
                <a href="registrar.php" class="nav-link <?= ($pagina == 'registrar.php') ? 'active' : '' ?>">
                    <i class="fas fa-exchange-alt"></i> Movimentação
                </a>
            <?php endif; ?>

            <div class="mt-3 border-top border-secondary pt-2 opacity-75">
                <small class="text-uppercase ms-3" style="font-size: 0.6rem; color: #a5d8ff;">Fluxo de Materiais</small>
                
                <a href="solicitar.php" class="nav-link <?= ($pagina == 'solicitar.php') ? 'active' : '' ?>">
                    <i class="fas fa-file-signature"></i> Solicitar Insumo
                </a>

                <?php if (temAcesso('aprovar_insumo') || $pode_gerenciar_usuarios): ?>
                    <a href="painel_aprovacao.php" class="nav-link <?= ($pagina == 'painel_aprovacao.php') ? 'active' : '' ?>">
                        <i class="fas fa-user-check"></i> Portal do Gestor
                    </a>
                <?php endif; ?>

                <?php if (temAcesso('triagem_insumo') || $pode_gerenciar_usuarios): ?>
                    <a href="painel_almoxarifado.php" class="nav-link <?= ($pagina == 'painel_almoxarifado.php') ? 'active' : '' ?>">
                        <i class="fas fa-warehouse"></i> Triagem Almox.
                    </a>
                <?php endif; ?>
            </div>

            <?php if (temAcesso('comprar')): ?>
                <div class="mt-3 border-top border-secondary pt-2 opacity-75">
                    <small class="text-uppercase ms-3" style="font-size: 0.6rem; color: #ffffff;">Suprimentos</small>
                    <a href="requisicoes.php" class="nav-link <?= ($pagina == 'requisicoes.php') ? 'active' : '' ?>">
                        <i class="fas fa-inbox"></i> Requisições 
                    </a>
                    <a href="acompanhamento.php" class="nav-link <?= ($pagina == 'acompanhamento.php') ? 'active' : '' ?>">
                        <i class="fas fa-tasks"></i> Acompanhamento
                    </a>
                </div>
            <?php endif; ?>

            <?php if ($pode_gerenciar_usuarios): ?>
                <div class="mt-4 border-top border-secondary pt-3 opacity-75">
                    <small class="text-uppercase ms-3" style="font-size: 0.65rem; color: #ffffff;">Configurações</small>
                    <a href="usuarios_gestao.php" class="nav-link <?= ($pagina == 'usuarios_gestao.php') ? 'active' : '' ?>" style="color: #ff8e8e;">
                        <i class="fas fa-users-cog"></i> Usuários
                    </a>
                    <a href="logs.php" class="nav-link <?= ($pagina == 'logs.php') ? 'active' : '' ?>" style="color: #a5d8ff;">
                        <i class="fas fa-history"></i> Logs
                    </a>
                </div>
            <?php endif; ?>

            <div class="mt-auto border-top border-secondary pt-3">
                <a href="logout.php" class="nav-link text-warning">
                    <i class="fas fa-power-off"></i> Sair
                </a>
            </div>
        </nav>
    </div>
</div>