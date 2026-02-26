<?php
// Desativar exibição de erros que quebram o JSON, mas registrar no log do servidor
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

$ip = $_SERVER['REMOTE_ADDR'];

try {
    // 1. Verificar se a tabela existe (Segurança contra o erro anterior)
    $valida = $conn->query("SHOW TABLES LIKE 'requisicoes'");
    if($valida->num_rows == 0) {
        throw new Exception("Erro Crítico: Tabela 'requisicoes' não encontrada no banco.");
    }

    // 2. Coleta de dados básicos
    $solicitante  = $_POST['solicitante'] ?? '';
    $setor        = $_POST['setor'] ?? '';
    $aprovador_id = (int)($_POST['aprovador_id'] ?? 0);
    $motivo = $_POST['motivo_solicitacao'] ?? '';

    // 3. Validar se há itens
    if (empty($_POST['item_nome'])) {
        throw new Exception("Nenhum produto foi selecionado.");
    }

    $conn->begin_transaction();

    // 4. Inserir Cabeçalho
    $stmt = $conn->prepare("INSERT INTO requisicoes (solicitante, setor, aprovador_id, motivo_solicitacao, status_pedido, ip_origem) VALUES (?, ?, ?, ?, 'PENDENTE_GESTOR', ?)");
    $stmt->bind_param("ssiss", $solicitante, $setor, $aprovador_id, $motivo, $ip);
    
    if (!$stmt->execute()) {
        throw new Exception("Erro ao salvar cabeçalho: " . $stmt->error);
    }
    
    $requisicao_id = $conn->insert_id;

    // 5. Inserir Itens e Processar Detalhamento
    $nomes = $_POST['item_nome'];
    $qtds  = $_POST['item_qtd'];
    $benef_nomes = $_POST['benef_nome'] ?? [];
    $benef_setores = $_POST['benef_setores'] ?? [];
    $benef_qtds = $_POST['benef_qtd'] ?? [];

    foreach ($nomes as $index => $prod) {
        $total = (int)$qtds[$index];
        $detalhe = "";
        $soma = 0;

        // Concatenar beneficiários para o campo de texto
        if (!empty($benef_nomes)) {
            foreach ($benef_nomes as $k => $b_nome) {
                if (!empty($b_nome)) {
                    $q_ind = (int)$benef_qtds[$k];
                    $setor_b = $benef_setores[$k] ?? 'Geral';
                    $detalhe .= "👤 $b_nome ($setor_b): $q_ind un | "; // Agora registra o setor
                    $soma += $q_ind;
                }
            }
        }

        $reserva = $total - $soma;
        if ($reserva > 0) $detalhe .= "📦 RESERVA: $reserva un";

        $stmt_item = $conn->prepare("INSERT INTO requisicao_itens (requisicao_id, produto_nome, quantidade, destino_detalhado) VALUES (?, ?, ?, ?)");
        $stmt_item->bind_param("isis", $requisicao_id, $prod, $total, $detalhe);
        $stmt_item->execute();
    }

    $conn->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if (isset($conn)) $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}