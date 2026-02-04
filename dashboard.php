<?php
require_once __DIR__ . '/config/db.php';

// Proteção de Sessão
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$nome_usuario = $_SESSION['usuario_nome'];
$nivel_usuario = $_SESSION['usuario_nivel'] ?? 'user';

// --- 1. CONSULTAS OTIMIZADAS (KPIs) ---

// Consolidado: Estoque Total e Quantidade de Alertas de Validade
$stats = $conn->query("SELECT 
    SUM(quantidade_atual) as total_estoque,
    COUNT(DISTINCT CASE WHEN data_validade <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND quantidade_atual > 0 THEN produto_id END) as alertas_validade
    FROM lotes")->fetch_assoc();

// Consumo do Mês Atual (Saídas registradas)
$sql_consumo = "SELECT ABS(SUM(quantidade_atual)) as total 
                FROM lotes 
                WHERE quantidade_atual < 0 
                AND MONTH(data_entrada) = MONTH(CURRENT_DATE()) 
                AND YEAR(data_entrada) = YEAR(CURRENT_DATE())";
$total_consumo = $conn->query($sql_consumo)->fetch_assoc()['total'] ?? 0;

// KPI de Eficiência: Tempo Médio de Atendimento de Compras (em minutos)
$tempo_medio = $conn->query("SELECT AVG(TIMESTAMPDIFF(MINUTE, data_abertura, data_finalizacao)) as media 
    FROM pedidos_compra WHERE status = 'FINALIZADO'")->fetch_assoc()['media'] ?? 0;

// 4. LISTAGEM FEFO CORRIGIDA
$resultado_vencer = $conn->query("SELECT p.descricao, 
                                         MIN(l.data_validade) as data_validade, 
                                         SUM(l.quantidade_atual) as quantidade_total 
                                  FROM produtos p 
                                  JOIN lotes l ON p.id = l.produto_id 
                                  WHERE l.data_validade <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) 
                                  AND l.quantidade_atual > 0 
                                  GROUP BY p.id 
                                  ORDER BY data_validade ASC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .card-kpi { border-radius: 15px; border: none; transition: transform 0.2s; }
        .card-kpi:hover { transform: translateY(-5px); }
        .icon-box { width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; border-radius: 12px; font-size: 1.25rem; }
        .animate-pulse { animation: pulse-red 2s infinite; }
        @keyframes pulse-red {
            0% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(220, 53, 69, 0); }
            100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
        }
    </style>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
    <header class="mb-5 d-flex justify-content-between align-items-end">
        <div>
            <h2 class="fw-bold text-dark mb-1">Painel de Performance</h2>
            <p class="text-muted mb-0">Monitoramento de insumos e tempo de resposta operacional.</p>
        </div>
        <div class="badge bg-white text-dark shadow-sm p-3 border rounded-3">
            <i class="fas fa-user-shield me-2 text-primary"></i><?php echo $nome_usuario; ?> 
            <span class="ms-2 badge bg-primary"><?php echo strtoupper($nivel_usuario); ?></span>
        </div>
    </header>

    <div class="row g-4 mb-5">
        <div class="col-md-3" style="cursor: pointer;" onclick="abrirEstoqueCritico()">
            <div class="card card-kpi p-4 shadow-sm">
                <div class="d-flex align-items-center">
                    <div class="icon-box bg-primary-subtle text-primary me-3"><i class="fas fa-boxes-stacked"></i></div>
                    <div>
                        <small class="text-muted fw-bold">ESTOQUE FÍSICO</small>
                        <h3 class="mb-0 fw-bold <?php echo ($stats['total_estoque'] < 0) ? 'text-danger' : ''; ?>">
                            <?php echo number_format($stats['total_estoque'] ?? 0, 0, ',', '.'); ?>
                        </h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-kpi p-4 shadow-sm border-start border-danger border-4">
                <div class="d-flex align-items-center">
                    <div class="icon-box bg-danger-subtle text-danger me-3"><i class="fas fa-hourglass-half"></i></div>
                    <div>
                        <small class="text-muted fw-bold">ALERTAS VALIDADE</small>
                        <h3 class="mb-0 fw-bold text-danger"><?php echo $stats['alertas_validade'] ?? 0; ?></h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-kpi p-4 shadow-sm">
                <div class="d-flex align-items-center">
                    <div class="icon-box bg-warning-subtle text-warning me-3"><i class="fas fa-truck-loading"></i></div>
                    <div>
                        <small class="text-muted fw-bold">CONSUMO MENSAL</small>
                        <h3 class="mb-0 fw-bold text-warning"><?php echo number_format($total_consumo, 0, ',', '.'); ?></h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-kpi p-4 shadow-sm bg-dark text-white">
                <div class="d-flex align-items-center">
                    <div class="icon-box bg-white bg-opacity-10 text-white me-3"><i class="fas fa-stopwatch"></i></div>
                    <div>
                        <small class="text-white-50 fw-bold">MÉDIA DE COMPRA</small>
                        <h3 class="mb-0 fw-bold"><?php echo round($tempo_medio); ?> <small class="fs-6 fw-light">min</small></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold"><i class="fas fa-exclamation-triangle text-warning me-2"></i>Prioridade de Uso (FEFO)</h5>
            <span class="text-muted small">Próximos 30 dias</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Item / Insumo</th>
                        <th class="text-center">Quantidade</th>
                        <th>Vencimento</th>
                        <th>Ação Recomendada</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($item = $resultado_vencer->fetch_assoc()): 
                        $dias = ceil((strtotime($item['data_validade']) - time()) / 86400);
                        
                        if($dias < 0) {
                            $status_class = "bg-danger";
                            $status_text = "VENCIDO";
                        } elseif($dias <= 7) {
                            $status_class = "bg-danger animate-pulse";
                            $status_text = "URGENTE: $dias DIAS";
                        } else {
                            $status_class = "bg-warning text-dark";
                            $status_text = "USO EM $dias DIAS";
                        }
                    ?>
                    <tr>
                        <td class="ps-4"><strong><?php echo $item['descricao']; ?></strong></td>
                        <td class="text-center"><?php echo number_format($item['quantidade_total'], 0); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($item['data_validade'])); ?></td>
                        <td><span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if($resultado_vencer->num_rows == 0): ?>
                        <tr><td colspan="4" class="text-center p-5 text-muted"><i class="fas fa-check-circle fa-2x d-block mb-2 text-success opacity-25"></i>Tudo certo! Nenhum insumo vencendo nos próximos 30 dias.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="modalCritico" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-exclamation-circle me-2"></i>Itens com Estoque Esgotado</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-sm">
                        <thead><tr><th>Produto</th><th class="text-end">Saldo Atual</th></tr></thead>
                        <tbody id="lista_negativa"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    function abrirEstoqueCritico() {
        $.get('api/get_estoque_critico.php', function(dados) {
            let h = '';
            dados.forEach(item => {
                h += `<tr>
                        <td>${item.descricao}</td>
                        <td class="text-end text-danger fw-bold">${item.saldo}</td>
                      </tr>`;
            });
            if(dados.length === 0) h = '<tr><td colspan="2" class="text-center">Nenhum item negativo.</td></tr>';
            $('#lista_negativa').html(h);
            $('#modalCritico').modal('show');
        });
    }
    </script>
</body>
</html>
</body>
</html>