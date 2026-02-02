<?php
// produtos.php
require_once __DIR__ . '/config/db.php';

if (!isset($_SESSION['usuario_id'])) { header("Location: login.php"); exit(); }

$nome_usuario = $_SESSION['usuario_nome'];
$nivel_usuario = $_SESSION['usuario_nivel'] ?? 'user';

// --- 1. CONFIGURAÇÃO DE PAGINAÇÃO E FILTROS ---
$itens_por_pagina = 15;
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

$busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';
$categoria_filtro = isset($_GET['categoria']) ? trim($_GET['categoria']) : '';

$condicoes = [];
$params = [];
$types = "";

if (!empty($busca)) {
    $condicoes[] = "(descricao LIKE ? OR codigo_referencia LIKE ?)";
    $search_term = "%$busca%";
    $params = array_merge($params, [$search_term, $search_term]);
    $types .= "ss";
}

if (!empty($categoria_filtro)) {
    $condicoes[] = "categoria = ?";
    $params[] = $categoria_filtro;
    $types .= "s";
}

$where_sql = !empty($condicoes) ? "WHERE " . implode(" AND ", $condicoes) : "";

// Contar total
$sql_count = "SELECT COUNT(*) as total FROM produtos $where_sql";
$stmt_count = $conn->prepare($sql_count);
if (!empty($params)) $stmt_count->bind_param($types, ...$params);
$stmt_count->execute();
$total_registros = $stmt_count->get_result()->fetch_assoc()['total'];
$total_paginas = ceil($total_registros / $itens_por_pagina);

// --- 2. BUSCA DOS PRODUTOS ---
$sql_lista = "SELECT p.*, 
              (SELECT SUM(quantidade_atual) FROM lotes WHERE produto_id = p.id) as estoque_total,
              (SELECT MIN(data_validade) FROM lotes WHERE produto_id = p.id AND quantidade_atual > 0 AND data_validade >= CURDATE()) as validade_proxima
              FROM produtos p 
              $where_sql 
              ORDER BY p.descricao ASC 
              LIMIT ?, ?";
$stmt_lista = $conn->prepare($sql_lista);
$final_params = array_merge($params, [$offset, $itens_por_pagina]);
$final_types = $types . "ii";
$stmt_lista->bind_param($final_types, ...$final_params);
$stmt_lista->execute();
$lista_produtos = $stmt_lista->get_result();

// --- 3. LÓGICA DE CADASTRO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_produto'])) {
    $conn->begin_transaction();
    try {
        $sql = "INSERT INTO produtos (codigo_referencia, descricao, unidade_medida, tipo_produto, categoria, preco_custo, estoque_minimo) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssdi", $_POST['referencia'], $_POST['descricao'], $_POST['unidade'], $_POST['tipo'], $_POST['categoria'], $_POST['preco_custo'], $_POST['minimo']);
        $stmt->execute();
        $p_id = $conn->insert_id;

        if (!empty($_POST['qtd_inicial'])) {
            $sql_lote = "INSERT INTO lotes (produto_id, numero_lote, data_validade, quantidade_inicial, quantidade_atual) VALUES (?, 'INICIAL', ?, ?, ?)";
            $stmt_lote = $conn->prepare($sql_lote);
            $val = !empty($_POST['validade']) ? $_POST['validade'] : '2099-12-31';
            $stmt_lote->bind_param("isdd", $p_id, $val, $_POST['qtd_inicial'], $_POST['qtd_inicial']);
            $stmt_lote->execute();
        }
        $conn->commit();
        header("Location: produtos.php?msg=sucesso"); exit();
    } catch (Exception $e) { $conn->rollback(); }
}

// --- 4. LÓGICA DE EDIÇÃO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_produto'])) {
    $conn->begin_transaction();
    try {
        $id = (int)$_POST['produto_id'];
        $sql = "UPDATE produtos SET codigo_referencia=?, descricao=?, unidade_medida=?, tipo_produto=?, categoria=?, preco_custo=?, estoque_minimo=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssdii", $_POST['referencia'], $_POST['descricao'], $_POST['unidade'], $_POST['tipo'], $_POST['categoria'], $_POST['preco_custo'], $_POST['minimo'], $id);
        $stmt->execute();

        if (isset($_POST['qtd_ajuste']) && $_POST['qtd_ajuste'] != 0) {
            $qtd = (float)$_POST['qtd_ajuste'];
            $val = !empty($_POST['validade_ajuste']) ? $_POST['validade_ajuste'] : '2099-12-31';
            $sql_lote = "INSERT INTO lotes (produto_id, numero_lote, data_validade, quantidade_inicial, quantidade_atual) VALUES (?, 'AJUSTE', ?, ?, ?)";
            $stmt_lote = $conn->prepare($sql_lote);
            $stmt_lote->bind_param("isdd", $id, $val, $qtd, $qtd);
            $stmt_lote->execute();
            
            // Log Universal
            $log_desc = ($qtd > 0 ? "Adicionou " : "Removeu ") . abs($qtd) . " un. Validade: $val";
            $sql_log = "INSERT INTO logs_sistema (usuario_id, usuario_nome, tabela_afetada, registro_id, acao, descricao_log) VALUES (?, ?, 'lotes', ?, 'AJUSTE_ESTOQUE', ?)";
            $st_log = $conn->prepare($sql_log);
            $st_log->bind_param("isis", $_SESSION['usuario_id'], $_SESSION['usuario_nome'], $id, $log_desc);
            $st_log->execute();
        }
        $conn->commit();
        header("Location: produtos.php?msg=editado"); exit();
    } catch (Exception $e) { $conn->rollback(); }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['excluir_produto'])) {
    $id = (int)$_POST['produto_id'];
    $conn->query("DELETE FROM produtos WHERE id = $id");
    header("Location: produtos.php?msg=excluido"); exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Central de Produtos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
    <header class="mb-4 d-flex justify-content-between align-items-center">
        <div>
            <h2 class="fw-bold text-dark mb-1">Gestão de Insumos</h2>
            <p class="text-muted mb-0">Gerenciando <?= number_format($total_registros, 0, ',', '.') ?> itens de Uso e Consumo.</p>
        </div>
        <button type="button" class="btn btn-primary shadow-sm px-4 py-2" data-bs-toggle="modal" data-bs-target="#modalNovo">
            <i class="fas fa-plus me-2"></i> Novo Item
        </button>
    </header>

    <div class="card p-3 mb-4">
        <form method="GET" class="row g-2">
            <div class="col-md-5"><input type="text" name="busca" class="form-control" placeholder="Código ou Descrição..." value="<?= htmlspecialchars($busca) ?>"></div>
            <div class="col-md-4">
                <select name="categoria" class="form-select">
                    <option value="">Todas as Categorias</option>
                    <option value="OPERACIONAL">OPERACIONAL</option>
                    <option value="ESCRITÓRIO ADM">ESCRITÓRIO ADM</option>
                    <option value="CONSERVAÇÃO">CONSERVAÇÃO</option>
                    <option value="PERFUMARIA E HIGIENE PESSOAL">PERFUMARIA</option>
                    <option value="ALIMENTOS">ALIMENTOS</option>
                </select>
            </div>
            <div class="col-md-3"><button type="submit" class="btn btn-dark w-100"><i class="fas fa-filter me-2"></i>Filtrar</button></div>
        </form>
    </div>

    <div class="card overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Cód.</th>
                        <th>Descrição</th>
                        <th>Unid. / Tipo</th>
                        <th>Categoria</th>
                        <th class="text-center">Estoque</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($p = $lista_produtos->fetch_assoc()): ?>
                    <tr>
                        <td class="ps-4"><code><?= $p['codigo_referencia'] ?></code></td>
                        <td><strong><?= $p['descricao'] ?></strong></td>
                        <td><small class="text-muted d-block"><?= $p['tipo_produto'] ?></small><span class="badge bg-light text-dark border"><?= $p['unidade_medida'] ?></span></td>
                        <td><span class="badge bg-info-subtle text-info border"><?= $p['categoria'] ?></span></td>
                        <td class="text-center">
                            <?php $est = $p['estoque_total'] ?? 0; ?>
                            <span class="<?= ($est <= $p['estoque_minimo']) ? 'text-danger fw-bold' : '' ?>"><?= number_format($est, 0) ?></span>
                        </td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-primary px-3" onclick='abrirAcoes(<?= json_encode($p) ?>)'>
                                <i class="fas fa-cog"></i> Ações
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <nav class="mt-4">
        <ul class="pagination justify-content-center">
            <li class="page-item <?= ($pagina_atual <= 1) ? 'disabled' : '' ?>">
                <a class="page-link" href="?pagina=<?= $pagina_atual - 1 ?>&busca=<?= $busca ?>&categoria=<?= $categoria_filtro ?>">
                    <i class="fas fa-chevron-left"></i>
                </a>
            </li>

            <?php
            $janela = 2; // Quantos números mostrar antes e depois da página atual
            
            for ($i = 1; $i <= $total_paginas; $i++):
                // Lógica para mostrar: Primeira página, Última página, e páginas ao redor da Atual
                if ($i == 1 || $i == $total_paginas || ($i >= $pagina_atual - $janela && $i <= $pagina_atual + $janela)): ?>
                    <li class="page-item <?= ($i == $pagina_atual) ? 'active' : '' ?>">
                        <a class="page-link" href="?pagina=<?= $i ?>&busca=<?= $busca ?>&categoria=<?= $categoria_filtro ?>"><?= $i ?></a>
                    </li>
                <?php 
                // Adiciona reticências (...) se houver um salto grande entre os números
                elseif ($i == $pagina_atual - $janela - 1 || $i == $pagina_atual + $janela + 1): ?>
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                <?php endif; 
            endfor; ?>

            <li class="page-item <?= ($pagina_atual >= $total_paginas) ? 'disabled' : '' ?>">
                <a class="page-link" href="?pagina=<?= $pagina_atual + 1 ?>&busca=<?= $busca ?>&categoria=<?= $categoria_filtro ?>">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </li>
        </ul>
        <p class="text-center text-muted small mt-2">
            Página <?= $pagina_atual ?> de <?= $total_paginas ?>
        </p>
    </nav>
</div>

<div class="modal fade" id="modalNovo" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white"><h5 class="modal-title fw-bold">Cadastrar Insumo</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body p-4"><div class="row g-3">
                <div class="col-md-4"><label class="fw-bold small">Cód. Produto</label><input type="text" name="referencia" class="form-control" required></div>
                <div class="col-md-8"><label class="fw-bold small">Descrição</label><input type="text" name="descricao" class="form-control" required></div>
                <div class="col-md-4"><label class="fw-bold small">Unidade</label><input type="text" name="unidade" class="form-control" placeholder="UN, CX, FD..." required></div>
                <div class="col-md-4"><label class="fw-bold small">Tipo</label>
                    <select name="tipo" class="form-select">
                        <option value="USO E CONSUMO">USO E CONSUMO</option>
                        <option value="OPERACIONAL">OPERACIONAL</option>
                    </select>
                </div>
                <div class="col-md-4"><label class="fw-bold small">Categoria</label>
                    <select name="categoria" class="form-select">
                        <option value="OPERACIONAL">OPERACIONAL</option>
                        <option value="ESCRITÓRIO ADM">ESCRITÓRIO ADM</option>
                        <option value="CONSERVAÇÃO">CONSERVAÇÃO</option>
                        <option value="PERFUMARIA E HIGIENE PESSOAL">PERFUMARIA</option>
                        <option value="ALIMENTOS">ALIMENTOS</option>
                    </select>
                </div>
                <div class="col-md-6 bg-light p-3 rounded border">
                    <label class="fw-bold small text-primary">Estoque Inicial</label>
                    <input type="number" name="qtd_inicial" class="form-control" placeholder="Quantidade">
                </div>
                <div class="col-md-6 bg-light p-3 rounded border">
                    <label class="fw-bold small text-primary">Validade</label>
                    <input type="date" name="validade" class="form-control">
                </div>
                <div class="col-md-6"><label class="fw-bold small">Estoque Mínimo</label><input type="number" name="minimo" class="form-control" value="5"></div>
                <div class="col-md-6"><label class="fw-bold small">Preço Custo</label><input type="number" step="0.01" name="preco_custo" class="form-control" value="0.00"></div>
            </div></div>
            <div class="modal-footer"><button type="submit" name="salvar_produto" class="btn btn-primary w-100 fw-bold">Salvar Item</button></div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalAcoes" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white"><h5 class="modal-title fw-bold"><i class="fas fa-edit me-2"></i>Gerenciar Insumo</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body p-4"><div class="row g-3">
                <input type="hidden" name="produto_id" id="edit_id">
                <div class="col-md-12 alert alert-info py-2"><strong>Estoque Atual:</strong> <span id="view_est">0</span></div>
                
                <div class="col-md-6 bg-warning-subtle p-2 border rounded">
                    <label class="fw-bold small text-dark">Ajustar Estoque (+ ou -)</label>
                    <input type="number" name="qtd_ajuste" class="form-control" placeholder="Ex: 10 ou -5">
                </div>
                <div class="col-md-6 bg-warning-subtle p-2 border rounded">
                    <label class="fw-bold small text-dark">Nova Validade</label>
                    <input type="date" name="validade_ajuste" class="form-control">
                </div>

                <div class="col-md-4"><label class="fw-bold small">Cód.</label><input type="text" name="referencia" id="edit_ref" class="form-control"></div>
                <div class="col-md-8"><label class="fw-bold small">Descrição</label><input type="text" name="descricao" id="edit_desc" class="form-control"></div>
                <div class="col-md-4"><label class="fw-bold small">Unidade</label><input type="text" name="unidade" id="edit_un" class="form-control"></div>
                <div class="col-md-4"><label class="fw-bold small">Tipo</label>
                    <select name="tipo" id="edit_tipo" class="form-select">
                        <option value="USO E CONSUMO">USO E CONSUMO</option>
                        <option value="OPERACIONAL">OPERACIONAL</option>
                    </select>
                </div>
                <div class="col-md-4"><label class="fw-bold small">Categoria</label>
                    <select name="categoria" id="edit_cat" class="form-select">
                        <option value="OPERACIONAL">OPERACIONAL</option>
                        <option value="ESCRITÓRIO ADM">ESCRITÓRIO ADM</option>
                        <option value="CONSERVAÇÃO">CONSERVAÇÃO</option>
                        <option value="PERFUMARIA E HIGIENE PESSOAL">PERFUMARIA</option>
                        <option value="ALIMENTOS">ALIMENTOS</option>
                    </select>
                </div>
                <div class="col-md-6"><label class="fw-bold small">Mínimo</label><input type="number" name="minimo" id="edit_min" class="form-control"></div>
                <div class="col-md-6"><label class="fw-bold small">Custo</label><input type="number" step="0.01" name="preco_custo" id="edit_custo" class="form-control"></div>
            </div></div>
            <div class="modal-footer d-flex justify-content-between">
                <button type="submit" name="excluir_produto" class="btn btn-outline-danger" onclick="return confirm('Excluir?')"><i class="fas fa-trash me-2"></i>Excluir</button>
                <button type="submit" name="editar_produto" class="btn btn-success px-5">Salvar Alterações</button>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function abrirAcoes(p) {
    document.getElementById('edit_id').value = p.id;
    document.getElementById('edit_ref').value = p.codigo_referencia;
    document.getElementById('edit_desc').value = p.descricao;
    document.getElementById('edit_un').value = p.unidade_medida;
    document.getElementById('edit_tipo').value = p.tipo_produto;
    document.getElementById('edit_cat').value = p.categoria;
    document.getElementById('edit_min').value = p.estoque_minimo;
    document.getElementById('edit_custo').value = p.preco_custo;
    document.getElementById('view_est').innerText = p.estoque_total || 0;
    new bootstrap.Modal(document.getElementById('modalAcoes')).show();
}
</script>

<?php if(isset($_GET['msg'])): ?>
<script>
    const msgs = {sucesso: 'Cadastrado!', editado: 'Atualizado!', excluido: 'Removido!'};
    Swal.fire({ title: 'Sucesso!', text: msgs['<?= $_GET['msg'] ?>'], icon: 'success', confirmButtonColor: '#254c90' });
</script>
<?php endif; ?>
</body>
</html>