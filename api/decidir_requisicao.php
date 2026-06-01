<?php
require_once __DIR__ . '/../config/db.php';
session_start(); // Essencial para capturar quem está logado

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Captura o ID de quem está clicando no botão agora
    $id_usuario_real = $_SESSION['glpi_id'] ?? $_SESSION['usuario_id'] ?? 0;

    $id = (int)($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? ''; 
    $justificativa = $_POST['justificativa'] ?? '';

    // Validação básica
    if ($id <= 0 || !in_array($status, ['APROVADO_GESTOR', 'REJEITADO'])) {
        echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
        exit;
    }

    try {
        /**
         * 2. GRAVAÇÃO DE AUDITORIA:
         * Mantemos o 'aprovador_id' original (quem devia aprovar)
         * E gravamos no 'finalizado_por_id' quem aprovou de fato
         */
        $stmt = $conn->prepare("UPDATE requisicoes SET 
                                status_pedido = ?, 
                                justificativa_gestor = ?, 
                                finalizado_por_id = ? 
                                WHERE id = ?");
        
        $stmt->bind_param("ssii", $status, $justificativa, $id_usuario_real, $id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            throw new Exception("Erro ao atualizar o banco de dados.");
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
}