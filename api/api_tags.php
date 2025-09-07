<?php
// ARQUIVO NOVO: api/api_tags.php
require 'config_pdo.php'; // Usa a conexão PDO

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Listar todas as tags
            $stmt = $pdo->prepare("SELECT id_tag, nome FROM tags ORDER BY nome ASC");
            $stmt->execute();
            $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($tags);
            break;

        case 'POST':
            // Criar uma nova tag
            $data = json_decode(file_get_contents("php://input"));
            if (empty($data->nome)) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'O nome da tag é obrigatório.']);
                exit();
            }
            $stmt = $pdo->prepare("INSERT INTO tags (nome) VALUES (?)");
            $stmt->execute([$data->nome]);
            echo json_encode(['status' => 'success', 'message' => 'Tag criada com sucesso.']);
            break;

        case 'PUT':
            // Atualizar uma tag
            $data = json_decode(file_get_contents("php://input"));
             if (empty($data->id_tag) || empty($data->nome)) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'ID e nome da tag são obrigatórios.']);
                exit();
            }
            $stmt = $pdo->prepare("UPDATE tags SET nome = ? WHERE id_tag = ?");
            $stmt->execute([$data->nome, $data->id_tag]);
            echo json_encode(['status' => 'success', 'message' => 'Tag atualizada com sucesso.']);
            break;

        case 'DELETE':
            // Deletar uma tag
            $data = json_decode(file_get_contents("php://input"));
            if (empty($data->id_tag)) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'O ID da tag é obrigatório.']);
                exit();
            }

            $pdo->beginTransaction();

            // Desassociar a tag de todos os produtos
            $stmt_unassoc = $pdo->prepare("DELETE FROM produto_tags WHERE id_tag = ?");
            $stmt_unassoc->execute([$data->id_tag]);

            // Excluir a tag
            $stmt_delete = $pdo->prepare("DELETE FROM tags WHERE id_tag = ?");
            $stmt_delete->execute([$data->id_tag]);
            
            $pdo->commit();

            echo json_encode(['status' => 'success', 'message' => 'Tag removida com sucesso.']);
            break;

        default:
            http_response_code(405);
            echo json_encode(['message' => 'Método não permitido.']);
            break;
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    // Verifica se é erro de chave única (tag duplicada)
    if ($e->getCode() == '23505') {
         echo json_encode(['status' => 'error', 'message' => 'Já existe uma tag com este nome.']);
    } else {
         echo json_encode(['status' => 'error', 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
    }
}
?>
