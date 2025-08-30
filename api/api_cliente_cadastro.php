<?php
// ARQUIVO: api/api_cliente_cadastro.php (CRIE ESTE NOVO ARQUIVO)
require 'config.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->nome_completo) || !isset($data->email) || !isset($data->senha)) {
    http_response_code(400);
    echo json_encode(['message' => 'Todos os campos são obrigatórios.']);
    exit();
}

// 1. Verificar se o e-mail já existe
$check_endpoint = $supabase_url . '/rest/v1/clientes?select=email&email=eq.' . urlencode($data->email);
$ch_check = curl_init($check_endpoint);
$headers = ['apikey: ' . $supabase_secret_key, 'Authorization: Bearer ' . $supabase_secret_key];
curl_setopt($ch_check, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch_check, CURLOPT_HTTPHEADER, $headers);
$response_check = curl_exec($ch_check);
curl_close($ch_check);

if (count(json_decode($response_check, true)) > 0) {
    http_response_code(409); // Conflict
    echo json_encode(['message' => 'Este e-mail já está cadastrado.']);
    exit();
}

// 2. Criar o novo cliente
$senha_hash = password_hash($data->senha, PASSWORD_DEFAULT);

$insert_endpoint = $supabase_url . '/rest/v1/clientes';
$postData = [
    'nome_completo' => $data->nome_completo,
    'email' => $data->email,
    'senha_hash' => $senha_hash
];

$ch_insert = curl_init($insert_endpoint);
curl_setopt($ch_insert, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch_insert, CURLOPT_POST, true);
curl_setopt($ch_insert, CURLOPT_POSTFIELDS, json_encode($postData));
curl_setopt($ch_insert, CURLOPT_HTTPHEADER, array_merge($headers, ['Content-Type: application/json', 'Prefer: return=minimal']));

curl_exec($ch_insert);
$httpcode = curl_getinfo($ch_insert, CURLINFO_HTTP_CODE);
curl_close($ch_insert);

if ($httpcode == 201) { // Created
    http_response_code(201);
    echo json_encode(['status' => 'success', 'message' => 'Cadastro realizado com sucesso!']);
} else {
    http_response_code(500);
    echo json_encode(['message' => 'Ocorreu um erro ao realizar o cadastro.']);
}
?>