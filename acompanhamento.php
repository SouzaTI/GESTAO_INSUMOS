<?php
require_once __DIR__ . '/config/db.php';
if (!isset($_SESSION['usuario_id'])) { header("Location: login.php"); exit(); }

// 1. Consulta para os Cards de Resumo
$aguardando = $conn->query("SELECT COUNT(*) as total FROM pedidos_compra WHERE status IN ('PENDENTE', 'EM COTACAO', 'APROVACAO')")->fetch_assoc()['total'];
$finalizados_hoje = $conn->query("SELECT COUNT(*) as total FROM pedidos_compra WHERE status = 'FINALIZADO' AND DATE(data_finalizacao) = CURDATE()")->fetch_assoc()['total'];

// 2. Busca todos os pedidos com contagem de opções de cotação
$pedidos = $conn->query("SELECT *, 
                         TIMESTAMPDIFF(MINUTE, data_abertura, NOW()) as minutos_espera,
                         (SELECT COUNT(*) FROM cotacoes_opcoes WHERE pedido_id = pedidos_compra.id) as total_opcoes 
                         FROM pedidos_compra ORDER BY data_abertura DESC");
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Acompanhamento - Gestão Digital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
    <header class="mb-4">
        <h2 class="fw-bold">Monitor de Pedidos em Tempo Real</h2>
        <p class="text-muted small">Acompanhe e converta cotações em pedidos de compra reais.</p>
    </header>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card card-status p-4 bg-warning text-dark shadow-sm border-0">
                <small class="fw-bold text-uppercase">Aguardando Ação</small>
                <h2 class="mb-0 fw-bold"><?= $aguardando ?></h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-status p-4 bg-success text-white shadow-sm border-0">
                <small class="fw-bold text-uppercase">Finalizados Hoje</small>
                <h2 class="mb-0 fw-bold"><?= $finalizados_hoje ?></h2>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">ID</th>
                        <th>Solicitante</th>
                        <th>Fornecedor / Orçamentos</th>
                        <th>Valor Est.</th>
                        <th>Status</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($p = $pedidos->fetch_assoc()): ?>
                    <tr>
                        <td class="ps-4">#<?= $p['id'] ?></td>
                        <td><strong><?= $p['solicitante'] ?></strong></td>
                        <td>
                            <?php if($p['status'] == 'EM COTACAO'): ?>
                                <span class="badge bg-info text-dark"><i class="fas fa-tags me-1"></i> <?= $p['total_opcoes'] ?> opções</span>
                            <?php else: ?>
                                <?= $p['fornecedor'] ?: '<span class="text-muted small">A definir</span>' ?>
                            <?php endif; ?>
                        </td>
                        <td class="fw-bold text-primary">R$ <?= number_format($p['valor_total'], 2, ',', '.') ?></td>
                        <td>
                            <?php 
                                $bg = 'secondary';
                                if($p['status'] == 'EM COTACAO') $bg = 'info text-dark';
                                if($p['status'] == 'APROVACAO') $bg = 'dark text-white'; 
                                if($p['status'] == 'PENDENTE') $bg = 'warning text-dark';
                                if($p['status'] == 'APROVADO') $bg = 'primary';
                                if($p['status'] == 'FINALIZADO') $bg = 'success';
                            ?>
                            <span class="badge bg-<?= $bg ?>"><?= $p['status'] ?></span>
                        </td>
                        <td class="text-center">
                            <?php if($p['status'] == 'EM COTACAO'): ?>
                            <button class="btn btn-sm btn-primary me-1" onclick="abrirComparativo(<?= $p['id'] ?>)">
                                <i class="fas fa-balance-scale"></i> Analisar
                            </button>
                            <?php endif; ?>

                            <?php if($p['status'] == 'APROVACAO'): ?>
                            <button class="btn btn-sm btn-primary me-1" onclick="abrirModalComplementar(<?= $p['id'] ?>)">
                                <i class="fas fa-edit"></i> Complementar
                            </button>
                            <?php endif; ?>

                            <button class="btn btn-sm btn-outline-danger me-1" onclick="gerarPDF(<?= $p['id'] ?>)" title="Ver PDF">
                                <i class="fas fa-file-pdf"></i>
                            </button>

                            <?php if($p['status'] == 'APROVADO' || $p['status'] == 'PENDENTE'): ?>
                                <button class="btn btn-sm btn-success" onclick="finalizarPedido(<?= $p['id'] ?>)">
                                    <?php if($p['fornecedor'] == 'COMERCIAL SOUZA ATACADO'): ?>
                                        <i class="fas fa-hand-holding me-1"></i> Entregue
                                    <?php else: ?>
                                        <i class="fas fa-check me-1"></i> Recebido
                                    <?php endif; ?>
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalComparativo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title"><i class="fas fa-search-dollar me-2"></i>Comparativo de Orçamentos</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light">
                <div id="container_cards_comparativo" class="row g-3"></div>
            </div>
        </div>
    </div>
</div>

<?php if($p['status'] == 'APROVACAO'): ?>
<button class="btn btn-sm btn-primary me-1" onclick="abrirModalComplementar(<?= $p['id'] ?>)">
    <i class="fas fa-edit"></i> Complementar
</button>
<?php endif; ?>

<div class="modal fade" id="modalComplementar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-file-invoice-dollar me-2"></i>Finalizar Dados para Pagamento</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="comp_pedido_id">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">CNPJ Faturado</label>
                        <input type="text" id="comp_cnpj" class="form-control mascara-cnpj" placeholder="00.000.000/0000-00">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Forma de Pagamento</label>
                        <select id="comp_pgto" class="form-select" onchange="togglePixComplementar()">
                            <option value="dinheiro">DINHEIRO</option>
                            <option value="pix">PIX</option>
                            <option value="cartao_csa">CARTÃO - CSA</option>
                            <option value="cartao_mixkar">CARTÃO - MIXKAR</option>
                            <option value="cartao_autoweb">CARTÃO - AUTOWEB</option>
                            <option value="cartao_souza">CARTÃO - COMERCIAL SOUZA</option>
                            <option value="boleto">BOLETO BANCÁRIO</option>
                        </select>
                    </div>

                    <div id="painel_pix_comp" class="col-md-12 p-3 bg-light border rounded d-none">
                        <div class="row g-2">
                            <div class="col-md-6"><label class="small fw-bold">Favorecido</label><input type="text" id="comp_pix_fav" class="form-control form-control-sm"></div>
                            <div class="col-md-6"><label class="small fw-bold">Chave PIX</label><input type="text" id="comp_pix_chave" class="form-control form-control-sm"></div>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label fw-bold small">Parcelamento e Condições</label>
                        <select id="comp_parcelas" class="form-select" onchange="toggleParcelamentoPersonalizado()">
                            <option value="A Vista">À Vista</option>
                            <optgroup label="Crédito (Parcelado)">
                                <?php for($i=2; $i<=12; $i++) echo "<option value='{$i}x'>{$i}x</option>"; ?>
                            </optgroup>
                            <optgroup label="Faturamento">
                                <option value="Boleto - 28 dias">Boleto - 28 dias</option>
                                <option value="Personalizado">Outro (Digitar abaixo)</option>
                            </optgroup>
                        </select>
                    </div>

                    <div class="col-md-12 d-none" id="div_parcela_personalizada">
                        <label class="form-label fw-bold small text-primary">Descreva a condição personalizada</label>
                        <input type="text" id="comp_parcela_texto" class="form-control" placeholder="Ex: 15/30/45 dias ou Entrada + Saldo">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="salvarComplemento()" class="btn btn-success w-100 fw-bold">CONFIRMAR E GERAR PEDIDO FINAL</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    $('.mascara-cnpj').mask('00.000.000/0000-00');
});

// Funções de Interface
function togglePixComplementar() {
    $('#painel_pix_comp').toggleClass('d-none', $('#comp_pgto').val() !== 'pix');
}

function toggleParcelamentoPersonalizado() {
    const isPersonalizado = $('#comp_parcelas').val() === 'Personalizado';
    $('#div_parcela_personalizada').toggleClass('d-none', !isPersonalizado);
    if(isPersonalizado) $('#comp_parcela_texto').focus();
}

function abrirComparativo(pedidoId) {
    $.get('api/get_cotacoes.php', { id: pedidoId }, function(res) {
        if(res.success) {
            let h = '';
            res.cotacoes.forEach(c => {
                let totalItem = parseFloat(c.valor_unitario) + parseFloat(c.valor_frete);
                h += `
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-header bg-white fw-bold text-center py-3 text-primary">
                            <i class="fas fa-shopping-bag me-1"></i> ${c.fornecedor_nome}
                        </div>
                        <div class="card-body text-center">
                            <h3 class="fw-bold text-success">R$ ${totalItem.toLocaleString('pt-br',{minimumFractionDigits:2})}</h3>
                            <p class="small text-muted mb-3">Unit: R$ ${parseFloat(c.valor_unitario).toLocaleString('pt-br',{minimumFractionDigits:2})} | Frete: R$ ${parseFloat(c.valor_frete).toLocaleString('pt-br',{minimumFractionDigits:2})}</p>
                            <div class="bg-light p-2 rounded small mb-3"><i class="fas fa-truck me-1"></i> ${c.prazo_entrega}</div>
                            <a href="${c.link_produto}" target="_blank" class="btn btn-sm btn-outline-secondary w-100 mb-2">Ver Anúncio</a>
                            <button onclick="escolherOpcao(${c.id}, ${pedidoId})" class="btn btn-primary w-100 fw-bold uppercase">Aprovar Este</button>
                        </div>
                    </div>
                </div>`;
            });
            $('#container_cards_comparativo').html(h);
            $('#modalComparativo').modal('show');
        }
    });
}

function escolherOpcao(opcaoId, pedidoId) {
    Swal.fire({
        title: 'Confirmar Escolha?',
        text: "O fornecedor será selecionado e o comprador preencherá os dados fiscais.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sim, Escolher!',
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('api/aprovar_cotacao.php', { opcao_id: opcaoId, pedido_id: pedidoId }, function(res) {
                if(res.success) {
                    Swal.fire('Sucesso!', 'Aguardando complemento do comprador.', 'success').then(() => location.reload());
                }
            }, 'json');
        }
    });
}

function abrirModalComplementar(id) {
    $.get('api/get_pedido_info.php', { id: id }, function(p) {
        $('#comp_pedido_id').val(id);
        
        // Limpa resumos anteriores e insere o novo cabeçalho informativo
        $('#modalComplementar .info-resumo').remove();
        const cabecalho = `
            <div class="info-resumo alert alert-primary py-2 mb-3 small d-flex justify-content-between">
                <span><strong>PEDIDO:</strong> #${p.id}</span>
                <span><strong>FORNECEDOR:</strong> ${p.fornecedor}</span>
                <span><strong>VALOR:</strong> R$ ${parseFloat(p.valor_total).toLocaleString('pt-br',{minimumFractionDigits:2})}</span>
            </div>`;
        
        $('#modalComplementar .modal-body').prepend(cabecalho);
        $('#modalComplementar').modal('show');
    }, 'json');
}

function salvarComplemento() {
    let parcelamentoFinal = $('#comp_parcelas').val();
    if(parcelamentoFinal === 'Personalizado') {
        parcelamentoFinal = $('#comp_parcela_texto').val();
        if(!parcelamentoFinal) return Swal.fire('Erro', 'Descreva a condição personalizada', 'error');
    }

    const dados = {
        id: $('#comp_pedido_id').val(),
        cnpj: $('#comp_cnpj').val(),
        pgto: $('#comp_pgto').val(),
        pix_fav: $('#comp_pix_fav').val(),
        pix_chave: $('#comp_pix_chave').val(),
        parcelas: parcelamentoFinal
    };

    if(!dados.cnpj) return Swal.fire('Erro', 'CNPJ é obrigatório', 'error');

    $.post('api/complementar_pedido.php', dados, function(res) {
        if(res.success) {
            Swal.fire('Pronto!', 'Pedido aprovado com sucesso.', 'success').then(() => location.reload());
        } else {
            Swal.fire('Erro', res.message, 'error');
        }
    }, 'json');
}

function finalizarPedido(id) {
    Swal.fire({
        title: 'Finalizar Recebimento?',
        text: "O estoque será atualizado com os itens deste pedido.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Confirmar',
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.showLoading();
            $.post('api/finalizar_pedido.php', { id: id }, function(res) {
                if(res.success) {
                    Swal.fire('Sucesso!', 'Estoque atualizado.', 'success').then(() => location.reload());
                }
            }, 'json');
        }
    });
}

function gerarPDF(id) { window.open('api/gerar_recibo.php?id=' + id, '_blank'); }
</script>
</body>
</html>