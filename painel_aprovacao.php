<?php
require_once __DIR__ . '/config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Definição de Identidade e Permissão
$id_gestor_logado = $_SESSION['glpi_id'] ?? $_SESSION['usuario_id'] ?? 0; 

// Proteção: Se não houver ID na sessão, barra o acesso
if ($id_gestor_logado === 0) {
    header("Location: login.php"); 
    exit;
}

// 2. Verificação de Perfil Administrativo
$caminho_permissoes = __DIR__ . '/verificar_permissoes.php';
$pode_ver_tudo = false;

if (file_exists($caminho_permissoes)) {
    require_once $caminho_permissoes;
    // Se a função temAcesso('usuarios') retornar true, você é Admin
    $pode_ver_tudo = temAcesso('usuarios'); 
} else {
    // Fallback de segurança para o seu ID de desenvolvedor (Ex: ID 2 ou 1)
    $pode_ver_tudo = ($id_gestor_logado == 2); 
}

// 3. Query Dinâmica Baseada no Perfil
if ($pode_ver_tudo) {
    // VISÃO ADMIN: Ignora o filtro de aprovador para monitorar todos os pedidos
    $query = "SELECT r.*, u.firstname as nome_aprovador 
              FROM requisicoes r
              LEFT JOIN glpidb_att.glpi_users u ON r.aprovador_id = u.id
              WHERE r.status_pedido = 'PENDENTE_GESTOR' 
              ORDER BY r.data_criacao DESC";
} else {
    // VISÃO GESTOR: Filtra rigorosamente pelo ID de quem está logado
    $query = "SELECT r.*, u.firstname as nome_aprovador 
              FROM requisicoes r
              LEFT JOIN glpidb_att.glpi_users u ON r.aprovador_id = u.id
              WHERE r.aprovador_id = $id_gestor_logado 
              AND r.status_pedido = 'PENDENTE_GESTOR' 
              ORDER BY r.data_criacao DESC";
}

$pedidos = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel de Aprovação - Gestão Digital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f0f2f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .navbar-custom { background: #1e3c72; color: white; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .card-requisicao { border: none; border-radius: 15px; transition: transform 0.2s; border-left: 6px solid #0d6efd; }
        .card-requisicao:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.08); }
        .badge-setor { background-color: #e7f0ff; color: #1e3c72; font-weight: 600; border-radius: 8px; }
        .item-box { background-color: #f8f9fa; border-radius: 10px; padding: 15px; margin-top: 10px; border: 1px solid #eee; }
        .btn-aprovar { background-color: #28a745; border: none; font-weight: bold; border-radius: 8px; padding: 10px 20px; transition: 0.3s; }
        .btn-aprovar:hover { background-color: #218838; box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3); }
        .btn-rejeitar { color: #dc3545; font-weight: 600; text-decoration: none; }
        .btn-rejeitar:hover { color: #a71d2a; }
        .empty-state { text-align: center; padding: 50px; color: #6c757d; }

        /* Mantendo a consistência de elevação visual */
        .hover-elevate:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1) !important;
            background-color: #f8f9fc;
        }

    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg mb-4 shadow-sm" style="background: #ffffff; border-bottom: 2px solid #e3e6f0; border-radius: 0 0 15px 15px;">
    <div class="container-fluid px-4 py-1">
        
        <span class="navbar-brand mb-0 d-flex align-items-center">
            <div class="bg-light text-primary rounded-circle d-flex align-items-center justify-content-center me-3 shadow-sm" style="width: 48px; height: 48px; border: 1px solid #d1d3e2;">
                <i class="fas fa-user-shield fa-lg"></i>
            </div>
            <div class="d-flex flex-column">
                <span class="fw-bold text-dark" style="font-size: 1.25rem; line-height: 1.1;">Portal do Gestor</span>
                <small class="text-muted fw-bold text-uppercase" style="font-size: 0.65rem; letter-spacing: 1px;">Validação e Controle de Insumos</small>
            </div>
        </span>

        <div class="d-flex align-items-center gap-4">
            
            <div class="d-none d-md-flex align-items-center bg-light px-3 py-2 rounded-pill border shadow-sm">
                <div class="text-end me-3 border-end pe-3">
                    <small class="text-uppercase text-muted fw-bold d-block" style="font-size: 0.55rem; letter-spacing: 1px;">Aprovador Ativo</small>
                    <span class="text-dark fw-bold" style="font-size: 0.85rem;">ID #<?php echo $id_gestor_logado; ?></span>
                </div>
                <div class="position-relative">
                    <i class="fas fa-fingerprint text-primary" style="font-size: 1.1rem;"></i>
                    <span class="position-absolute top-0 start-100 translate-middle p-1 bg-success border border-light rounded-circle"></span>
                </div>
            </div>
            
            <a href="dashboard.php" class="btn btn-white shadow-sm border rounded-pill px-4 fw-bold text-dark hover-elevate" style="transition: all 0.3s; height: 42px; display: flex; align-items: center;">
                <i class="fas fa-home me-2 text-primary"></i> Início
            </a>
        </div>
    </div>
</nav>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold text-dark m-0">Requisições Pendentes</h4>
        <span class="text-muted small">Aguardando seu "Selo de OK"</span>
    </div>

    <?php if($pedidos && $pedidos->num_rows > 0): ?>
        <?php while($req = $pedidos->fetch_assoc()): ?>
            <div class="card card-requisicao shadow-sm mb-4">
                <div class="card-body p-4">
                    <div class="row align-items-start">
                        <div class="col-md-8">
                            <div class="d-flex align-items-center mb-2">
                                <h5 class="fw-bold m-0"><?php echo $req['solicitante']; ?></h5>
                                <span class="badge badge-setor ms-3 px-3 py-2 text-uppercase" style="font-size: 0.7rem;">
                                    <i class="fas fa-building me-1"></i> <?php echo $req['setor']; ?>
                                </span>
                            </div>
                            
                            <p class="text-secondary mb-3">
                                <i class="fas fa-comment-dots me-1 text-primary"></i> 
                                <strong>Motivo:</strong> <?php echo htmlspecialchars($req['motivo_solicitacao']); ?>
                            </p>

                            <div class="item-box">
                                <div class="text-dark fw-bold mb-2 small text-uppercase">Itens Solicitados</div>
                                <?php 
                                $id_req = $req['id'];
                                $itens = $conn->query("SELECT * FROM requisicao_itens WHERE requisicao_id = $id_req");
                                while($item = $itens->fetch_assoc()): ?>
                                    <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                                        <div>
                                            <span class="fw-bold text-primary"><?php echo $item['produto_nome']; ?></span>
                                            <div class="text-muted" style="font-size: 0.8rem;">
                                                <?php 
                                                    // Melhora visual da lista de beneficiários
                                                    echo str_replace('|', '<br>', $item['destino_detalhado']); 
                                                ?>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-dark rounded-pill"><?php echo $item['quantidade']; ?> un</span>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        </div>

                        <div class="col-md-4 text-md-end mt-4 mt-md-0">
                            <div class="d-grid gap-2 d-md-block">
                                <button class="btn btn-aprovar text-white shadow-sm" onclick="decidirPedido(<?php echo $req['id']; ?>, 'APROVADO_GESTOR')">
                                    <i class="fas fa-check-circle me-2"></i> APROVAR TUDO
                                </button>
                                <div class="mt-3 text-center text-md-end">
                                    <button class="btn btn-link btn-rejeitar" onclick="decidirPedido(<?php echo $req['id']; ?>, 'REJEITADO')">
                                        <i class="fas fa-times-circle me-1"></i> Recusar Pedido
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body empty-state">
                <i class="fas fa-inbox fa-3x mb-3 text-light"></i>
                <h5>Tudo em dia!</h5>
                <p class="m-0">Nenhuma requisição aguardando sua aprovação no momento.</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function decidirPedido(id, status) {
    if (status === 'REJEITADO') {
        Swal.fire({
            title: 'Motivo da Recusa',
            input: 'textarea',
            inputPlaceholder: 'Explique por que está recusando este pedido...',
            showCancelButton: true,
            confirmButtonText: 'Confirmar Recusa',
            confirmButtonColor: '#dc3545',
            inputValidator: (value) => {
                if (!value) return 'Você precisa explicar o motivo da recusa!'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                enviarDecisao(id, status, result.value);
            }
        });
    } else {
        // Fluxo de Aprovação normal (já funcional)
        Swal.fire({
            title: 'Deseja Aprovar?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sim, aprovar!',
            confirmButtonColor: '#28a745'
        }).then((result) => {
            if (result.isConfirmed) enviarDecisao(id, status);
        });
    }
}

function enviarDecisao(id, status, justificativa = '') {
    const formData = new URLSearchParams();
    formData.append('id', id);
    formData.append('status', status);
    formData.append('justificativa', justificativa);

    fetch('api/decidir_requisicao.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if(res.success) {
            Swal.fire('Sucesso!', 'Pedido processado.', 'success').then(() => location.reload());
        }
    });
}
</script>
</body>
</html>