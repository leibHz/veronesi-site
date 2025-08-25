<?php
// api/encomenda_handler.php
session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Apenas usuários logados podem acessar qualquer função deste handler
if (!isset($_SESSION['cliente_id'])) {
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'error' => 'Acesso negado. Você precisa estar logado.']);
    exit;
}

require_once '../db_connect.php';
$clienteId = $_SESSION['cliente_id'];

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    if ($method === 'GET' && $action === 'get_my_orders') {
        // 1. Busca todas as encomendas do cliente logado
        $sql_encomendas = "SELECT id, data_encomenda, status FROM encomendas WHERE cliente_id = ? ORDER BY data_encomenda DESC";
        $stmt_encomendas = $pdo->prepare($sql_encomendas);
        $stmt_encomendas->execute([$clienteId]);
        $encomendas = $stmt_encomendas->fetchAll();

        // 2. Prepara a consulta para buscar os itens de uma encomenda
        $sql_itens = "SELECT ei.quantidade, ei.preco_unitario, p.nome, p.imagem_url
                      FROM encomenda_itens ei
                      JOIN produtos p ON ei.produto_id = p.id
                      WHERE ei.encomenda_id = ?";
        $stmt_itens = $pdo->prepare($sql_itens);

        // 3. Para cada encomenda, busca seus itens e anexa ao resultado
        foreach ($encomendas as $key => $encomenda) {
            $stmt_itens->execute([$encomenda['id']]);
            $encomendas[$key]['itens'] = $stmt_itens->fetchAll();
        }

        echo json_encode($encomendas);

    } elseif ($method === 'POST' && $action === 'create_encomenda') {
        $data = json_decode(file_get_contents('php://input'), true);
        $cartItems = $data['cart'] ?? [];

        if (empty($cartItems)) {
            throw new Exception('O carrinho de encomendas está vazio.');
        }

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO encomendas (cliente_id, status) VALUES (?, 'nova')");
        $stmt->execute([$clienteId]);
        $encomendaId = $pdo->lastInsertId();

        $sqlInsertItem = "INSERT INTO encomenda_itens (encomenda_id, produto_id, quantidade, preco_unitario) VALUES (?, ?, ?, ?)";
        $stmtInsertItem = $pdo->prepare($sqlInsertItem);

        $sqlGetPrice = "SELECT preco FROM produtos WHERE id = ?";
        $stmtGetPrice = $pdo->prepare($sqlGetPrice);

        foreach ($cartItems as $item) {
            $stmtGetPrice->execute([$item['id']]);
            $produto = $stmtGetPrice->fetch();
            if (!$produto) { throw new Exception("Produto com ID {$item['id']} não encontrado."); }
            $precoAtual = $produto['preco'];
            $stmtInsertItem->execute([$encomendaId, $item['id'], $item['quantity'], $precoAtual]);
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Encomenda realizada com sucesso!', 'encomenda_id' => $encomendaId]);

    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Ação inválida.']);
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erro interno no servidor.', 'details' => $e->getMessage()]);
}
?>
