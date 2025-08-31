<?php
// ARQUIVO: api/api_admin_produtos.php (ATUALIZADO)
// Adicionado suporte a PDO para transações, gestão de promoções e tags.
require 'config_pdo.php'; // Usa a conexão PDO para transações
require 'config.php'; // Ainda necessário para a URL do Supabase (para GET)

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

$method = $_SERVER['REQUEST_METHOD'];

// --- GET (com cURL, pois é mais simples para joins complexos no Supabase REST)
if ($method === 'GET') {
    $context = stream_context_create(['http' => ['header' => "apikey: $supabase_secret_key\r\nAuthorization: Bearer $supabase_secret_key\r\n"]]);
    
    // Buscar sessões
    if (isset($_GET['sessoes'])) {
        $endpoint = $supabase_url . '/rest/v1/sessoes?select=id_sessao,nome';
        echo file_get_contents($endpoint, false, $context);
        exit();
    }
    
    // Buscar um único produto
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        // ATUALIZADO: Traz as tags junto com o produto
        $endpoint = $supabase_url . '/rest/v1/produtos?id_produto=eq.' . $id . '&select=*,tags(nome)&limit=1';
        $response = file_get_contents($endpoint, false, $context);
        $products = json_decode($response);
        echo json_encode($products[0] ?? null);
        exit();
    }
    
    // Buscar todos os produtos
    // ATUALIZADO: Traz a sessão e as tags
    $endpoint = $supabase_url . '/rest/v1/produtos?select=*,sessao:sessoes(nome),tags(nome)&order=nome.asc';
    echo file_get_contents($endpoint, false, $context);
    exit();
}

// --- DELETE (com PDO para simplicidade)
if ($method === 'DELETE') {
    $data = json_decode(file_get_contents("php://input"));
    $id = $data->id_produto ?? null;

    if (!$id) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'ID do produto não fornecido.']);
        exit();
    }
    
    try {
        $pdo->beginTransaction();
        // Remove primeiro as associações de tags
        $stmt_tags = $pdo->prepare("DELETE FROM produto_tags WHERE id_produto = ?");
        $stmt_tags->execute([$id]);
        
        // Remove o produto
        $stmt_prod = $pdo->prepare("DELETE FROM produtos WHERE id_produto = ?");
        $stmt_prod->execute([$id]);
        
        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'Produto removido com sucesso.']);

    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Erro ao remover o produto: ' . $e->getMessage()]);
    }
    exit();
}

// --- POST (CRIAR/ATUALIZAR com PDO para transações)
if ($method === 'POST') {
    try {
        $data = $_POST;
        $id_produto = $data['id_produto'] ?: null;
        $imagem_path = $data['imagem_atual'] ?? null;

        if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] == 0) {
            $target_dir = "../uploads/";
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            $filename = uniqid() . '-' . basename($_FILES["imagem"]["name"]);
            $target_file = $target_dir . $filename;
            if (move_uploaded_file($_FILES["imagem"]["tmp_name"], $target_file)) {
                $imagem_path = "uploads/" . $filename;
            }
        }

        $pdo->beginTransaction();

        // CORREÇÃO: Converte os valores dos checkboxes para strings 'true'/'false'
        // para garantir a compatibilidade com o driver PDO do PostgreSQL.
        $disponivel = (isset($data['disponivel']) && $data['disponivel'] === 'on') ? 'true' : 'false';
        $em_promocao = (isset($data['em_promocao']) && $data['em_promocao'] === 'on') ? 'true' : 'false';

        // 1. INSERIR ou ATUALIZAR o produto
        if ($id_produto) { // Atualizar
            $sql = "UPDATE produtos SET nome = ?, preco = ?, id_sessao = ?, descricao = ?, unidade_medida = ?, disponivel = ?, em_promocao = ?, imagem_url = ? WHERE id_produto = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $data['nome'], $data['preco'], $data['id_sessao'], $data['descricao'],
                $data['unidade_medida'],
                $disponivel,
                $em_promocao,
                $imagem_path, $id_produto
            ]);
        } else { // Inserir
            $sql = "INSERT INTO produtos (nome, preco, id_sessao, descricao, unidade_medida, disponivel, em_promocao, imagem_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?) RETURNING id_produto";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $data['nome'], $data['preco'], $data['id_sessao'], $data['descricao'],
                $data['unidade_medida'],
                $disponivel,
                $em_promocao,
                $imagem_path
            ]);
            $id_produto = $stmt->fetchColumn();
        }

        // 2. GERENCIAR TAGS
        if (isset($data['tags']) && !empty($id_produto)) {
            // Limpa as tags antigas do produto
            $stmt_delete_tags = $pdo->prepare("DELETE FROM produto_tags WHERE id_produto = ?");
            $stmt_delete_tags->execute([$id_produto]);

            $tags_array = array_filter(array_map('trim', explode(',', $data['tags'])));
            
            if (!empty($tags_array)) {
                foreach ($tags_array as $tag_nome) {
                    // Verifica se a tag já existe
                    $stmt_find_tag = $pdo->prepare("SELECT id_tag FROM tags WHERE nome = ?");
                    $stmt_find_tag->execute([$tag_nome]);
                    $id_tag = $stmt_find_tag->fetchColumn();

                    // Se não existe, cria a tag
                    if (!$id_tag) {
                        $stmt_create_tag = $pdo->prepare("INSERT INTO tags (nome) VALUES (?) RETURNING id_tag");
                        $stmt_create_tag->execute([$tag_nome]);
                        $id_tag = $stmt_create_tag->fetchColumn();
                    }

                    // Associa a tag ao produto
                    $stmt_assoc_tag = $pdo->prepare("INSERT INTO produto_tags (id_produto, id_tag) VALUES (?, ?)");
                    $stmt_assoc_tag->execute([$id_produto, $id_tag]);
                }
            }
        }
        
        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'Produto salvo com sucesso.']);

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Erro ao salvar o produto: ' . $e->getMessage()]);
    }
    exit();
}
?>