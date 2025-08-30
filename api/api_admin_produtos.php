<?php
// ARQUIVO: api/api_admin_produtos.php (COMPLETO E CORRIGIDO)
// Agora usa o require 'config.php' para mais clareza.
require 'config.php'; // Inclui o novo arquivo de configuração

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
// Permite todos os métodos necessários para o CRUD
header("Access-Control-Allow-Methods: GET, POST, DELETE, PATCH");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

$method = $_SERVER['REQUEST_METHOD'];

// Função para buscar sessões
if ($method === 'GET' && isset($_GET['sessoes'])) {
    $endpoint = $supabase_url . '/rest/v1/sessoes?select=id_sessao,nome';
    $context = stream_context_create(['http' => ['header' => "apikey: $supabase_secret_key\r\nAuthorization: Bearer $supabase_secret_key\r\n"]]);
    $response = file_get_contents($endpoint, false, $context);
    echo $response;
    exit();
}

// Função para buscar um único produto por ID
if ($method === 'GET' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $endpoint = $supabase_url . '/rest/v1/produtos?id_produto=eq.' . $id . '&select=*&limit=1';
    $context = stream_context_create(['http' => ['header' => "apikey: $supabase_secret_key\r\nAuthorization: Bearer $supabase_secret_key\r\n"]]);
    $response = file_get_contents($endpoint, false, $context);
    $products = json_decode($response);
    echo json_encode($products[0] ?? null);
    exit();
}

// Função para buscar todos os produtos (para o admin)
if ($method === 'GET') {
    $endpoint = $supabase_url . '/rest/v1/produtos?select=*,sessao:sessoes(nome)&order=nome.asc';
    $context = stream_context_create(['http' => ['header' => "apikey: $supabase_secret_key\r\nAuthorization: Bearer $supabase_secret_key\r\n"]]);
    $response = file_get_contents($endpoint, false, $context);
    echo $response;
    exit();
}

// Função para DELETAR um produto
if ($method === 'DELETE') {
    $data = json_decode(file_get_contents("php://input"));
    $id = $data->id_produto;
    $endpoint = $supabase_url . '/rest/v1/produtos?id_produto=eq.' . $id;
    $opts = ['http' => ['method' => 'DELETE', 'header' => "apikey: $supabase_secret_key\r\nAuthorization: Bearer $supabase_secret_key\r\n"]];
    file_get_contents($endpoint, false, stream_context_create($opts));
    echo json_encode(['status' => 'success', 'message' => 'Produto removido.']);
    exit();
}

// Função para CRIAR ou ATUALIZAR um produto
if ($method === 'POST') {
    $data = $_POST;
    $id = $data['id_produto'] ?? null;
    $imagem_path = $data['imagem_atual'] ?? null;

    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] == 0) {
        $target_dir = "../uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $filename = uniqid() . '-' . basename($_FILES["imagem"]["name"]);
        $target_file = $target_dir . $filename;
        if (move_uploaded_file($_FILES["imagem"]["tmp_name"], $target_file)) {
            $imagem_path = "uploads/" . $filename;
        }
    }

    $payload = [
        'nome' => $data['nome'],
        'preco' => $data['preco'],
        'id_sessao' => $data['id_sessao'],
        'descricao' => $data['descricao'],
        'unidade_medida' => $data['unidade_medida'],
        'disponivel' => isset($data['disponivel']) && $data['disponivel'] === 'on',
        'imagem_url' => $imagem_path
    ];

    $http_method = $id ? 'PATCH' : 'POST';
    $endpoint = $id ? ($supabase_url . '/rest/v1/produtos?id_produto=eq.' . $id) : ($supabase_url . '/rest/v1/produtos');

    $opts = ['http' => [
        'method' => $http_method,
        'header' => "Content-Type: application/json\r\napikey: $supabase_secret_key\r\nAuthorization: Bearer $supabase_secret_key\r\nPrefer: return=minimal\r\n",
        'content' => json_encode($payload)
    ]];
    $context = stream_context_create($opts);
    file_get_contents($endpoint, false, $context);
    
    echo json_encode(['status' => 'success', 'message' => 'Produto salvo com sucesso.']);
    exit();
}
?>