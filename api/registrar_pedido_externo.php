<?php
/**
 * api/registrar_pedido_externo.php
 * Recebe o POST do solicitar.php, persiste a requisição e calcula estoque de mesa.
 * Refatorado: prepared statements, transação, lógica de qtd_mesa, log de auditoria.
 */

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

// Aceita apenas POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit;
}

// ─── 1. Leitura e sanitização dos dados ──────────────────────────────────────

$solicitante       = trim($_POST['solicitante'] ?? '');
$setor             = trim($_POST['setor'] ?? '');
$aprovador_id      = (int)($_POST['aprovador_id'] ?? 0);
$motivo            = trim($_POST['motivo_solicitacao'] ?? '');
$ip_origem         = $_SERVER['REMOTE_ADDR'] ?? '';
$dispositivo_info  = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);

// Campos de item (arrays paralelos vindos do form)
$nomes_item  = $_POST['item_nome']  ?? [];
$qtds_item   = $_POST['item_qtd']   ?? [];
$benef_nomes = $_POST['benef_nome'] ?? [];
$benef_setos = $_POST['benef_setor'] ?? [];
$benef_qtds  = $_POST['benef_qtd']  ?? [];

// ─── 2. Validações básicas ────────────────────────────────────────────────────

if (empty($solicitante) || empty($setor) || $aprovador_id <= 0 || empty($motivo)) {
    echo json_encode(['success' => false, 'message' => 'Preencha todos os campos obrigatórios.']);
    exit;
}

if (empty($nomes_item) || empty($qtds_item)) {
    echo json_encode(['success' => false, 'message' => 'Adicione ao menos um item ao pedido.']);
    exit;
}

// Filtra apenas letras e espaços no nome
if (!preg_match('/^[a-zA-ZÀ-ÿ\s]+$/u', $solicitante)) {
    echo json_encode(['success' => false, 'message' => 'Nome do solicitante inválido.']);
    exit;
}

// ─── 3. Persiste tudo em transação ───────────────────────────────────────────

$conn->begin_transaction();

try {

    // 3a. Insere a requisição principal
    $sql_req = "INSERT INTO requisicoes
                    (solicitante, setor, aprovador_id, motivo_solicitacao, status_pedido, ip_origem, dispositivo_info, data_criacao)
                VALUES (?, ?, ?, ?, 'PENDENTE_GESTOR', ?, ?, NOW())";
    $stmt_req = $conn->prepare($sql_req);
    $stmt_req->bind_param('ssisss',
        $solicitante,
        $setor,
        $aprovador_id,
        $motivo,
        $ip_origem,
        $dispositivo_info
    );
    $stmt_req->execute();
    $req_id = $conn->insert_id;

    if ($req_id <= 0) {
        throw new Exception('Não foi possível criar a requisição.');
    }

    // 3b. Insere cada item — calculando qtd_mesa para cada um
    $sql_item = "INSERT INTO requisicao_itens
                     (requisicao_id, produto_nome, quantidade, qtd_mesa, destino_detalhado)
                 VALUES (?, ?, ?, ?, ?)";
    $stmt_item = $conn->prepare($sql_item);

    foreach ($nomes_item as $idx => $nome_item) {
        $nome_item  = trim($nome_item);
        $qtd_total  = (int)($qtds_item[$idx] ?? 0);

        if (empty($nome_item) || $qtd_total <= 0) {
            continue; // pula linhas vazias
        }

        // Coleta beneficiários associados a este índice de item
        // O form usa arrays flat; precisamos agrupar por item
        // Nota: os índices de benef_* são sequenciais por item.
        // Usamos uma chave de agrupamento baseada no índice do item.
        // (Ver montagem do payload na seção de itens com data-item-idx)
        $beneficiarios_do_item = [];
        if (isset($_POST['benef_item_idx'])) {
            foreach ($_POST['benef_item_idx'] as $b_idx => $item_idx) {
                if ((int)$item_idx === $idx) {
                    $bn   = trim($benef_nomes[$b_idx] ?? '');
                    $bst  = trim($benef_setos[$b_idx] ?? '');
                    $bqtd = (int)($benef_qtds[$b_idx] ?? 0);
                    if (!empty($bn) && $bqtd > 0) {
                        $beneficiarios_do_item[] = [
                            'nome'     => $bn,
                            'setor'    => $bst,
                            'qtd'      => $bqtd,
                        ];
                    }
                }
            }
        }

        // Calcula qtd distribuída e qtd de mesa
        $qtd_distrib = array_sum(array_column($beneficiarios_do_item, 'qtd'));
        $qtd_mesa    = max(0, $qtd_total - $qtd_distrib);

        // Monta string de destino legível (para exibição nos painéis)
        $destino_str = '';
        foreach ($beneficiarios_do_item as $b) {
            $destino_str .= $b['nome'] . ' (' . $b['setor'] . '): ' . $b['qtd'] . ' un.|';
        }
        if ($qtd_mesa > 0) {
            $destino_str .= 'ESTOQUE DE MESA: ' . $qtd_mesa . ' un.|';
        }
        $destino_str = rtrim($destino_str, '|');

        $stmt_item->bind_param('issis',
            $req_id,
            $nome_item,
            $qtd_total,
            $qtd_mesa,
            $destino_str
        );
        $stmt_item->execute();

        // 3c. Se há qtd_mesa, registra também no estoque de mesa
        if ($qtd_mesa > 0) {
            $sql_mesa = "INSERT INTO estoque_mesa
                             (setor, produto_nome, quantidade, data_entrada, requisicao_id)
                         VALUES (?, ?, ?, NOW(), ?)";
            $stmt_mesa = $conn->prepare($sql_mesa);
            $stmt_mesa->bind_param('ssii',
                $setor,
                $nome_item,
                $qtd_mesa,
                $req_id
            );
            $stmt_mesa->execute();
        }
    }

    // 3d. Log de auditoria
    $log_desc = "Nova requisição de $solicitante ($setor) com " . count($nomes_item) . " item(ns).";
    $sql_log  = "INSERT INTO logs_sistema
                     (usuario_id, usuario_nome, tabela_afetada, registro_id, acao, descricao_log)
                 VALUES (0, ?, 'requisicoes', ?, 'CRIAR_REQUISICAO', ?)";
    $stmt_log = $conn->prepare($sql_log);
    $stmt_log->bind_param('sis', $solicitante, $req_id, $log_desc);
    $stmt_log->execute();

    $conn->commit();

    // Gera protocolo legível
    $protocolo = date('Ymd') . '-' . str_pad($req_id, 4, '0', STR_PAD_LEFT);

    echo json_encode([
        'success'   => true,
        'message'   => 'Requisição registrada com sucesso.',
        'req_id'    => $req_id,
        'protocolo' => $protocolo,
    ]);

} catch (Exception $e) {
    $conn->rollback();
    error_log('Erro ao registrar requisição: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno ao salvar o pedido. Tente novamente.',
    ]);
}
