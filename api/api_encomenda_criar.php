<?php
// ARQUIVO: api/api_encomenda_criar.php (REESTRUTURADO E CORRIGIDO)
// Adicionada verificação de segurança para impedir encomendas com a loja fechada.

require 'config_pdo.php'; 

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// --- NOVA VERIFICAÇÃO DE STATUS DA LOJA ---
require 'config.php';       
require 'status_logic.php'; 

// Busca a configuração atual do site
$config_endpoint = $supabase_url . '/rest/v1/configuracoes_site?id_config=eq.1&select=*';
$config_context = stream_context_create(['http' => ['header' => "apikey: $supabase_publishable_key\r\n"]]); 
$config_response = @file_get_contents($config_endpoint, false, $config_context);
$config_array = json_decode($config_response);
$config = $config_array[0] ?? null;

if (!$config) {
    http_response_code(503); // Service Unavailable
    echo json_encode(['status' => 'error', 'message' => 'Serviço de encomendas temporariamente indisponível. Não foi possível verificar o status da loja.']);
    exit();
}

// Calcula o status real (aberto/fechado)
$status_calculado = calcularStatusLoja($config);

// Verifica se as encomendas estão ativas E se a loja está no horário de funcionamento
if (!$config->encomendas_ativas || !$status_calculado['status_real']) {
    http_response_code(403); // Forbidden
    echo json_encode(['status' => 'error', 'message' => 'Desculpe, a loja está fechada para encomendas no momento.']);
    exit();
}
// --- FIM DA VERIFICAÇÃO ---

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->id_cliente) || !isset($data->itens) || empty($data->itens)) {
    http_response_code(400); 
    echo json_encode(['status' => 'error', 'message' => 'Dados da encomenda inválidos ou incompletos.']);
    exit();
}

$id_cliente = $data->id_cliente;
$itens_carrinho = $data->itens;
$valor_total = 0;

try {
    $ids_produtos = array_map(function($item) { return $item->id; }, $itens_carrinho);
    
    if (empty($ids_produtos)) {
        throw new Exception("O carrinho não contém itens válidos.");
    }

    $placeholders = implode(',', array_fill(0, count($ids_produtos), '?'));
    
    $sql_precos = "SELECT id_produto, preco FROM produtos WHERE id_produto IN ($placeholders)";
    $stmt_precos = $pdo->prepare($sql_precos);
    $stmt_precos->execute($ids_produtos);

    $produtos_db = $stmt_precos->fetchAll(PDO::FETCH_KEY_PAIR);

    foreach ($itens_carrinho as $item) {
        if (isset($produtos_db[$item->id])) {
            $valor_total += $produtos_db[$item->id] * $item->quantity;
        } else {
            throw new Exception("O produto com ID {$item->id} não foi encontrado ou está indisponível.");
        }
    }

    $pdo->beginTransaction();

    $sql_encomenda = "INSERT INTO encomendas (id_cliente, valor_total, status) VALUES (?, ?, 'nova') RETURNING id_encomenda";
    $stmt_encomenda = $pdo->prepare($sql_encomenda);
    $stmt_encomenda->execute([$id_cliente, $valor_total]);
    $id_encomenda = $stmt_encomenda->fetchColumn();

    $sql_itens = "INSERT INTO encomenda_itens (id_encomenda, id_produto, quantidade, preco_unitario) VALUES (?, ?, ?, ?)";
    $stmt_itens = $pdo->prepare($sql_itens);

    foreach ($itens_carrinho as $item) {
        $stmt_itens->execute([
            $id_encomenda,
            $item->id,
            $item->quantity,
            $produtos_db[$item->id]
        ]);
    }

    $pdo->commit();

    http_response_code(201);
    echo json_encode(['status' => 'success', 'message' => 'Encomenda realizada com sucesso!', 'id_encomenda' => $id_encomenda]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Não foi possível processar a encomenda: ' . $e->getMessage()]);
}
?>
