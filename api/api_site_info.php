<?php
// ARQUIVO: api/api_site_info.php (CRIE ESTE NOVO ARQUIVO)
// API PÚBLICA para o frontend buscar o status e horários da loja.
require 'config.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$endpoint = $supabase_url . '/rest/v1/configuracoes_site?id_config=eq.1&select=*';
$context = stream_context_create(['http' => ['header' => "apikey: $supabase_publishable_key\r\n"]]);
$response = file_get_contents($endpoint, false, $context);
$config = json_decode($response);

// Retorna apenas a primeira (e única) linha de configuração
echo json_encode($config[0] ?? null);
?>