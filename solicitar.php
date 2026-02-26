<?php 
// 1. Conexão com o banco local do sistema
require_once __DIR__ . '/config/db.php'; 

// 2. Conexão PDO com o GLPI (Essencial para buscar os aprovadores)
try {
    $glpi_pdo = new PDO("mysql:host=127.0.0.1;port=3307;dbname=glpidb_att;charset=utf8mb4", 'root', '');
    $glpi_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro ao conectar no banco do GLPI: " . $e->getMessage());
}

// 3. Agora a consulta funcionará sem o erro de "null"
// SQL filtrado apenas para os IDs dos gestores autorizados
$query_aprovadores = "SELECT id, realname, firstname 
                      FROM glpi_users 
                      WHERE id IN (17, 19, 23, 40) 
                      ORDER BY firstname ASC";
$res_aprovadores = $glpi_pdo->query($query_aprovadores);

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Requisição de Materiais - Gestão Digital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />

    <style>
        body { 
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); 
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', sans-serif;
            padding: 20px;
        }

        .form-container { 
            max-width: 900px; 
            width: 100%;
            background: rgba(255, 255, 255, 0.98); 
            padding: 30px; 
            border-radius: 20px; 
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }

        .header-icon {
            width: 60px;
            height: 60px;
            background: #e7f0ff;
            color: #1e3c72;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
        }

        .item-linha { 
            background: #f8f9fc; 
            border-radius: 12px; 
            margin-bottom: 20px; 
            border: 1px solid #e3e6f0;
            border-left: 5px solid #1e3c72;
        }

        .form-label { color: #1e3c72; font-weight: 700; font-size: 0.75rem; text-transform: uppercase; }
        
        .btn-add {
            color: #1e3c72;
            border: 2px dashed #d1d3e2;
            width: 100%;
            border-radius: 10px;
            padding: 10px;
            font-weight: 600;
        }

        .area-distribuicao {
            background-color: #ffffff;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }

        .beneficiario-row input {
            font-size: 0.85rem;
            border: 1px solid #ced4da;
        }

        .btn-submit {
            background: #1e3c72;
            border: none;
            padding: 15px;
            font-weight: bold;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(30, 60, 114, 0.4);
        }
    </style>
</head>
<body>

    <div class="form-container">
        <div class="text-center mb-4">
            <div class="header-icon">
                <i class="fas fa-file-signature fa-xl"></i>
            </div>
            <h4 class="fw-bold text-dark mb-0">REQUISIÇÃO DIGITAL</h4>
            <p class="text-muted small">Detalhamento Individual de Consumo</p>
        </div>

        <form id="formPublico">
            <div class="row mb-4">
                <div class="col-md-6 mb-2">
                    <label class="form-label"><i class="fas fa-user me-1"></i> Solicitante</label>
                    <input type="text" name="solicitante" id="solicitante" class="form-control" placeholder="Nome Completo" required>
                </div>
                <div class="col-md-6 mb-2">
                    <label class="form-label"><i class="fas fa-building me-1"></i> Setor</label>
                    <select name="setor" class="form-select" required>
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

            <div class="col-md-12 mt-3 animate__animated animate__fadeIn">
                <label class="form-label fw-bold text-primary">
                    <i class="fas fa-user-check me-1"></i> QUEM IRÁ APROVAR ESTA SOLICITAÇÃO?
                </label>
                <select name="aprovador_id" id="aprovador_id" class="form-select select2-aprovador shadow-sm" required>
                    <option value="">Selecione o Gestor Responsável...</option>
                    <?php while($gestor = $res_aprovadores->fetch(PDO::FETCH_ASSOC)): ?>
                        <option value="<?php echo $gestor['id']; ?>">
                            <?php echo $gestor['firstname'] . " " . $gestor['realname']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <div class="form-text small">O pedido será enviado para a fila de aprovação deste gestor.</div>
            </div>

            <div class="col-md-12 mt-3 animate__animated animate__fadeIn">
                <label class="form-label fw-bold text-primary">
                    <i class="fas fa-comment-dots me-1"></i> MOTIVO DA SOLICITAÇÃO
                </label>
                <textarea name="motivo_solicitacao" class="form-control shadow-sm" rows="2" 
                        placeholder="Ex: Reposição de estoque do setor / Uso em novo projeto..." required></textarea>
            </div>

            <div id="lista_itens">
                <div class="item-linha p-3 shadow-sm animate__animated animate__fadeIn" id="item_base">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-7">
                            <label class="form-label small">Produto do Catálogo</label>
                            <select name="item_nome[]" class="form-select busca-produto-catalogo" required></select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Qtd Total</label>
                            <input type="number" name="item_qtd[]" class="form-control text-center fw-bold" placeholder="0" required min="1">
                        </div>
                        <div class="col-md-3 text-end">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="toggleDistribuicao(this)">
                                <i class="fas fa-users me-1"></i> Detalhar Beneficiários
                            </button>
                            <button type="button" class="btn btn-link text-danger p-0 ms-1" onclick="removerLinha(this)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>

                        <div class="col-12 mt-3 area-distribuicao p-2 d-none">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="small fw-bold text-primary"><i class="fas fa-id-badge me-1"></i> DISTRIBUIÇÃO INDIVIDUAL</span>
                                <button type="button" class="btn btn-xs btn-primary py-0 px-2" style="font-size: 0.7rem;" onclick="addPessoa(this)">+ Adicionar Pessoa</button>
                            </div>
                            <div class="tabela-pessoas">
                                <div class="row g-1 mb-1 beneficiario-row">
                                    <div class="col-5"><input type="text" name="benef_nome[]" class="form-control form-control-sm" placeholder="Nome"></div>
                                    <div class="col-4"><input type="text" name="benef_setor[]" class="form-control form-control-sm" placeholder="setor"></div>
                                    <div class="col-2"><input type="number" name="benef_qtd[]" class="form-control form-control-sm text-center" placeholder="Qtd"></div>
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
                <i class="fas fa-paper-plane me-2"></i> ENVIAR PEDIDO AO ALMOXARIFADO
            </button>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        function inicializarSelect2(elemento) {
            const target = elemento ? $(elemento) : $('.busca-produto-catalogo');
            target.select2({
                theme: 'bootstrap-5',
                placeholder: 'Procure o material...',
                ajax: {
                    url: 'api/search_products.php',
                    dataType: 'json',
                    processResults: d => ({ results: d.map(i => ({ id: i.text, text: i.text, categoria: i.categoria })) })
                }
            }).on('select2:select', function(e) {
                const data = e.params.data;
                const linha = $(this).closest('.item-linha');
                // Auto-preenchimento para categorias de uso geral
                if (['LIMPEZA', 'CONSERVAÇÃO', 'COPA'].includes(data.categoria)) {
                    linha.find('.area-distribuicao').removeClass('d-none');
                    linha.find('input[name="benef_nome[]"]').val('USO GERAL - PRÉDIO');
                }
            });
        }

        function toggleDistribuicao(btn) {
            $(btn).closest('.item-linha').find('.area-distribuicao').toggleClass('d-none');
        }

        function addPessoa(btn) {
            const row = `
            <div class="row g-1 mb-1 beneficiario-row animate__animated animate__fadeInLeft">
                <div class="col-5"><input type="text" name="benef_nome[]" class="form-control form-control-sm" placeholder="Nome"></div>
                <div class="col-4"><input type="text" name="benef_setor[]" class="form-control form-control-sm" placeholder="setor"></div>
                <div class="col-2"><input type="number" name="benef_qtd[]" class="form-control form-control-sm text-center" placeholder="Qtd"></div>
                <div class="col-1 text-center"><button type="button" class="btn btn-link text-danger p-0" onclick="$(this).closest('.beneficiario-row').remove()"><i class="fas fa-times"></i></button></div>
            </div>`;
            $(btn).closest('.area-distribuicao').find('.tabela-pessoas').append(row);
        }

        function adicionarLinha() {
            const clone = $('#item_base').clone().removeAttr('id').addClass('animate__animated animate__fadeInUp');
            clone.find('.area-distribuicao').addClass('d-none');
            clone.find('input').val('');
            clone.find('.tabela-pessoas .beneficiario-row:not(:first)').remove();
            clone.find('.select2-container').remove(); // Remove select2 velho para reinicializar
            $('#lista_itens').append(clone);
            inicializarSelect2(clone.find('.busca-produto-catalogo'));
        }

        function removerLinha(btn) { 
            if($('.item-linha').length > 1) $(btn).closest('.item-linha').remove(); 
        }

        $(document).ready(function() {
            inicializarSelect2();
            $('#solicitante').on('input', function() { this.value = this.value.replace(/[^a-zA-ZÀ-ÿ\s]/g, ""); });
        });

        $('#formPublico').on('submit', function(e) {
            e.preventDefault();
            const btn = $(this).find('button[type="submit"]');
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> ENVIANDO...');
            
            $.post('api/registrar_pedido_externo.php', $(this).serialize(), function(res) {
                if(res.success) {
                    Swal.fire({ title: 'Enviado!', text: 'Pedido registrado com sucesso.', icon: 'success' }).then(() => location.reload());
                } else {
                    Swal.fire('Erro', res.message, 'error');
                    btn.prop('disabled', false).html('ENVIAR PEDIDO');
                }
            }, 'json');
        });
    </script>
</body>
</html>