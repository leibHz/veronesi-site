<?php
// ARQUIVO NOVO: api/api_admin_clientes.php
// Gerencia a listagem e exclusão de clientes pelo painel administrativo.

require 'config.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

$method = $_SERVER['REQUEST_METHOD'];

// --- ROTA GET: Listar todos os clientes ---
if ($method === 'GET') {
    // Seleciona os campos relevantes e ordena por nome
    $endpoint = $supabase_url . '/rest/v1/clientes?select=id_cliente,nome_completo,email,data_cadastro&order=nome_completo.asc';
    
    $context = stream_context_create([
        'http' => [
            'header' => "apikey: $supabase_secret_key\r\nAuthorization: Bearer $supabase_secret_key\r\n"
        ]
    ]);

    $response = file_get_contents($endpoint, false, $context);
    $http_code = $http_response_header[0];

    if (strpos($http_code, '200') !== false) {
        echo $response;
    } else {
        http_response_code(500);
        echo json_encode(['message' => 'Erro ao buscar clientes.']);
    }
    exit();
}

// --- ROTA DELETE: Excluir um cliente ---
if ($method === 'DELETE') {
    $data = json_decode(file_get_contents("php://input"));

    if (!isset($data->id_cliente)) {
        http_response_code(400);
        echo json_encode(['message' => 'ID do cliente é obrigatório.']);
        exit();
    }

    $id = intval($data->id_cliente);

    // Endpoint para deletar o cliente com o ID correspondente
    $endpoint = $supabase_url . '/rest/v1/clientes?id_cliente=eq.' . $id;

    $opts = [
        'http' => [
            'method' => 'DELETE',
            'header' => "apikey: $supabase_secret_key\r\nAuthorization: Bearer $supabase_secret_key\r\nPrefer: return=minimal\r\n"
        ]
    ];
    $context = stream_context_create($opts);
    file_get_contents($endpoint, false, $context);

    // Supabase retorna 204 No Content em um delete bem-sucedido, mesmo com return=minimal.
    // A ausência de erro é considerada sucesso.
    echo json_encode(['status' => 'success', 'message' => 'Cliente removido com sucesso.']);
    exit();
}

// Se o método não for GET ou DELETE
http_response_code(405);
echo json_encode(['message' => 'Método não permitido.']);
?>
