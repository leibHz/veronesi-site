<?php
header('Content-Type: application/json');

// Adiciona relatório de erros para depuração
ini_set('display_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/config_pdo.php';

$json_str = file_get_contents('php://input');
$data = json_decode($json_str);

// CORREÇÃO: Acessa os dados da subscrição dentro do objeto correto
$subscription_data = $data->subscription ?? null;
$id_cliente = isset($data->id_cliente) && !empty($data->id_cliente) ? $data->id_cliente : null;

if (!$subscription_data || !isset($subscription_data->endpoint)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Dados de inscrição inválidos.']);
    exit;
}

try {
    $endpoint = $subscription_data->endpoint;
    $p256dh = $subscription_data->keys->p256dh;
    $auth = $subscription_data->keys->auth;

    // Lógica "UPSERT": Verifica se o ENDPOINT já existe.
    $stmt = $pdo->prepare("SELECT id_inscricao, id_cliente FROM notificacoes_push WHERE endpoint = ?");
    $stmt->execute([$endpoint]);
    $existing_subscription = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing_subscription) {
        // O endpoint já existe.
        // Se o usuário está logado agora, mas a inscrição era anônima, atualiza com o id_cliente.
        if ($id_cliente !== null && $existing_subscription['id_cliente'] === null) {
            $stmt_update = $pdo->prepare("UPDATE notificacoes_push SET id_cliente = ? WHERE endpoint = ?");
            $stmt_update->execute([$id_cliente, $endpoint]);
            echo json_encode(['status' => 'success', 'message' => 'Inscrição atualizada para o usuário.']);
        } else {
            // Se não, não faz nada.
            echo json_encode(['status' => 'success', 'message' => 'Inscrição já existente.']);
        }
    } else {
        // A inscrição não existe, então insere uma nova.
        $stmt_insert = $pdo->prepare(
            "INSERT INTO notificacoes_push (id_cliente, endpoint, p256dh, auth) VALUES (?, ?, ?, ?)"
        );
        $stmt_insert->execute([$id_cliente, $endpoint, $p256dh, $auth]);
        echo json_encode(['status' => 'success', 'message' => 'Inscrição salva com sucesso.']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    // Retorna a mensagem de erro específica do banco de dados
    echo json_encode(['status' => 'error', 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Erro geral no servidor: ' . $e->getMessage()]);
}
