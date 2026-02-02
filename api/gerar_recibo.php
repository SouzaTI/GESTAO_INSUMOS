<?php
require_once __DIR__ . '/../config/db.php';
$id = (int)$_GET['id'];

// 1. Busca os dados consolidados do cabeçalho (Incluso os novos campos financeiros)
$pedido = $conn->query("SELECT * FROM pedidos_compra WHERE id = $id")->fetch_assoc();

if (!$pedido) {
    die("Pedido não encontrado.");
}

// 2. Busca os itens vinculados usando o elo 'pedido_id'
$sql_itens = "SELECT p.descricao, m.quantidade, p.unidade_medida, m.observacao 
              FROM movimentacoes m
              JOIN produtos p ON m.produto_id = p.id
              WHERE m.pedido_id = $id";
$resultado_itens = $conn->query($sql_itens);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Recibo de Solicitação #<?= $id ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 40px; color: #333; background: #fff; line-height: 1.4; }
        .header { border-bottom: 3px solid #254c90; padding-bottom: 15px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { color: #254c90; margin: 0; font-size: 26px; font-weight: 800; }
        
        .order-info { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; background: #f8f9fa; padding: 20px; border-radius: 10px; border: 1px solid #eee; margin-bottom: 20px; }
        .info-block p { margin: 4px 0; font-size: 14px; }
        .info-label { font-weight: bold; color: #555; text-transform: uppercase; font-size: 11px; display: block; }
        
        /* Bloco Financeiro Destacado */
        .payment-info { background: #fff; border: 2px solid #254c90; padding: 15px; border-radius: 10px; margin-bottom: 30px; display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; }
        .pix-box { grid-column: span 3; background: #eef2f7; padding: 10px; border-radius: 6px; margin-top: 10px; border-left: 4px solid #254c90; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background: #254c90; color: white; text-align: left; padding: 12px; font-size: 12px; text-transform: uppercase; }
        td { padding: 12px; border-bottom: 1px solid #eee; font-size: 14px; }
        
        .total-section { text-align: right; padding: 20px; background: #254c90; color: white; border-radius: 8px; margin-top: 10px; }
        .total-value { font-size: 24px; font-weight: bold; }

        .signatures { margin-top: 60px; display: grid; grid-template-columns: 1fr 1fr; gap: 50px; text-align: center; }
        .sig-line { border-top: 1px solid #333; padding-top: 5px; font-size: 11px; font-weight: bold; }

        .footer { margin-top: 40px; font-size: 10px; text-align: center; color: #888; border-top: 1px solid #eee; padding-top: 20px; }
        .btn-print { background: #254c90; color: white; border: none; padding: 12px 25px; border-radius: 6px; cursor: pointer; font-weight: bold; transition: 0.3s; }
        
        @media print { .btn-print { display: none; } body { padding: 0; } .order-info, .payment-info { border: 1px solid #ccc; } }
    </style>
</head>
<body>

    <div style="text-align: right; margin-bottom: 20px;">
        <button class="btn-print" onclick="window.print()">
            <i class="fas fa-print me-2"></i> IMPRIMIR DOCUMENTO
        </button>
    </div>

    <div class="header">
        <div>
            <h1>SOLICITAÇÃO DE COMPRA #<?= $id ?></h1>
            <p style="margin: 0; color: #666;">Sistema de Gestão Digital - Fluxo de Suprimentos</p>
        </div>
        <div style="text-align: right;">
            <span style="padding: 8px 15px; background: #254c90; color: #fff; border-radius: 5px; font-weight: bold; font-size: 12px;">
                STATUS: <?= $pedido['status'] ?>
            </span>
        </div>
    </div>

    <div class="order-info">
        <div class="info-block">
            <span class="info-label">Dados do Solicitante</span>
            <p><strong>Nome:</strong> <?= strtoupper($pedido['solicitante']) ?></p>
            <p><strong>Data de Emissão:</strong> <?= date('d/m/Y H:i', strtotime($pedido['data_abertura'])) ?></p>
        </div>
        <div class="info-block" style="text-align: right;">
            <span class="info-label">Dados do Faturamento</span>
            <p><strong>Fornecedor:</strong> <?= $pedido['fornecedor'] ?: 'NÃO DEFINIDO' ?></p>
            <p><strong>CNPJ Faturado:</strong> <?= $pedido['cnpj'] ?: 'NÃO INFORMADO' ?></p>
        </div>
    </div>

    <div class="payment-info">
        <div>
            <span class="info-label">Meio de Pagamento</span>
            <p><strong><?= strtoupper($pedido['forma_pagamento'] ?: 'A definir') ?></strong></p>
        </div>
        <div>
            <span class="info-label">Condição de Pagamento</span>
            <p><strong><?= $pedido['parcelamento'] ?: 'A combinar' ?></strong></p>
        </div>
        <div>
            <span class="info-label">Valor Total</span>
            <p><strong>R$ <?= number_format($pedido['valor_total'], 2, ',', '.') ?></strong></p>
        </div>

        <?php if(strtolower($pedido['forma_pagamento']) == 'pix'): ?>
        <div class="pix-box">
            <span class="info-label"><i class="fas fa-qrcode"></i> Dados para Transferência PIX</span>
            <p style="font-family: monospace; font-size: 16px; margin-top: 5px;">
                Chave: <strong><?= $pedido['pix_chave'] ?></strong>
            </p>
            <p style="font-size: 12px; color: #666;">Favorecido: <?= strtoupper($pedido['pix_favorecido']) ?></p>
        </div>
        <?php endif; ?>
    </div>

    <table>
        <thead>
            <tr>
                <th width="50%">Descrição do Insumo / Produto</th>
                <th style="text-align: center;">Qtd / Unidade</th>
                <th>Observação</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($resultado_itens && $resultado_itens->num_rows > 0): ?>
                <?php while($item = $resultado_itens->fetch_assoc()): ?>
                <tr>
                    <td><strong><?= strtoupper($item['descricao']) ?></strong></td>
                    <td style="text-align: center;"><?= number_format($item['quantidade'], 2, ',', '.') ?> <?= $item['unidade_medida'] ?></td>
                    <td style="color: #666; font-style: italic; font-size: 12px;"><?= $item['observacao'] ?: '-' ?></td>
                </tr>
                <?php endwhile; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="total-section">
        <span class="info-label" style="color: #cbdcf8; margin-bottom: 5px;">Total Geral Autorizado:</span>
        <div class="total-value">R$ <?= number_format($pedido['valor_total'], 2, ',', '.') ?></div>
    </div>

    <div class="signatures">
        <div class="sig-line">
            <?= strtoupper($pedido['solicitante']) ?><br>Solicitante
        </div>
        <div class="sig-line">
            DIRETORIA / FINANCEIRO<br>Autorização Final
        </div>
    </div>

    <div class="footer">
        <p>Este documento é uma via oficial gerada pelo sistema GESTÃO DIGITAL.</p>
        <p>A autenticidade deste pedido pode ser validada no painel administrativo.</p>
        <p>Gerado em <?= date('d/m/Y \à\s H:i:s') ?></p>
    </div>

</body>
</html>