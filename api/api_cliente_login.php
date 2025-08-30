<?php
// ARQUIVO: api/api_cliente_login.php (CRIE ESTE NOVO ARQUIVO)
require 'config.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->email) || !isset($data->senha)) {
    http_response_code(400);
    echo json_encode(['message' => 'Email e senha são obrigatórios.']);
    exit();
}

$endpoint = $supabase_url . '/rest/v1/clientes?select=id_cliente,nome_completo,senha_hash&email=eq.' . urlencode($data->email) . '&limit=1';
$ch = curl_init($endpoint);
$headers = ['apikey: ' . $supabase_secret_key, 'Authorization: Bearer ' . $supabase_secret_key];

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpcode !== 200) {
    http_response_code(500);
    echo json_encode(['message' => 'Erro no serviço de autenticação.']);
    exit();
}

$clientes = json_decode($response, true);

if (count($clientes) > 0 && password_verify($data->senha, $clientes[0]['senha_hash'])) {
    http_response_code(200);
    // Retorna os dados do cliente para serem salvos no frontend
    echo json_encode([
        'status' => 'success',
        'message' => 'Login bem-sucedido!',
        'cliente' => [
            'id_cliente' => $clientes[0]['id_cliente'],
            'nome_completo' => $clientes[0]['nome_completo']
        ]
    ]);
} else {
    http_response_code(401);
    echo json_encode(['message' => 'Email ou senha inválidos.']);
}
?>