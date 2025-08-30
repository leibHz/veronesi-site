<?php
// ARQUIVO: api/api_admin_dashboard.php
// -----------------------------------------------------------------
// Busca dados para preencher o dashboard.

require 'config.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Função para fazer requisições GET com contagem (count)
function getCount($table, $supabase_url, $supabase_secret_key) {
    // Usamos o header 'Prefer: count=exact' para obter o total de registros
    $endpoint = $supabase_url . '/rest/v1/' . $table . '?select=';
    
    $ch = curl_init($endpoint);
    $headers = [
        'apikey: ' . $supabase_secret_key,
        'Authorization: Bearer ' . $supabase_secret_key,
        'Prefer: count=exact'
    ];

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    // CURLOPT_HEADER => true para pegar os cabeçalhos da resposta
    curl_setopt($ch, CURLOPT_HEADER, true);

    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpcode !== 200) return 0;

    // Extrai o 'Content-Range' do cabeçalho da resposta
    preg_match('/Content-Range: \d+-\d+\/(\d+)/', $response, $matches);
    
    return isset($matches[1]) ? (int)$matches[1] : 0;
}

// Busca as contagens de cada tabela
$total_produtos = getCount('produtos', $supabase_url, $supabase_secret_key);
$total_clientes = getCount('clientes', $supabase_url, $supabase_secret_key);
$total_encomendas = getCount('encomendas', $supabase_url, $supabase_secret_key);

// Monta o array de resposta
$stats = [
    'total_produtos' => $total_produtos,
    'total_clientes' => $total_clientes,
    'total_encomendas' => $total_encomendas
];

// Retorna os dados em JSON
echo json_encode($stats);

?>