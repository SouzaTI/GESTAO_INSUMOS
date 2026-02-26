<?php
require_once __DIR__ . '/config/db.php';

// Simulação da sessão do Alex Cunha
$id_gestor_logado = 40; 

$query = "SELECT * FROM requisicoes 
          WHERE aprovador_id = $id_gestor_logado 
          AND status_pedido = 'PENDENTE_GESTOR' 
          ORDER BY data_criacao DESC";
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
    </style>
</head>
<body>

<nav class="navbar navbar-custom mb-4">
    <div class="container">
        <span class="navbar-brand mb-0 h1"><i class="fas fa-shield-check me-2"></i> Portal do Gestor</span>
        <span class="badge bg-light text-dark shadow-sm">ID Aprovador: <?php echo $id_gestor_logado; ?></span>
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