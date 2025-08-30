<?php
// ARQUIVO: api/api_produtos.php (COMPLETO E CORRIGIDO)
// **CORREÇÃO CRÍTICA:** Agora usa a chave pública ($supabase_publishable_key) do config.php,
// o que resolve o erro 404/403 para os clientes.
require 'config.php'; // Inclui o novo arquivo de configuração

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$endpoint = $supabase_url . '/rest/v1/produtos';

$queryParams = [];
$queryParams['select'] = 'id_produto,nome,preco,imagem_url,unidade_medida,disponivel,sessao:sessoes(nome)';

// --- FILTROS ---
$filters = [];
$filters[] = 'disponivel=eq.true'; // Apenas produtos disponíveis

if (isset($_GET['q']) && !empty($_GET['q'])) {
    $filters[] = 'nome=ilike.*' . urlencode($_GET['q']) . '*';
}
if (isset($_GET['promocao']) && $_GET['promocao'] === 'true') {
    $filters[] = 'em_promocao=eq.true';
}

// --- ORDENAÇÃO ---
if (isset($_GET['ordenar'])) {
    switch ($_GET['ordenar']) {
        case 'alfabetica_desc': $queryParams['order'] = 'nome.desc'; break;
        case 'preco_asc': $queryParams['order'] = 'preco.asc'; break;
        case 'preco_desc': $queryParams['order'] = 'preco.desc'; break;
        default: $queryParams['order'] = 'nome.asc'; break;
    }
} else {
    $queryParams['order'] = 'nome.asc';
}

$queryString = http_build_query($queryParams);
if (!empty($filters)) {
    $queryString .= '&' . implode('&', $filters);
}

$full_url = $endpoint . '?' . $queryString;

// --- EXECUÇÃO DA REQUISIÇÃO ---
$ch = curl_init($full_url);
// **AQUI ESTÁ A CORREÇÃO:** Usa a chave pública para respeitar as políticas do Supabase.
$headers = ['apikey: ' . $supabase_publishable_key];
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpcode !== 200) {
    http_response_code($httpcode > 0 ? $httpcode : 500);
    echo json_encode(['status' => 'error', 'message' => 'Erro ao buscar produtos. Código: ' . $httpcode]);
    exit();
}

echo $response;
?>
