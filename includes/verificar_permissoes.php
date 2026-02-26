<?php
require_once __DIR__ . '/../config/db.php'; // Conexão $conn com gestao_insumos

/**
 * Função para verificar se o usuário tem acesso a um módulo específico
 * @param string $modulo (comprar, estoque, financeiro, usuarios)
 */
function temAcesso($modulo) {
    global $conn;

    if (session_status() === PHP_SESSION_NONE) { session_start(); }

    // 1. REGRA DE OURO: Super-Admin do GLPI sempre tem acesso total
    if (isset($_SESSION['usuario_nivel']) && $_SESSION['usuario_nivel'] === 'admin') {
        return true;
    }

    // 2. Busca as permissões específicas na nova tabela local
    $user_id = $_SESSION['usuario_id'] ?? 0;
    $stmt = $conn->prepare("SELECT privilegios FROM permissoes_app WHERE glpi_user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $resultado = $stmt->get_result()->fetch_assoc();

    if ($resultado) {
        $privilegios = json_decode($resultado['privilegios'], true);
        // Retorna true se o checkbox do módulo estava marcado
        return isset($privilegios[$modulo]) && $privilegios[$modulo] === true;
    }

    return false; // Se não achou na tabela ou não tem o check, nega o acesso
}

/**
 * Trava de página: Redireciona se não tiver acesso
 */
function travarPagina($modulo) {
    if (!temAcesso($modulo)) {
        header("Location: dashboard.php?erro=sem_permissao_modulo");
        exit();
    }
}
?>