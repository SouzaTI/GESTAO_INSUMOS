<?php
require_once __DIR__ . '/config/db.php';
if (!isset($_SESSION['usuario_id'])) { header("Location: login.php"); exit(); }
$nome_usuario = $_SESSION['usuario_nome'];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Central de Relatórios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root { --sidebar-width: 260px; --primary-color: #254c90; }
        body { background-color: #f4f7f6; font-family: 'Segoe UI', sans-serif; margin: 0; }
        .sidebar { width: var(--sidebar-width); height: 100vh; background: var(--primary-color); color: white; position: fixed; left: 0; top: 0; display: flex; flex-direction: column; box-shadow: 4px 0 10px rgba(0,0,0,0.1); z-index: 1000; }
        .sidebar-header { padding: 25px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .nav-link { color: rgba(255,255,255,0.7); padding: 15px 25px; font-size: 0.95rem; display: flex; align-items: center; transition: 0.3s; text-decoration: none; }
        .nav-link:hover, .nav-link.active { color: white; background: rgba(255,255,255,0.1); }
        .main-content { margin-left: var(--sidebar-width); padding: 40px; min-height: 100vh; }
        .card { border: none; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .mode-card { cursor: pointer; transition: 0.3s; border: 2px solid transparent; }
        .mode-card:hover { transform: scale(1.02); }
        .mode-card.active { border-color: var(--primary-color); background-color: #eef2f7 !important; }
        .section-header { border-left: 4px solid var(--primary-color); padding-left: 10px; font-weight: bold; margin-bottom: 15px; color: var(--primary-color); }
        
        /* Ajuste do Campo de Busca Larga */
        .select2-container { width: 100% !important; flex: 1 1 auto; }
        .select2-container--default .select2-selection--single { height: 45px !important; border: 1px solid #dee2e6 !important; border-radius: 8px 0 0 8px !important; padding-top: 8px !important; }
        .input-group .btn-plus { height: 45px; border-radius: 0 8px 8px 0 !important; width: 50px; }
    </style>
</head>
<body>

<div class="main-content">
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card mode-card p-4 text-center shadow-sm" onclick="setModo('compra')">
                <i class="fas fa-shopping-cart fa-2x mb-2 text-primary"></i>
                <h5 class="fw-bold mb-0">Solicitação de Compra</h5>
                <small class="text-muted">Interno vs Externo</small>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mode-card p-4 text-center shadow-sm" onclick="setModo('estoque')">
                <i class="fas fa-warehouse fa-2x mb-2 text-danger"></i>
                <h5 class="fw-bold mb-0">Gestão de Estoque</h5>
                <small class="text-muted">Entradas e Avarias</small>
            </div>
        </div>
    </div>

    <div id="container_formulario" class="d-none">
        <div class="card p-4 mb-4 bg-white border shadow-sm">
            <h5 class="fw-bold mb-3"><i class="fas fa-plus-circle me-2 text-success"></i>Adicionar Item ao Carrinho</h5>
            <div class="row g-3 align-items-end">
                <div class="col-md-12">
                    <label class="form-label fw-bold small">Produto</label>
                    <div class="input-group">
                        <select id="busca_produto" class="form-control"></select>
                        <button class="btn btn-outline-secondary btn-plus" type="button" data-bs-toggle="modal" data-bs-target="#modalNovoProduto">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
                </div>
        </div>

        </div>
</div>

<div class="modal fade" id="modalNovoProduto" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form id="form_novo_produto" class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold">Cadastrar Novo Produto/Insumo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-4"><label class="form-label fw-bold">Ref.</label><input type="text" name="ref" class="form-control" required></div>
                    <div class="col-md-8"><label class="form-label fw-bold">Descrição Completa</label><input type="text" name="desc" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label fw-bold">Código de Barras (EAN)</label><input type="text" name="ean" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label fw-bold">Categoria</label>
                        <select name="cat" class="form-select">
                            <option value="INSUMOS">INSUMOS</option>
                            <option value="ALIMENTOS">ALIMENTOS</option>
                            <option value="LIMPEZA">LIMPEZA</option>
                        </select>
                    </div>
                    <div class="col-md-4"><label class="form-label fw-bold">Unidade (UN/CX/FD)</label><input type="text" name="unid" class="form-control" required></div>
                    <div class="col-md-4"><label class="form-label fw-bold">Preço Custo (R$)</label><input type="number" step="0.01" name="custo" class="form-control" required></div>
                    <div class="col-md-4"><label class="form-label fw-bold">Fornecedor Padrão</label><input type="text" name="fornecedor" class="form-control"></div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-success px-4 fw-bold">Salvar e Adicionar à Busca</button>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    // Toda a sua lógica de Carrinho e setModo() continua aqui intacta
    
    // Lógica para salvar novo produto via AJAX e já selecionar ele na busca
    $('#form_novo_produto').on('submit', function(e) {
        e.preventDefault();
        // Aqui você faria o $.post para sua API de produtos
        // No sucesso, você fecha o modal e coloca o novo item no Select2
        alert('Produto cadastrado com sucesso e pronto para uso!');
        $('#modalNovoProduto').modal('hide');
    });
</script>
</body>
</html>