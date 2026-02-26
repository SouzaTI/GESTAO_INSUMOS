<?php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

// Verifica se os dados foram enviados por POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? ''; // Pode ser 'APROVADO_GESTOR' ou 'REJEITADO'
    $justificativa = $_POST['justificativa'] ?? ''; // Nova variável

    // Validação básica para evitar erros de banco
    if ($id <= 0 || !in_array($status, ['APROVADO_GESTOR', 'REJEITADO'])) {
        echo json_encode(['success' => false, 'message' => 'Dados inválidos ou status incorreto.']);
        exit;
    }

    try {
        // Atualiza o status da requisição
        $stmt = $conn->prepare("UPDATE requisicoes SET status_pedido = ?, justificativa_gestor = ? WHERE id = ?");
        $stmt->bind_param("ssi", $status, $justificativa, $id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            throw new Exception("Erro ao atualizar o banco de dados.");
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método de requisição não permitido.']);
}