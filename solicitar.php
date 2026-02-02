<?php require_once __DIR__ . '/config/db.php'; ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Requisição de Materiais - Gestão Digital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Fundo com Degradê Azul Profissional */
        body { 
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); 
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', sans-serif;
            padding: 20px;
        }

        /* Container Flutuante com efeito Glassmorphism */
        .form-container { 
            max-width: 650px; 
            width: 100%;
            background: rgba(255, 255, 255, 0.98); 
            padding: 40px; 
            border-radius: 20px; 
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }

        .header-icon {
            width: 70px;
            height: 70px;
            background: #e7f0ff;
            color: #1e3c72;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
        }

        /* Estilização das linhas de itens */
        .item-linha { 
            background: #f8f9fc; 
            padding: 12px; 
            border-radius: 10px; 
            margin-bottom: 10px; 
            border: 1px solid #e3e6f0;
        }

        .form-label { color: #1e3c72; font-weight: 700; font-size: 0.8rem; }
        
        .btn-add {
            color: #1e3c72;
            border: 2px dashed #d1d3e2;
            width: 100%;
            border-radius: 10px;
            padding: 8px;
            transition: all 0.3s;
        }

        .btn-add:hover { background: #f0f4ff; border-color: #1e3c72; }

        .btn-submit {
            background: #1e3c72;
            border: none;
            padding: 12px;
            font-weight: bold;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(30, 60, 114, 0.3);
        }
    </style>
</head>
<body>

    <div class="form-container">
        <div class="text-center mb-4">
            <div class="header-icon">
                <i class="fas fa-file-signature fa-2x"></i>
            </div>
            <h3 class="fw-bold text-dark mb-1">REQUISIÇÃO DIGITAL</h3>
            <p class="text-muted small">Central de Solicitação de Insumos</p>
        </div>

        <form id="formPublico">
            <div class="row mb-4">
                <div class="col-md-6">
                    <label class="form-label fw-bold"><i class="fas fa-user me-1"></i> SOLICITANTE</label>
                    <input type="text" name="solicitante" class="form-control" placeholder="Seu nome completo" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold"><i class="fas fa-building me-1"></i> SETOR</label>
                    <select name="setor" class="form-select" required>
                        <option value="">Selecione...</option>
                        <option value="Conservação">Limpeza / Conservação</option>
                        <option value="Operacional">Operacional / Frota</option>
                        <option value="Administrativo">Administrativo</option>
                        <option value="TI">T.I / Suporte</option>
                    </select>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-2">
                <label class="form-label fw-bold m-0"><i class="fas fa-box-open me-1"></i> ITENS DA REQUISIÇÃO</label>
            </div>
            
            <div id="lista_itens">
                <div class="row g-2 item-linha align-items-center">
                    <div class="col-8">
                        <input type="text" name="item_nome[]" class="form-control border-0 shadow-none" placeholder="O que você precisa?" required>
                    </div>
                    <div class="col-3">
                        <input type="number" name="item_qtd[]" class="form-control border-0 shadow-none text-center" placeholder="Qtd" required>
                    </div>
                    <div class="col-1 text-end">
                        <button type="button" class="btn btn-link text-danger p-0" onclick="removerLinha(this)"><i class="fas fa-trash-alt"></i></button>
                    </div>
                </div>
            </div>

            <button type="button" class="btn btn-add" onclick="adicionarLinha()">
                <i class="fas fa-plus-circle me-1"></i> Adicionar outro produto
            </button>

            <button type="submit" class="btn btn-primary btn-submit w-100">
                <i class="fas fa-paper-plane me-2"></i> ENVIAR PEDIDO AO COMPRADOR
            </button>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function adicionarLinha() {
            const html = `<div class="row g-2 item-linha align-items-center animate__animated animate__fadeInUp">
                <div class="col-8"><input type="text" name="item_nome[]" class="form-control border-0 shadow-none" placeholder="O que você precisa?" required></div>
                <div class="col-3"><input type="number" name="item_qtd[]" class="form-control border-0 shadow-none text-center" placeholder="Qtd" required></div>
                <div class="col-1 text-end"><button type="button" class="btn btn-link text-danger p-0" onclick="removerLinha(this)"><i class="fas fa-trash-alt"></i></button></div>
            </div>`;
            $('#lista_itens').append(html);
        }
        
        function removerLinha(btn) { 
            if($('.item-linha').length > 1) $(btn).closest('.item-linha').fadeOut(300, function(){ $(this).remove(); }); 
        }

        $('#formPublico').on('submit', function(e) {
            e.preventDefault();
            const btn = $(this).find('button[type="submit"]');
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> ENVIANDO...');

            $.post('api/registrar_pedido_externo.php', $(this).serialize(), function(res) {
                if(res.success) {
                    Swal.fire({
                        title: 'Enviado com Sucesso!',
                        text: 'O comprador já recebeu sua solicitação.',
                        icon: 'success',
                        confirmButtonColor: '#4e73df'
                    }).then(() => location.reload());
                } else {
                    Swal.fire('Atenção', res.message, 'warning');
                    btn.prop('disabled', false).html('<i class="fas fa-paper-plane me-2"></i> ENVIAR PEDIDO');
                }
            }, 'json');
        });
    </script>
</body>
</html>