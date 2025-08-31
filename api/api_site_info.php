<?php
// ARQUIVO: api/api_site_info.php (MODIFICADO)
// API PÚBLICA que agora calcula e retorna o status real da loja (aberto/fechado).
require 'config.php';
require 'status_logic.php'; // Inclui a nova lógica de status

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$endpoint = $supabase_url . '/rest/v1/configuracoes_site?id_config=eq.1&select=*';
// Usa a chave pública, pois esta é uma API para o cliente
$context = stream_context_create(['http' => ['header' => "apikey: $supabase_publishable_key\r\n"]]); 
$response = @file_get_contents($endpoint, false, $context);

if ($response === false) {
    http_response_code(500);
    echo json_encode(['message' => 'Não foi possível buscar as informações do site.']);
    exit();
}

$config_array = json_decode($response);
$config = $config_array[0] ?? null;

if ($config) {
    // Calcula o status real usando a função da lógica de status
    $status_calculado = calcularStatusLoja($config);
    
    // Adiciona as novas informações ao objeto que será enviado ao frontend
    $config->status_loja_real = $status_calculado['status_real'];
    $config->mensagem_loja_real = $status_calculado['mensagem_real'];
    
    // Cria uma flag final para o frontend saber se pode ou não aceitar encomendas
    // A encomenda só é permitida se o serviço estiver ativo E a loja estiver aberta.
    $config->pode_encomendar = $config->encomendas_ativas && $status_calculado['status_real'];
}

echo json_encode($config);
?>
