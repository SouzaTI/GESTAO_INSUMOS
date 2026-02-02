<?php
require_once __DIR__ . '/config/db.php';
if (!isset($_SESSION['usuario_id'])) { header("Location: login.php"); exit(); }

// Busca os últimos 100 logs para não sobrecarregar a página
$logs = $conn->query("SELECT * FROM logs_sistema ORDER BY data_hora DESC LIMIT 100");
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Auditoria de Sistema - Logs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
    <header class="mb-4">
        <h2 class="fw-bold"><i class="fas fa-history me-2 text-primary"></i>Rastro do Sistema</h2>
        <p class="text-muted">Histórico detalhado de todas as operações realizadas.</p>
    </header>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Data/Hora</th>
                        <th>Usuário Logado</th>
                        <th>Ação</th>
                        <th>Tabela</th>
                        <th>ID Ref.</th>
                        <th>Descrição do Evento</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($l = $logs->fetch_assoc()): ?>
                    <tr>
                        <td class="small"><?= date('d/m/Y H:i:s', strtotime($l['data_hora'])) ?></td>
                        <td><span class="badge bg-secondary"><?= $l['usuario_nome'] ?></span></td>
                        <td>
                            <?php 
                                $color = 'primary';
                                if($l['acao'] == 'CRIAR_PEDIDO') $color = 'success';
                                if($l['acao'] == 'APROVAR') $color = 'info';
                                if($l['acao'] == 'FINALIZAR') $color = 'dark';
                            ?>
                            <span class="badge bg-<?= $color ?>"><?= $l['acao'] ?></span>
                        </td>
                        <td class="small text-muted"><?= $l['tabela_afetada'] ?></td>
                        <td>#<?= $l['registro_id'] ?></td>
                        <td class="small"><?= $l['descricao_log'] ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>