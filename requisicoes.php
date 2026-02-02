<?php
require_once __DIR__ . '/config/db.php';
if (!isset($_SESSION['usuario_id'])) { header("Location: login.php"); exit(); }

// Busca apenas as requisições novas
$requisicoes = $conn->query("SELECT * FROM requisicoes_externas WHERE status = 'NOVA' ORDER BY data_envio DESC");
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Caixa de Entrada - Requisições</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .card-req { transition: transform 0.2s; border-radius: 12px; }
        .card-req:hover { transform: translateY(-5px); }
        .badge-setor { font-size: 0.75rem; letter-spacing: 0.5px; }
        .itens-lista { font-size: 0.9rem; line-height: 1.5; background: #fcfcfc; border-left: 3px solid #0d6efd; }
    </style>
</head>
<body>
<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0"><i class="fas fa-inbox text-primary me-2"></i>Requisições Externas</h2>
            <p class="text-muted small">Triagem de solicitações enviadas pelos setores.</p>
        </div>
        <span class="badge bg-primary rounded-pill px-3 py-2"><?= $requisicoes->num_rows ?> Pendentes</span>
    </div>

    <div class="row">
        <?php if($requisicoes->num_rows == 0): ?>
            <div class="col-12 text-center py-5">
                <i class="fas fa-mug-hot fa-3x text-muted mb-3"></i>
                <h4 class="text-muted">Tudo limpo por aqui!</h4>
                <p class="small text-muted">Nenhuma nova requisição de materiais no momento.</p>
            </div>
        <?php endif; ?>

        <?php while($req = $requisicoes->fetch_assoc()): ?>
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card card-req shadow-sm border-0 h-100">
                <div class="card-header bg-white border-0 pt-3 d-flex justify-content-between">
                    <span class="badge bg-light text-primary border border-primary-subtle badge-setor text-uppercase">
                        <i class="fas fa-layer-group me-1"></i> <?= htmlspecialchars($req['setor']) ?>
                    </span>
                    <small class="text-muted italic"><?= date('d/m H:i', strtotime($req['data_envio'])) ?></small>
                </div>
                <div class="card-body">
                    <h6 class="card-subtitle mb-3 text-dark">
                        <i class="fas fa-user-tag text-muted me-1 small"></i> 
                        <strong><?= htmlspecialchars($req['solicitante']) ?></strong>
                    </h6>
                    
                    <div class="itens-lista p-2 rounded mb-3">
                        <div class="fw-bold small text-muted mb-1 text-uppercase" style="font-size: 0.65rem;">Itens Solicitados:</div>
                        <div style="white-space: pre-wrap;"><?= htmlspecialchars($req['descricao_itens']) ?></div>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <a href="registrar.php?req_id=<?= $req['id'] ?>" class="btn btn-success btn-sm w-100 fw-bold">
                            <i class="fas fa-shopping-cart me-1"></i> INICIAR COMPRA
                        </a>
                        <button onclick="descartarReq(<?= $req['id'] ?>)" class="btn btn-outline-danger btn-sm" title="Descartar">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                <div class="card-footer bg-light border-0 py-2 d-flex justify-content-between">
                    <code class="text-muted" style="font-size: 0.65rem;">IP: <?= $req['ip_origem'] ?></code>
                    <i class="fas fa-mobile-alt text-muted" title="<?= htmlspecialchars($req['dispositivo_info']) ?>"></i>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function descartarReq(id) {
    Swal.fire({
        title: 'Descartar Requisição?',
        text: "Esta ação não pode ser desfeita e a solicitação será arquivada.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sim, descartar!',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('api/status_requisicao.php', {id: id, status: 'DESCARTADA'}, function(res) {
                if(res.success) {
                    Swal.fire('Arquivado!', 'A requisição foi removida da triagem.', 'success')
                    .then(() => location.reload());
                } else {
                    Swal.fire('Erro', 'Não foi possível atualizar o status.', 'error');
                }
            }, 'json');
        }
    });
}
</script>
</body>
</html>