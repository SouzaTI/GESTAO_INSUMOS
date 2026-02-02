<?php
require_once __DIR__ . '/config/db.php';

$req_id = isset($_GET['req_id']) ? (int)$_GET['req_id'] : null;
$req_dados = null;

if ($req_id) {
    // Busca os dados da solicitação enviada "da rua"
    $req_dados = $conn->query("SELECT * FROM requisicoes_externas WHERE id = $req_id")->fetch_assoc();
}

if (!isset($_SESSION['usuario_id'])) { header("Location: login.php"); exit(); }
$nome_usuario = $_SESSION['usuario_nome'];

// Busca as permissões dinâmicas do usuário
$id_user = $_SESSION['usuario_id'];
$user_data = $conn->query("SELECT privilegios FROM usuarios WHERE id = $id_user")->fetch_assoc();
$perm = json_decode($user_data['privilegios'] ?? '{}', true);

$pode_comprar = isset($perm['comprar']) && $perm['comprar'] == true;
$pode_estoque = isset($perm['estoque']) && $perm['estoque'] == true;
$pode_ver_financeiro = isset($perm['financeiro']) && $perm['financeiro'] == true;
$query_aprovadores = $conn->query("SELECT nome FROM usuarios ORDER BY nome ASC");
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Central de Movimentação</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
    <header class="mb-4 d-flex justify-content-between align-items-center">
        <div>
            <h2 class="fw-bold">Central de Fluxo Inteligente</h2>
            <p class="text-muted">Selecione o seu perfil de operação abaixo.</p>
        </div>
        <div class="text-end text-muted small">
            <i class="fas fa-user-circle me-1"></i> <?php echo $nome_usuario; ?>
        </div>
    </header>

    <div class="row g-3 mb-4">
        <?php if ($pode_comprar): ?>
        <div class="col-md-6">
            <div class="card mode-card p-4 text-center shadow-sm" onclick="setModo('compra')">
                <i class="fas fa-shopping-cart fa-2x mb-2 text-primary"></i>
                <h5 class="fw-bold mb-0">Solicitação de Compra</h5>
                <small class="text-muted">Pedidos externos e direcionamento interno</small>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($pode_estoque): ?>
        <div class="col-md-6">
            <div class="card mode-card p-4 text-center shadow-sm" onclick="setModo('estoque')">
                <i class="fas fa-warehouse fa-2x mb-2 text-danger"></i>
                <h5 class="fw-bold mb-0">Gestão de Estoque</h5>
                <small class="text-muted">Entradas físicas e registros de avaria</small>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div id="container_formulario" class="d-none">
        <div class="card p-4 mb-4 shadow-sm border-start border-4" id="header_card">
            <h5 class="section-header" id="header_titulo"></h5>
            
            <div class="row g-3 campos-compra">
                <div class="col-md-12 mb-2">
                    <label class="form-label fw-bold text-primary small">Origem do Produto / Tipo de Compra</label>
                    <select id="m_tipo_compra" class="form-select border-primary" onchange="ajustarCamposCompra()">
                        <option value="cotacao">🔍 APENAS COTAÇÃO (Busca de Preços)</option>
                        <option value="externo">💰 COMPRA DIRETA (Já tenho o Fornecedor e Valor)</option>
                        <option value="interno">🏢 RETIRADA INTERNA (Estoque Próprio)</option>
                    </select>
                </div>

                <div class="col-md-4 div-externo"><label class="form-label fw-bold small">Fornecedor (Externo)</label><input type="text" id="master_fornecedor" class="form-control"></div>
                <div class="col-md-4 div-externo"><label class="form-label fw-bold small">CNPJ Faturado</label><input type="text" id="master_cnpj" class="form-control" placeholder="00.000.000/0000-00"></div>
                
                <div class="col-md-4 div-externo">
                    <label class="form-label fw-bold small">Forma de Pagamento</label>
                    <select id="master_pgto" class="form-select" onchange="togglePainelPix()">
                        <option value="dinheiro">DINHEIRO</option>
                        <option value="pix">PIX</option>
                        <option value="cartao_csa">CARTÃO DE CRÉDITO - CSA</option>
                        <option value="cartao_mixkar">CARTÃO DE CRÉDITO - MIXKAR</option>
                        <option value="cartao_autoweb">CARTÃO DE CRÉDITO - AUTOWEB</option>
                        <option value="cartao_souza">CARTÃO DE CRÉDITO - COMERCIAL SOUZA</option>
                        <option value="boleto">BOLETO BANCÁRIO</option>
                    </select>
                </div>

                <div id="painel_pix" class="col-md-12 p-3 bg-light border rounded mb-3 d-none">
                    <h6 class="fw-bold text-primary mb-2 small"><i class="fas fa-qrcode me-2"></i>Dados para Pagamento PIX</h6>
                    <div class="row g-2">
                        <div class="col-md-5">
                            <label class="small fw-bold">Nome do Favorecido</label>
                            <input type="text" id="pix_favorecido" class="form-control form-control-sm" placeholder="Nome completo ou Razão Social">
                        </div>
                        <div class="col-md-3">
                            <label class="small fw-bold">Tipo de Chave</label>
                            <select id="pix_tipo_chave" class="form-select form-select-sm">
                                <option value="cpf_cnpj">CPF / CNPJ</option>
                                <option value="email">E-mail</option>
                                <option value="telefone">Telefone</option>
                                <option value="aleatoria">Chave Aleatória</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="small fw-bold">Chave PIX</label>
                            <input type="text" id="pix_chave" class="form-control form-control-sm" placeholder="Insira a chave aqui">
                        </div>
                    </div>
                </div>

                <div class="col-md-3 div-externo">
                    <label class="form-label fw-bold small">Parcelamento</label>
                    <select id="master_parcelas" class="form-select">
                        <option value="A Vista" selected>À Vista</option>
                        <?php for($i=2; $i<=12; $i++) echo "<option value='{$i}x'>{$i}x</option>"; ?>
                        <option value="Personalizado">Personalizado (Dias)</option>
                    </select>
                </div>
                <div class="col-md-3 div-externo">
                    <label class="form-label fw-bold small">Valor Total Pedido</label>
                    <input type="text" id="master_total" class="form-control bg-light fw-bold text-primary" readonly value="0,00">
                </div>
                
                <div class="col-md-3">
                    <label class="form-label fw-bold small">Quem Solicitou?</label>
                    <input type="text" id="master_solicitante" class="form-control" 
                        value="<?= $req_dados ? htmlspecialchars($req_dados['solicitante']) : $nome_usuario ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold small">Quem Aprovou?</label>
                    <select id="master_aprovador" class="form-select">
                        <option value="">Selecione o Gestor...</option>
                        <?php while($user = $query_aprovadores->fetch_assoc()): ?>
                            <option value="<?= $user['nome'] ?>"><?= $user['nome'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>

            <div class="row g-3 campos-estoque">
                <div class="col-md-4">
                    <label class="form-label fw-bold small">Tipo de Registro</label>
                    <select id="master_tipo_estoque" class="form-select" onchange="ajustarCamposEstoque()">
                        <option value="entrada">📥 Entrada Física de Estoque</option>
                        <option value="avaria">⚠️ Registro de Avaria / Perda</option>
                    </select>
                </div>

                <div class="col-md-4 div-destino-estoque">
                    <label class="form-label fw-bold small">Destinação Final</label>
                    <select id="master_destino_estoque" class="form-select" onchange="ajustarCamposEstoque()">
                        <option value="uso_consumo">🏢 Uso e Consumo Interno</option>
                        <option value="revenda">💰 Revenda (Recuperado para Venda)</option>
                        <option value="descarte">🗑️ Descarte / Sucata</option>
                    </select>
                </div>

                <div class="col-md-4"><label class="form-label fw-bold small">Quem Conferiu?</label><input type="text" id="master_conferente" class="form-control"></div>
                <div class="col-md-4"><label class="form-label fw-bold small">Quem Armazenou?</label><input type="text" id="master_armazenador" class="form-control"></div>
            </div>
        </div>

        <div class="card p-4 mb-4 bg-white border shadow-sm">
            <h5 class="fw-bold mb-3"><i class="fas fa-cart-plus me-2 text-primary"></i>Adicionar Item ao Carrinho</h5>
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-bold small">Produto no Catálogo</label>
                    <div class="input-group">
                        <select id="busca_produto" class="form-control"></select>
                        <button class="btn btn-primary px-3" type="button" onclick="novoInsumoManual()" title="Digitar novo item manual"><i class="fas fa-keyboard"></i></button>
                    </div>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label fw-bold small">Qtd / Unidade</label>
                    <div class="input-group">
                        <input type="number" id="item_qtd" class="form-control" value="1" oninput="calcularSubtotalItem()">
                        <select id="item_unidade" class="form-select text-center" style="max-width: 80px;">
                            <option value="UN">UN</option>
                            <option value="CX">CX</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-3" id="div_btn_cotacao">
                    <label class="form-label fw-bold small text-primary">Orçamentos Encontrados</label>
                    <button type="button" class="btn btn-outline-primary w-100" data-bs-toggle="modal" data-bs-target="#modalCotacao">
                        <i class="fas fa-list-ol me-2"></i>Lançar Cotações (<span id="count_cotacoes">0</span>)
                    </button>
                </div>

                <div class="col-md-2" id="div_valor_direto">
                    <label class="form-label fw-bold small" id="label_valor_unit">Valor Unitário</label>
                    <input type="text" id="item_valor_unit" class="form-control mascara-moeda" placeholder="0,00">
                </div>

                <div class="col-md-2" id="campo_lote_venc">
                    <label class="form-label fw-bold small">Lote / Vencimento</label>
                    <input type="text" id="item_lote_venc" class="form-control" placeholder="Lote/Validade">
                </div>

                <div class="col-md-2" id="div_obs">
                    <label class="form-label fw-bold small" id="label_obs">Observação</label>
                    <input type="text" id="item_obs" class="form-control" placeholder="...">
                </div>

                <div class="col-md-12 mt-3">
                    <button type="button" onclick="adicionarAoCarrinho()" class="btn btn-primary w-100 fw-bold py-2 shadow-sm">
                        <i class="fas fa-plus-circle me-2"></i>INSERIR ITEM NA LISTA
                    </button>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 overflow-hidden mb-4">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th class="ps-4">Item</th>
                        <th>Qtd/Unid</th>
                        <th>Info Financeira</th>
                        <th>Subtotal Est.</th>
                        <th>Lote/Venc</th>
                        <th>Observação</th>
                        <th class="text-center">Ação</th>
                    </tr>
                </thead>
                <tbody id="corpo_lista"></tbody>
            </table>
        </div>

        <button type="button" onclick="finalizarTudo()" class="btn btn-success btn-lg w-100 mt-2 shadow rounded-pill"><i class="fas fa-check-double me-2"></i>FINALIZAR OPERAÇÃO COMPLETA</button>
    </div>
</div>

<div class="modal fade" id="modalCotacao" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Lançar Opções de Fornecedores</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2 mb-3 p-3 bg-light border rounded">
                    <div class="col-md-3"><label class="small fw-bold">Fornecedor</label><input type="text" id="cot_fornecedor" class="form-control form-control-sm" placeholder="Ex: Mercado Livre"></div>
                    <div class="col-md-2"><label class="small fw-bold">Val. Unit</label><input type="text" id="cot_valor" class="form-control form-control-sm moeda" placeholder="0,00"></div>
                    <div class="col-md-2"><label class="small fw-bold">Frete</label><input type="text" id="cot_frete" class="form-control form-control-sm moeda" placeholder="0,00"></div>
                    <div class="col-md-2"><label class="small fw-bold">Entrega (Dias)</label><input type="text" id="cot_entrega" class="form-control form-control-sm" placeholder="Ex: 3 dias"></div>
                    <div class="col-md-3"><label class="small fw-bold">Link/Anúncio</label><input type="text" id="cot_link" class="form-control form-control-sm" placeholder="http://..."></div>
                    <div class="col-md-12 mt-2">
                        <button type="button" onclick="salvarOpcaoCotacao()" class="btn btn-sm btn-dark w-100">ADICIONAR ESTA OPÇÃO À COMPARAÇÃO</button>
                    </div>
                </div>
                <table class="table table-sm small">
                    <thead><tr><th>Fornecedor</th><th>Unitário</th><th>Frete</th><th>Prazo</th><th>Ação</th></tr></thead>
                    <tbody id="lista_cotacoes_temporaria"></tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Confirmar Orçamentos</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
let modoAtual = '';
let listaItens = [];
let cotacoesTemporarias = [];

function togglePainelPix() {
    const pgto = $('#master_pgto').val();
    if(pgto === 'pix') {
        $('#painel_pix').removeClass('d-none');
    } else {
        $('#painel_pix').addClass('d-none');
        $('#pix_favorecido, #pix_chave').val('');
    }
}

function setModo(modo) {
    listaItens = []; 
    renderTabela();
    atualizarTotalCabeçalho();
    modoAtual = modo;
    $('.mode-card').removeClass('active');
    $(`.mode-card:has(.fa-${modo === 'compra' ? 'shopping-cart' : 'warehouse'})`).addClass('active');
    $('#container_formulario').removeClass('d-none');
    
    if(modo === 'compra') {
        $('#header_titulo').text('Dados da Solicitação de Compra');
        $('#campo_lote_venc').addClass('d-none');
        $('.campos-compra').show(); $('.campos-estoque').hide();
        ajustarCamposCompra();
    } else {
        $('#header_titulo').text('Dados da Operação de Estoque');
        $('#campo_lote_venc').removeClass('d-none');
        $('#div_btn_cotacao').hide();
        $('#div_valor_direto').show();
        $('.campos-compra').hide(); $('.campos-estoque').show();
        ajustarCamposEstoque();
    }
}

function ajustarCamposCompra() {
    const tipo = $('#m_tipo_compra').val();
    
    if(tipo === 'cotacao') {
        $('.div-externo').hide(); // Esconde CNPJ, Fornecedor Master, Pagamento
        $('#div_btn_cotacao').show(); // Mostra apenas o botão de lançar os orçamentos
        $('#div_valor_direto').hide();
    } else if(tipo === 'externo') {
        $('.div-externo').show(); // Mostra tudo para fechar o pedido na hora
        $('#div_btn_cotacao').hide(); 
        $('#div_valor_direto').show();
    } else {
        $('.div-externo').hide();
        $('#div_btn_cotacao').hide();
        $('#div_valor_direto').show();
    }
}
function ajustarCamposEstoque() {
    const tipo = $('#master_tipo_estoque').val();
    const destino = $('#master_destino_estoque').val();
    if(tipo === 'avaria') {
        $('.div-destino-estoque').hide();
        $('#label_valor_unit').text('Custo da Perda');
    } else {
        $('.div-destino-estoque').show();
        $('#label_valor_unit').text(destino === 'revenda' ? 'Venda Unit.' : 'Custo Unit.');
    }
}

function salvarOpcaoCotacao() {
    const f = $('#cot_fornecedor').val();
    const v = $('#cot_valor').val();
    const fr = $('#cot_frete').val() || '0,00';
    const e = $('#cot_entrega').val();
    const l = $('#cot_link').val();

    if(!f || !v) return Swal.fire('Erro', 'Fornecedor e Valor são obrigatórios', 'error');

    cotacoesTemporarias.push({ fornecedor: f, valor: v, frete: fr, entrega: e, link: l });
    $('#cot_fornecedor, #cot_valor, #cot_frete, #cot_entrega, #cot_link').val('');
    renderCotacoesTemporarias();
}

function renderCotacoesTemporarias() {
    let h = '';
    cotacoesTemporarias.forEach((c, i) => {
        h += `<tr><td>${c.fornecedor}</td><td>R$ ${c.valor}</td><td>R$ ${c.frete}</td><td>${c.entrega}</td><td><button class="btn btn-sm btn-danger" onclick="cotacoesTemporarias.splice(${i},1);renderCotacoesTemporarias();"><i class="fas fa-times"></i></button></td></tr>`;
    });
    $('#lista_cotacoes_temporaria').html(h);
    $('#count_cotacoes').text(cotacoesTemporarias.length);
}

$(document).ready(function() {
    $('#master_cnpj').mask('00.000.000/0000-00');
    $('.moeda, .mascara-moeda').mask('#.##0,00', {reverse: true});
    
    // ATUALIZAÇÃO DO SELECT2 PARA SUPORTAR TAGS (ITENS MANUAIS)
    $('#busca_produto').select2({
        ajax: { 
            url: 'api/search_products.php', 
            dataType: 'json', 
            delay: 250, 
            processResults: d => ({ results: d }), 
            cache: true 
        },
        placeholder: 'Pesquise ou Digite um novo item...',
        minimumInputLength: 1,
        tags: true, // Permite criar novas tags digitando
        createTag: function (params) {
            var term = $.trim(params.term);
            if (term === '') return null;
            return {
                id: 'NOVO_' + term, // Identifica item novo para a API
                text: term,
                newTag: true
            }
        }
    });

    $('#busca_produto').on('select2:select', function (e) {
        if(e.params.data.unid) $('#item_unidade').val(e.params.data.unid).trigger('change');
    });

    <?php if ($req_id && $req_dados): ?>
        // Ativa o formulário de compra imediatamente
        setModo('compra'); 
        
        // Mostra um aviso com a lista de itens que o setor pediu
        Swal.fire({
            title: 'Requisição Importada!',
            html: `<div class="text-start small">
                    <strong>Solicitante:</strong> <?= htmlspecialchars($req_dados['solicitante']) ?><br>
                    <strong>Setor:</strong> <?= htmlspecialchars($req_dados['setor']) ?><br><hr>
                    <strong>Itens Pedidos:</strong><br>
                    <div class="bg-light p-2 mt-2" style="white-space: pre-wrap; font-family: monospace;"><?= addslashes($req_dados['descricao_itens']) ?></div>
                </div>`,
            icon: 'info',
            confirmButtonText: 'Entendido'
        });
    <?php endif; ?>
});

function novoInsumoManual() {
    $('#busca_produto').val(null).trigger('change');
    $('#busca_produto').select2('open'); // Foca no campo para digitar
}

function adicionarAoCarrinho() {
    const data = $('#busca_produto').select2('data')[0];
    const q = $('#item_qtd').val();
    const tipoCompra = $('#m_tipo_compra').val(); // Captura se é cotacao, externo ou interno
    
    if(!data || q <= 0) return Swal.fire('Aviso', 'Selecione ou digite o produto e a quantidade', 'warning');

    let produtoNome = data.text.toUpperCase();
    let produtoId = data.id.toString().includes('NOVO_') ? 0 : data.id;

    let valor_ref = 0;
    let info_financeira = "";

    // NOVA LÓGICA DE VALIDAÇÃO
    if(modoAtual === 'compra' && tipoCompra === 'cotacao') {
        // Trava de cotação apenas para o modo de busca de preços
        if(cotacoesTemporarias.length === 0) {
            return Swal.fire('Atenção', 'Para o modo cotação, lance as opções de fornecedores primeiro.', 'warning');
        }
        
        // Usa o menor valor entre as cotações para o cálculo
        valor_ref = Math.min(...cotacoesTemporarias.map(c => parseFloat(c.valor.replace('.','').replace(',','.'))));
        info_financeira = `<span class="badge bg-primary">${cotacoesTemporarias.length} Cotações</span>`;
    } else {
        // Para COMPRA DIRETA (externo), INTERNO ou ESTOQUE, usa o valor do campo "Valor Unitário"
        valor_ref = parseFloat($('#item_valor_unit').val().replace('.', '').replace(',', '.')) || 0;
        
        // Verifica se o valor foi preenchido em caso de compra direta
        if(modoAtual === 'compra' && tipoCompra === 'externo' && valor_ref <= 0) {
            return Swal.fire('Aviso', 'Insira o Valor Unitário para esta compra direta.', 'warning');
        }
        
        info_financeira = "R$ " + ($('#item_valor_unit').val() || "0,00");
    }

    listaItens.push({ 
        id: produtoId, 
        nome: produtoNome, 
        qtd: q, 
        unid: $('#item_unidade').val(), 
        lote: $('#item_lote_venc').val(), 
        obs: $('#item_obs').val(),
        valor_unit: valor_ref, 
        subtotal: (parseFloat(q) * valor_ref),
        cotacoes: [...cotacoesTemporarias]
    });
    
    cotacoesTemporarias = [];
    renderCotacoesTemporarias();
    renderTabela();
    atualizarTotalCabeçalho();
    $('#item_qtd').val(1); $('#item_obs, #item_lote_venc, #item_valor_unit').val('');
    $('#busca_produto').val(null).trigger('change');
}

function atualizarTotalCabeçalho() {
    let soma = 0;
    listaItens.forEach(it => { soma += it.subtotal; });
    $('#master_total').val(soma.toLocaleString('pt-br', {minimumFractionDigits: 2}));
}

function renderTabela() {
    let h = '';
    let verFinanceiro = <?php echo $pode_ver_financeiro ? 'true' : 'false'; ?>;
    listaItens.forEach((it, i) => { 
        let info = it.cotacoes.length > 0 ? `<span class="badge bg-info text-dark">${it.cotacoes.length} Orçamentos</span>` : `R$ ${it.valor_unit.toLocaleString('pt-br', {minimumFractionDigits: 2})}`;
        h += `<tr><td class="ps-4"><strong>${it.nome}</strong></td><td>${it.qtd} ${it.unid}</td><td class="${verFinanceiro ? '' : 'd-none'}">${info}</td><td class="fw-bold ${verFinanceiro ? '' : 'd-none'}">R$ ${it.subtotal.toLocaleString('pt-br', {minimumFractionDigits: 2})}</td><td>${it.lote}</td><td>${it.obs}</td><td class="text-center"><button class="btn btn-sm btn-danger" onclick="listaItens.splice(${i},1);renderTabela();atualizarTotalCabeçalho();"><i class="fas fa-trash"></i></button></td></tr>`; 
    });
    $('#corpo_lista').html(h);
}

function finalizarTudo() {
    if(listaItens.length === 0) return Swal.fire('Erro', 'Lista vazia!', 'error');

    const tipoCompra = $('#m_tipo_compra').val();
    
    // Lógica simples: Se for interno, define o fornecedor fixo, senão pega o do campo
    let fornecedorFinal = (tipoCompra === 'interno') ? 'COMERCIAL SOUZA ATACADO' : $('#master_fornecedor').val();

    const dados = {
        modo: modoAtual,
        cabecalho: {
            tipo_compra: tipoCompra,
            solicitante: $('#master_solicitante').val() || 'Administrador',
            fornecedor: fornecedorFinal, // Aqui vai o nome automático ou o digitado
            cnpj: $('#master_cnpj').val() || '',
            pgto: $('#master_pgto').val() || '',
            pix_favorecido: $('#pix_favorecido').val() || '',
            pix_tipo_chave: $('#pix_tipo_chave').val() || '',
            pix_chave: $('#pix_chave').val() || '',
            parcelas: $('#master_parcelas').val() || 'A Vista',
            total: $('#master_total').val() || '0,00',
            tipo_estoque: $('#master_tipo_estoque').val() || 'entrada',
            destino_estoque: $('#master_destino_estoque').val() || 'uso_consumo'
        },
        itens: listaItens
    };

    $.ajax({
        url: 'api/salvar_movimentacao.php',
        type: 'POST',
        data: JSON.stringify(dados),
        contentType: 'application/json',
        success: function(res) {
            if(res.success) { 
                Swal.fire('Sucesso!', 'Operação enviada.', 'success').then(() => window.location.href = 'acompanhamento.php'); 
            } else { 
                Swal.fire('Erro', res.message, 'error'); 
            }
        }
    });
}

function calcularSubtotalItem() {
    const qtd = parseFloat($('#item_qtd').val()) || 0;
    const valorUnit = parseFloat($('#item_valor_unit').val().replace('.', '').replace(',', '.')) || 0;
    const subtotal = qtd * valorUnit;
    
    // Se você tiver um campo visual para mostrar o subtotal antes de adicionar, atualize-o aqui
    console.log("Subtotal calculado: ", subtotal);
}

</script>
</body>
</html>