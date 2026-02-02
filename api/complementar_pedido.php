<?php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

$id = (int)$_POST['id'];

try {
    // Usando as colunas confirmadas na sua estrutura
    // Removido o campo 'data_finalizacao_dados' que causava o erro
    $sql = "UPDATE pedidos_compra SET 
            status = 'APROVADO', 
            cnpj = ?, 
            forma_pagamento = ?, 
            pix_favorecido = ?, 
            pix_chave = ?, 
            parcelamento = ?
            WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssi", 
        $_POST['cnpj'], 
        $_POST['pgto'], 
        $_POST['pix_fav'], 
        $_POST['pix_chave'], 
        $_POST['parcelas'], 
        $id
    );
    $stmt->execute();

    // REGISTRO DE LOG
    $desc = "Complementou dados (CNPJ: ".$_POST['cnpj'].") do pedido #$id";
    $stmt_log = $conn->prepare("INSERT INTO logs_sistema (usuario_id, usuario_nome, tabela_afetada, registro_id, acao, descricao_log) VALUES (?, ?, 'pedidos_compra', ?, 'COMPLEMENTAR', ?)");
    $stmt_log->bind_param("isis", $_SESSION['usuario_id'], $_SESSION['usuario_nome'], $id, $desc);
    $stmt_log->execute();

    echo json_encode(['success' => true]);

    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => "Erro no banco: " . $e->getMessage()]);
}