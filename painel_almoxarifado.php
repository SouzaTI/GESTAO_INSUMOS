<?php
require_once __DIR__ . '/config/db.php';

// Proteção básica: se não estiver logado no sistema, nem abre o painel
if (!isset($_SESSION['usuario_id'])) {
    die("Acesso restrito. Por favor, faça login para operar o almoxarifado.");
}

$operador_nome = $_SESSION['usuario_nome'];
$operador_id = $_SESSION['usuario_id'];

/** * Busca pedidos aprovados vinculando ao banco do GLPI */
$query = "SELECT r.*, u.firstname as nome_gestor 
          FROM requisicoes r
          LEFT JOIN glpidb_att.glpi_users u ON r.aprovador_id = u.id
          WHERE r.status_pedido = 'APROVADO_GESTOR' 
          ORDER BY r.data_criacao ASC";
$pedidos_vips = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Triagem Almoxarifado - Gestão de Insumos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fc; }
        .table-container { background: white; border-radius: 15px; overflow: hidden; }
        .badge-gestor { background-color: #e7f0ff; color: #1e3c72; border: 1px solid #b3d1ff; }
        .item-detalhe { font-size: 0.85rem; line-height: 1.4; }
        .btn-acao { transition: all 0.2s; }
        .btn-acao:hover { transform: scale(1.05); }
        .barra-operador { background: #fff; border-bottom: 2px solid #eee; padding: 10px 20px; margin-bottom: 20px; }
    </style>
</head>
<body class="p-0">

<div class="barra-operador d-flex justify-content-between align-items-center">
    <div class="small text-muted">
        <i class="fas fa-user-shield me-1"></i> Operador Logado: <strong><?php echo $operador_nome; ?></strong>
    </div>
    <div class="text-success small fw-bold">
        <i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i> SISTEMA PRONTO PARA BAIXAS
    </div>
</div>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0">
                <i class="fas fa-warehouse me-2 text-warning"></i> Triagem de Saída
            </h3>
            <p class="text-muted small mb-0">Pedidos validados por gestores aguardando processamento físico</p>
        </div>
        <span class="badge bg-dark px-3 py-2">Fila de Separação: <?php echo $pedidos_vips->num_rows; ?></span>
    </div>

    <?php if($pedidos_vips->num_rows > 0): ?>
        <div class="table-container shadow-sm">
            <table class="table table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th class="ps-4">ID</th>
                        <th>Solicitante / Setor</th>
                        <th>Autorização</th>
                        <th>Itens & Distribuição</th>
                        <th class="text-center pe-4">Ações de Saída</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $pedidos_vips->fetch_assoc()): ?>
                        <tr class="align-middle">
                            <td class="ps-4 fw-bold text-muted">#<?php echo $row['id']; ?></td>
                            <td>
                                <div class="fw-bold"><?php echo $row['solicitante']; ?></div>
                                <div class="small text-uppercase text-secondary"><?php echo $row['setor']; ?></div>
                            </td>
                            <td>
                                <span class="badge badge-gestor">
                                    <i class="fas fa-user-check me-1"></i> 
                                    <?php echo $row['nome_gestor'] ?? 'Gestor Externo'; ?>
                                </span>
                                <div class="mt-1" style="font-size: 0.7rem; color: #666;">
                                    Motivo: <?php echo htmlspecialchars($row['motivo_solicitacao']); ?>
                                </div>
                            </td>
                            <td style="max-width: 400px;">
                                <?php 
                                $id_r = $row['id'];
                                $its = $conn->query("SELECT * FROM requisicao_itens WHERE requisicao_id = $id_r");
                                while($i = $its->fetch_assoc()): ?>
                                    <div class="mb-2 p-2 border-start border-primary border-3 bg-light rounded shadow-sm item-detalhe">
                                        <div class="d-flex justify-content-between">
                                            <span class="fw-bold"><?php echo $i['produto_nome']; ?></span>
                                            <span class="badge bg-primary rounded-pill"><?php echo $i['quantidade']; ?> un</span>
                                        </div>
                                        <div class="text-muted mt-1 italic">
                                            <i class="fas fa-arrow-right me-1" style="font-size: 0.7rem;"></i>
                                            <?php echo str_replace('|', ' • ', $i['destino_detalhado']); ?>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </td>
                            <td class="text-center pe-4">
                                <div class="d-grid gap-2">
                                    <button class="btn btn-success btn-sm btn-acao" 
                                            onclick="finalizarSaida(<?php echo $row['id']; ?>, 'ESTOQUE')">
                                        <i class="fas fa-box-open me-1"></i> Baixa Estoque
                                    </button>
                                    <button class="btn btn-outline-primary btn-sm btn-acao" 
                                            onclick="finalizarSaida(<?php echo $row['id']; ?>, 'COMPRA')">
                                        <i class="fas fa-shopping-cart me-1"></i> Solicitar Compra
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="text-center py-5 bg-white rounded shadow-sm mt-3">
            <i class="fas fa-check-circle fa-4x text-light mb-3"></i>
            <h5 class="text-secondary">Nenhum pedido aprovado pendente!</h5>
            <p class="text-muted">Tudo em dia com as aprovações dos gestores.</p>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function finalizarSaida(id, origem) {
    if(origem === 'ESTOQUE') {
        Swal.fire({
            title: 'Registrar Entrega',
            html: `<p class="small">Informe quem está vindo retirar o material no balcão:</p>
                   <input type="text" id="retirado_por" class="swal2-input" placeholder="Nome do portador">`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Confirmar Saída',
            confirmButtonColor: '#198754',
            preConfirm: () => {
                const nome = Swal.getPopup().querySelector('#retirado_por').value;
                if (!nome) { Swal.showValidationMessage('Você precisa informar quem retirou!'); }
                return { nome: nome };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                enviarFinalizacao(id, 'FINALIZADO', result.value.nome);
            }
        });
    } else {
        Swal.fire({
            title: 'Mover para Compras?',
            text: "O item seguirá para o setor de compras com o selo de aprovado pelo gestor.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sim, mover para compras',
            confirmButtonColor: '#0d6efd'
        }).then((result) => {
            if (result.isConfirmed) {
                enviarFinalizacao(id, 'SOLICITAR_COMPRA', 'SETOR DE COMPRAS');
            }
        });
    }
}

function enviarFinalizacao(id, status, responsavel) {
    const params = new URLSearchParams();
    params.append('id', id);
    params.append('status', status);
    params.append('retirado_por', responsavel);
    // Envia o ID do operador para o registro de log
    params.append('operador_id', <?php echo $operador_id; ?>);

    fetch('api/finalizar_pedido.php', { method: 'POST', body: params })
    .then(r => r.json())
    .then(res => {
        if(res.success) {
            Swal.fire({
                title: 'Sucesso!',
                text: 'Processo concluído e carimbado por <?php echo $operador_nome; ?>.',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            }).then(() => location.reload());
        } else {
            Swal.fire('Erro!', res.message, 'error');
        }
    });
}
</script>
</body>
</html>