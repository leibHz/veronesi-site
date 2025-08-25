<?php
// api/produtos.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../db_connect.php';

$action = $_GET['action'] ?? 'get_products';

try {
    if ($action === 'get_products') {
        $sql = "SELECT p.*, s.nome AS sessao_nome FROM produtos p LEFT JOIN sessoes s ON p.sessao_id = s.id";
        $whereClauses = [];
        $params = [];

        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $whereClauses[] = "p.nome LIKE ?";
            $params[] = '%' . $_GET['search'] . '%';
        }
        if (isset($_GET['sessao_id']) && is_numeric($_GET['sessao_id'])) {
            $whereClauses[] = "p.sessao_id = ?";
            $params[] = (int)$_GET['sessao_id'];
        }
        if (isset($_GET['promocao']) && $_GET['promocao'] === 'true') {
            $whereClauses[] = "p.em_promocao = TRUE";
        }
        if (isset($_GET['tag_id']) && is_numeric($_GET['tag_id'])) {
            $sql .= " JOIN produto_tags pt ON p.id = pt.produto_id";
            $whereClauses[] = "pt.tag_id = ?";
            $params[] = (int)$_GET['tag_id'];
        }

        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(' AND ', $whereClauses);
        }

        $orderBy = " ORDER BY p.nome ASC";
        if (isset($_GET['sort'])) {
            switch ($_GET['sort']) {
                case 'preco_asc': $orderBy = " ORDER BY p.preco ASC"; break;
                case 'preco_desc': $orderBy = " ORDER BY p.preco DESC"; break;
                case 'nome_desc': $orderBy = " ORDER BY p.nome DESC"; break;
            }
        }
        $sql .= $orderBy;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode($stmt->fetchAll());

    } elseif ($action === 'fetch_tags') { // NOME DA AÇÃO ALTERADO AQUI
        $sql = "SELECT t.id, t.nome FROM tags t JOIN produto_tags pt ON t.id = pt.tag_id GROUP BY t.id ORDER BY t.nome ASC";
        $stmt = $pdo->query($sql);
        
        $tags = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        echo json_encode($tags);
    }

} catch (PDOException $e) {
    http_response_code(500);
    error_log($e->getMessage());
    echo json_encode(['error' => 'Erro ao processar requisição de produtos.']);
}
?>