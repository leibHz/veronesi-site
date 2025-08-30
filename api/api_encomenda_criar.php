<?php
// ARQUIVO: api/api_encomenda_criar.php (REESTRUTURADO E CORRIGIDO)
// Lógica de criação de encomenda separada da configuração do banco de dados.

// Inclui a conexão PDO já pronta do arquivo de configuração.
require 'config_pdo.php'; 

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Pega os dados JSON enviados pelo frontend.
$data = json_decode(file_get_contents("php://input"));

// Validação inicial dos dados recebidos.
if (!isset($data->id_cliente) || !isset($data->itens) || empty($data->itens)) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'Dados da encomenda inválidos ou incompletos.']);
    exit();
}

$id_cliente = $data->id_cliente;
$itens_carrinho = $data->itens;
$valor_total = 0;

try {
    // 1. BUSCAR PREÇOS REAIS DO BANCO DE DADOS
    // Isso previne que um usuário mal-intencionado manipule o preço no frontend.
    $ids_produtos = array_map(function($item) { return $item->id; }, $itens_carrinho);
    
    if (empty($ids_produtos)) {
        throw new Exception("O carrinho não contém itens válidos.");
    }

    // Cria os placeholders (?) para a consulta SQL de forma segura.
    $placeholders = implode(',', array_fill(0, count($ids_produtos), '?'));
    
    $sql_precos = "SELECT id_produto, preco FROM produtos WHERE id_produto IN ($placeholders)";
    $stmt_precos = $pdo->prepare($sql_precos);
    $stmt_precos->execute($ids_produtos);

    // Cria um array associativo [id_produto => preco] para fácil acesso.
    $produtos_db = $stmt_precos->fetchAll(PDO::FETCH_KEY_PAIR);

    // Calcula o valor total com base nos preços do banco de dados.
    foreach ($itens_carrinho as $item) {
        if (isset($produtos_db[$item->id])) {
            $valor_total += $produtos_db[$item->id] * $item->quantity;
        } else {
            // Se um produto do carrinho não for encontrado no DB, a encomenda é inválida.
            throw new Exception("O produto com ID {$item->id} não foi encontrado ou está indisponível.");
        }
    }

    // 2. INICIAR TRANSAÇÃO
    // Uma transação garante que todas as operações (inserir encomenda e seus itens)
    // ocorram com sucesso, ou nenhuma delas ocorre. Isso mantém a consistência dos dados.
    $pdo->beginTransaction();

    // 3. INSERIR NA TABELA 'encomendas'
    // RETURNING id_encomenda retorna o ID da nova encomenda criada.
    $sql_encomenda = "INSERT INTO encomendas (id_cliente, valor_total, status) VALUES (?, ?, 'nova') RETURNING id_encomenda";
    $stmt_encomenda = $pdo->prepare($sql_encomenda);
    $stmt_encomenda->execute([$id_cliente, $valor_total]);
    $id_encomenda = $stmt_encomenda->fetchColumn();

    // 4. INSERIR ITENS NA TABELA 'encomenda_itens'
    $sql_itens = "INSERT INTO encomenda_itens (id_encomenda, id_produto, quantidade, preco_unitario) VALUES (?, ?, ?, ?)";
    $stmt_itens = $pdo->prepare($sql_itens);

    foreach ($itens_carrinho as $item) {
        $stmt_itens->execute([
            $id_encomenda,
            $item->id,
            $item->quantity,
            $produtos_db[$item->id] // Usa o preço que veio do banco
        ]);
    }

    // 5. EFETIVAR A TRANSAÇÃO
    // Se tudo correu bem, o commit() salva permanentemente as alterações no banco.
    $pdo->commit();

    // Retorna uma resposta de sucesso para o frontend.
    http_response_code(201); // Created
    echo json_encode(['status' => 'success', 'message' => 'Encomenda realizada com sucesso!', 'id_encomenda' => $id_encomenda]);

} catch (Exception $e) {
    // Se qualquer erro ocorrer, desfaz a transação com rollback().
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // Retorna uma mensagem de erro genérica.
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => 'Não foi possível processar a encomenda: ' . $e->getMessage()]);
}
?>
