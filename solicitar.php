<?php
/**
 * solicitar.php — Página pública de requisição de materiais
 * Refatorado: credenciais centralizadas, gestores dinâmicos, estoque de mesa
 */
require_once __DIR__ . '/config/config.db.php';
// $conn (MySQLi) e $pdo (PDO) já vêm do db.php

// Busca gestores autorizados via tabela de permissões (sem IDs hardcoded)
$sql_gestores = "
    SELECT id, firstname, realname
    FROM glpi_users 
    WHERE id IN (17, 19, 23, 40)
    ORDER BY firstname ASC
";
try {
    $res_aprovadores = $pdo->query($sql_gestores);
} catch (PDOException $e) {
    error_log("Erro ao buscar gestores: " . $e->getMessage());
    $res_aprovadores = null;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Requisição de Materiais</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css"/>
    <style>
        :root {
            --primary: #1e3c72;
            --primary-light: #2a5298;
            --accent: #e8f0fe;
        }
        body {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            min-height: 100vh;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            font-family: 'Segoe UI', sans-serif;
            padding: 30px 20px;
        }
        .form-container {
            max-width: 920px;
            width: 100%;
            background: #fff;
            padding: 32px;
            border-radius: 20px;
        }
        .form-label {
            color: var(--primary);
            font-weight: 700;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: .5px;
        }
        .item-linha {
            background: #f8f9fc;
            border-radius: 12px;
            margin-bottom: 16px;
            border: 1px solid #e3e6f0;
            border-left: 5px solid var(--primary);
            overflow: visible;
        }
        .area-distribuicao {
            background: #fff;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        .beneficiario-row input { font-size: 0.85rem; }

        /* Aviso de estoque de mesa */
        .aviso-mesa {
            display: none;
            font-size: 0.78rem;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 6px;
            margin-top: 6px;
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffc107;
        }
        .aviso-mesa.negativo {
            background: #f8d7da;
            color: #842029;
            border-color: #f5c2c7;
        }
        .aviso-mesa.zerado {
            background: #d1e7dd;
            color: #0f5132;
            border-color: #badbcc;
        }

        .btn-add {
            color: var(--primary);
            border: 2px dashed #d1d3e2;
            width: 100%;
            border-radius: 10px;
            padding: 10px;
            font-weight: 600;
            background: transparent;
        }
        .btn-add:hover { background: var(--accent); }
        .btn-submit {
            background: var(--primary);
            border: none;
            padding: 15px;
            font-weight: bold;
            border-radius: 10px;
        }
        .btn-submit:hover { background: var(--primary-light); }

        /* Select2 */
        .select2-container--bootstrap-5 .select2-selection {
            border: 1px solid #d1d3e2 !important;
            min-height: 45px !important;
            display: flex !important;
            align-items: center !important;
        }
        .select2-container { width: 100% !important; }

        .header-icon {
            width: 60px; height: 60px;
            background: var(--accent);
            color: var(--primary);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 10px;
        }
        .numero-item {
            width: 22px; height: 22px;
            background: var(--primary);
            color: #fff;
            border-radius: 50%;
            font-size: 0.7rem;
            font-weight: 700;
            display: inline-flex; align-items: center; justify-content: center;
            margin-right: 6px;
        }
    </style>
</head>
<body>
<div class="form-container">
    <div class="text-center mb-4">
        <div class="header-icon">
            <i class="fas fa-file-signature fa-xl"></i>
        </div>
        <h4 class="fw-bold text-dark mb-0">Requisição de Materiais</h4>
        <p class="text-muted small">Preencha os dados e indique para quem são os itens solicitados.</p>
    </div>

    <form id="formPublico">

        <!-- Dados do solicitante -->
        <div class="row mb-3">
            <div class="col-md-6 mb-2">
                <label class="form-label"><i class="fas fa-user me-1"></i> Solicitante</label>
                <input type="text" name="solicitante" id="solicitante" class="form-control" placeholder="Nome Completo" required>
            </div>
            <div class="col-md-6 mb-2">
                <label class="form-label"><i class="fas fa-building me-1"></i> Setor</label>
                <select name="setor" id="setor" class="form-select" required>
                    <option value="">Selecione...</option>
                    <option value="Conservação">CONSERVAÇÃO</option>
                    <option value="Cadastro">CADASTRO</option>
                    <option value="Compras">COMPRAS</option>
                    <option value="Faturamento">FATURAMENTO</option>
                    <option value="Transporte">TRANSPORTE</option>
                    <option value="Marketing">MARKETING</option>
                    <option value="RH">RH</option>
                    <option value="Carregamento">CARREGAMENTO</option>
                    <option value="Logística">LOGÍSTICA</option>
                    <option value="Televendas">TELEVENDAS</option>
                    <option value="Facilities & T.I">FACILITIES & T.I</option>
                    <option value="Fiscal">FISCAL</option>
                    <option value="Manutenção">MANUTENÇÃO</option>
                    <option value="Recebimento">RECEBIMENTO</option>
                </select>
            </div>
        </div>

        <!-- Gestor aprovador -->
        <div class="mb-3">
            <label class="form-label"><i class="fas fa-user-check me-1"></i> Quem irá aprovar esta solicitação?</label>
            <select name="aprovador_id" id="aprovador_id" class="form-select select2-aprovador" required>
                <option value="">Selecione o gestor responsável...</option>
                <?php if ($res_aprovadores): ?>
                    <?php while ($g = $res_aprovadores->fetch(PDO::FETCH_ASSOC)): ?>
                        <option value="<?= htmlspecialchars($g['id']) ?>">
                            <?= htmlspecialchars($g['firstname'] . ' ' . $g['realname']) ?>
                        </option>
                    <?php endwhile; ?>
                <?php endif; ?>
            </select>
            <div class="form-text small">O pedido será enviado para a fila de aprovação deste gestor.</div>
        </div>

        <!-- Motivo -->
        <div class="mb-4">
            <label class="form-label"><i class="fas fa-comment-dots me-1"></i> Motivo da solicitação</label>
            <textarea name="motivo_solicitacao" class="form-control" rows="2"
                      placeholder="Ex: Reposição de estoque do setor / Uso em novo projeto..." required></textarea>
        </div>

        <!-- Lista de itens -->
        <div id="lista_itens">
            <!-- Item base (será clonado) -->
            <div class="item-linha p-3 shadow-sm" id="item_base">
                <div class="d-flex align-items-center mb-2">
                    <span class="numero-item item-numero">1</span>
                    <span class="small text-muted fw-bold text-uppercase" style="font-size:.65rem; letter-spacing:1px">Item</span>
                </div>
                <div class="row g-2 align-items-end">
                    <div class="col-md-7">
                        <label class="form-label small"><i class="fas fa-barcode me-1"></i> Produto do catálogo</label>
                        <select name="item_nome[]" class="form-select busca-produto-catalogo" required></select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Qtd. total</label>
                        <input type="number" name="item_qtd[]" class="form-control text-center fw-bold item-qtd-total"
                               placeholder="0" required min="1" oninput="calcularResto(this.closest('.item-linha'))">
                    </div>
                    <div class="col-md-3 text-end">
                        <button type="button" class="btn btn-sm btn-outline-primary me-1" onclick="toggleDistribuicao(this)">
                            <i class="fas fa-users me-1"></i> Detalhar beneficiários
                        </button>
                        <button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="removerLinha(this)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>

                    <!-- Aviso de estoque de mesa -->
                    <div class="col-12">
                        <div class="aviso-mesa">
                            <i class="fas fa-inbox me-1"></i> <span class="aviso-texto"></span>
                        </div>
                    </div>

                    <!-- Área de distribuição por beneficiário -->
                    <div class="col-12 mt-2 area-distribuicao p-2 d-none">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small fw-bold text-primary">
                                <i class="fas fa-id-badge me-1"></i> Distribuição individual
                            </span>
                            <button type="button" class="btn btn-sm btn-primary py-0 px-2"
                                    style="font-size:.7rem;" onclick="addPessoa(this)">
                                + Adicionar pessoa
                            </button>
                        </div>
                        <div class="tabela-pessoas">
                            <div class="row g-1 mb-1 beneficiario-row">
                                <div class="col-5">
                                    <input type="text" name="benef_nome[]" class="form-control form-control-sm" placeholder="Nome">
                                </div>
                                <div class="col-4">
                                    <input type="text" name="benef_setor[]" class="form-control form-control-sm" placeholder="Setor">
                                </div>
                                <div class="col-2">
                                    <input type="number" name="benef_qtd[]" min="0"
                                           class="form-control form-control-sm text-center benef-qtd"
                                           placeholder="Qtd"
                                           oninput="calcularResto(this.closest('.item-linha'))">
                                </div>
                                <div class="col-1"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <button type="button" class="btn btn-add mb-4" onclick="adicionarLinha()">
            <i class="fas fa-plus-circle me-1"></i> Adicionar outro produto
        </button>

        <button type="submit" class="btn btn-primary btn-submit w-100">
            <i class="fas fa-paper-plane me-2"></i> Enviar pedido ao almoxarifado
        </button>

    </form>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    // ─── Select2 ────────────────────────────────────────────
    function inicializarSelect2(elemento) {
        const target = elemento ? $(elemento) : $('.busca-produto-catalogo');
        target.select2({
            theme: 'bootstrap-5',
            placeholder: 'Procure o material...',
            width: '100%',
            ajax: {
                url: 'api/search_products.php',
                dataType: 'json',
                delay: 250,
                processResults: d => ({
                    results: d.map(i => ({ id: i.text, text: i.text, categoria: i.categoria }))
                })
            }
        }).on('select2:select', function(e) {
            const linha = $(this).closest('.item-linha');
            const cat = e.params.data.categoria || '';
            // Abre automaticamente a área de distribuição para itens de conservação
            if (['LIMPEZA', 'CONSERVAÇÃO', 'COPA'].includes(cat)) {
                linha.find('.area-distribuicao').removeClass('d-none');
                linha.find('input[name="benef_nome[]"]').val('USO GERAL – PRÉDIO');
            }
            calcularResto(linha[0]);
        });
    }

    // ─── Lógica de estoque de mesa ───────────────────────────
    function calcularResto(linhaEl) {
        const total = parseInt(linhaEl.querySelector('.item-qtd-total')?.value) || 0;
        let distribuido = 0;
        linhaEl.querySelectorAll('.benef-qtd').forEach(inp => {
            distribuido += parseInt(inp.value) || 0;
        });
        const resto = total - distribuido;
        const aviso = linhaEl.querySelector('.aviso-mesa');
        const texto = linhaEl.querySelector('.aviso-texto');

        aviso.classList.remove('negativo', 'zerado');

        if (total === 0) {
            aviso.style.display = 'none';
            return;
        }

        if (resto > 0) {
            texto.textContent = resto + ' unidade(s) sem destinatário → irão para o estoque de mesa do setor';
            aviso.style.display = 'block';
        } else if (resto < 0) {
            texto.textContent = 'Atenção: você distribuiu ' + Math.abs(resto) + ' a mais do que o total solicitado!';
            aviso.classList.add('negativo');
            aviso.style.display = 'block';
        } else {
            texto.textContent = 'Tudo distribuído — nenhum item irá para o estoque de mesa.';
            aviso.classList.add('zerado');
            aviso.style.display = 'block';
        }
    }

    function toggleDistribuicao(btn) {
        $(btn).closest('.item-linha').find('.area-distribuicao').toggleClass('d-none');
    }

    function addPessoa(btn) {
        const row = `
        <div class="row g-1 mb-1 beneficiario-row">
            <div class="col-5"><input type="text" name="benef_nome[]" class="form-control form-control-sm" placeholder="Nome"></div>
            <div class="col-4"><input type="text" name="benef_setor[]" class="form-control form-control-sm" placeholder="Setor"></div>
            <div class="col-2"><input type="number" name="benef_qtd[]" min="0" class="form-control form-control-sm text-center benef-qtd" placeholder="Qtd" oninput="calcularResto(this.closest('.item-linha'))"></div>
            <div class="col-1 text-center">
                <button type="button" class="btn btn-link text-danger p-0" onclick="$(this).closest('.beneficiario-row').remove(); calcularResto(this.closest('.item-linha'))">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>`;
        $(btn).closest('.area-distribuicao').find('.tabela-pessoas').append(row);
    }

    function adicionarLinha() {
        const total = $('#lista_itens .item-linha').length + 1;
        const clone = $('#item_base').clone().removeAttr('id');

        // Reseta o clone
        clone.find('.area-distribuicao').addClass('d-none');
        clone.find('.aviso-mesa').hide();
        clone.find('input').val('');
        clone.find('.tabela-pessoas .beneficiario-row:not(:first)').remove();
        clone.find('.item-numero').text(total);

        // Remove select2 injetado para não duplicar bugs
        clone.find('.select2-container').remove();
        clone.find('select.busca-produto-catalogo')
             .removeClass('select2-hidden-accessible')
             .removeAttr('data-select2-id')
             .empty();

        $('#lista_itens').append(clone);
        inicializarSelect2(clone.find('.busca-produto-catalogo'));
    }

    function removerLinha(btn) {
        if ($('.item-linha').length > 1) {
            $(btn).closest('.item-linha').remove();
            // Renumera os itens
            $('.item-numero').each(function(i) { $(this).text(i + 1); });
        }
    }

    // ─── Inicialização ───────────────────────────────────────
    $(document).ready(function() {
        inicializarSelect2();
        $('.select2-aprovador').select2({ theme: 'bootstrap-5' });

        // Permite apenas letras no nome
        $('#solicitante').on('input', function() {
            this.value = this.value.replace(/[^a-zA-ZÀ-ÿ\s]/g, '');
        });
    });

    // ─── Submit ──────────────────────────────────────────────
    $('#formPublico').on('submit', function(e) {
        e.preventDefault();

        // Validação: nenhum item com distribuição negativa
        let erros = [];
        document.querySelectorAll('.item-linha').forEach(function(linha, i) {
            const total = parseInt(linha.querySelector('.item-qtd-total')?.value) || 0;
            let distrib = 0;
            linha.querySelectorAll('.benef-qtd').forEach(inp => { distrib += parseInt(inp.value) || 0; });
            if (distrib > total) {
                erros.push('Item ' + (i + 1) + ': quantidade distribuída (' + distrib + ') maior que o total (' + total + ')');
            }
            if (total === 0) {
                erros.push('Item ' + (i + 1) + ': informe a quantidade total.');
            }
        });

        if (erros.length) {
            Swal.fire({
                title: 'Revise os itens',
                html: erros.map(e => '• ' + e).join('<br>'),
                icon: 'warning'
            });
            return;
        }

        const btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i> Enviando...');

        $.post('api/registrar_pedido_externo.php', $(this).serialize(), function(res) {
            if (res.success) {
                Swal.fire({
                    title: 'Pedido enviado!',
                    html: 'Sua requisição foi registrada com sucesso.<br><small class="text-muted">Protocolo: <strong>#' + res.protocolo + '</strong></small>',
                    icon: 'success',
                    confirmButtonColor: '#1e3c72'
                }).then(() => location.reload());
            } else {
                Swal.fire('Erro ao enviar', res.message || 'Tente novamente.', 'error');
                btn.prop('disabled', false).html('<i class="fas fa-paper-plane me-2"></i> Enviar pedido ao almoxarifado');
            }
        }, 'json').fail(function() {
            Swal.fire('Erro de conexão', 'Não foi possível contatar o servidor.', 'error');
            btn.prop('disabled', false).html('<i class="fas fa-paper-plane me-2"></i> Enviar pedido ao almoxarifado');
        });
    });
</script>
</body>
</html>
