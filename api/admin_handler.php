<?php
// api/admin_handler.php

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['funcionario_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Acesso negado. Sessão inválida.']);
    exit;
}

require_once '../db_connect.php';

// Funções de verificação de permissão
function has_permission($permission) {
    return in_array($permission, $_SESSION['funcionario_permissoes'] ?? []);
}

function check_permission($permission) {
    if (!has_permission($permission)) {
        http_response_code(403);
        echo json_encode(['error' => 'Você não tem permissão para executar esta ação.']);
        exit;
    }
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    if ($method === 'GET') {
        switch ($action) {
            case 'get_dashboard_stats':
                $stats = [];
                $stats['total_produtos'] = $pdo->query("SELECT COUNT(*) FROM produtos")->fetchColumn();
                $stats['encomendas_novas'] = $pdo->query("SELECT COUNT(*) FROM encomendas WHERE status = 'nova'")->fetchColumn();
                $stats['total_clientes'] = $pdo->query("SELECT COUNT(*) FROM clientes")->fetchColumn();
                echo json_encode($stats);
                break;

            case 'get_site_config':
                echo json_encode($pdo->query("SELECT * FROM configuracoes_site WHERE id = 1")->fetch());
                break;

            case 'get_products':
                $sql = "SELECT p.*, s.nome AS sessao_nome, GROUP_CONCAT(t.nome SEPARATOR ', ') as tags
                        FROM produtos p 
                        LEFT JOIN sessoes s ON p.sessao_id = s.id
                        LEFT JOIN produto_tags pt ON p.id = pt.produto_id
                        LEFT JOIN tags t ON pt.tag_id = t.id
                        GROUP BY p.id
                        ORDER BY p.id DESC";
                echo json_encode($pdo->query($sql)->fetchAll());
                break;

            case 'get_sessoes':
                echo json_encode($pdo->query("SELECT id, nome FROM sessoes ORDER BY nome ASC")->fetchAll());
                break;

            case 'get_encomendas':
                $sql = "SELECT e.id, e.data_encomenda, e.status, c.nome_completo AS cliente_nome, SUM(ei.quantidade * ei.preco_unitario) AS valor_total, COUNT(ei.id) AS total_itens FROM encomendas e JOIN clientes c ON e.cliente_id = c.id JOIN encomenda_itens ei ON e.id = ei.encomenda_id GROUP BY e.id ORDER BY e.data_encomenda DESC";
                echo json_encode($pdo->query($sql)->fetchAll());
                break;

            case 'get_funcionarios':
                check_permission('gerenciar_funcionarios');
                $funcionarios = $pdo->query("SELECT id, nome_completo, email, cargo, ativo, data_contratacao FROM funcionarios ORDER BY nome_completo ASC")->fetchAll();
                $stmt_perms = $pdo->prepare("SELECT permissao_id FROM funcionario_permissoes WHERE funcionario_id = ?");
                foreach($funcionarios as $key => $func) {
                    $stmt_perms->execute([$func['id']]);
                    $funcionarios[$key]['permissoes'] = $stmt_perms->fetchAll(PDO::FETCH_COLUMN);
                }
                echo json_encode($funcionarios);
                break;

            case 'get_session_info':
                echo json_encode([
                    'nome' => $_SESSION['funcionario_nome'],
                    'permissoes' => $_SESSION['funcionario_permissoes']
                ]);
                break;
            
            case 'get_all_permissions':
                echo json_encode($pdo->query("SELECT * FROM permissoes ORDER BY nome ASC")->fetchAll());
                break;

            default:
                http_response_code(400);
                echo json_encode(['error' => 'Ação GET inválida.']);
                break;
        }
    } elseif ($method === 'POST') {
        $data = [];
        if ($action === 'add_product' || $action === 'update_product') {
            $data = $_POST;
        } else {
            $data = json_decode(file_get_contents('php://input'), true);
        }

        switch ($action) {
            case 'add_product':
            case 'update_product':
                $isEditing = ($action === 'update_product');
                $image_path = $data['existing_image_url'] ?? null;

                if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = '../uploads/';
                    if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }
                    $fileName = uniqid() . '-' . basename($_FILES['imagem']['name']);
                    $targetFile = $uploadDir . $fileName;
                    if (!move_uploaded_file($_FILES['imagem']['tmp_name'], $targetFile)) { throw new Exception("Erro ao mover o arquivo enviado."); }
                    $image_path = 'uploads/' . $fileName;
                }

                $pdo->beginTransaction();

                // CORREÇÃO: Usar o operador null coalescing (??) para evitar erros de "undefined index"
                $nome = $data['nome'] ?? 'Produto sem nome';
                $preco = $data['preco'] ?? 0.00;
                $estoque = $data['estoque'] ?? 0;
                $sessao_id = $data['sessao_id'] ?? null;
                $descricao = $data['descricao'] ?? '';
                $codigo_barras = $data['codigo_barras'] ?? null;
                $tags_string = $data['tags'] ?? '';

                if ($isEditing) {
                    $id = $data['id'] ?? null;
                    if (!$id) { throw new Exception("ID do produto é necessário para atualização."); }
                    $sql = "UPDATE produtos SET nome = ?, preco = ?, estoque = ?, sessao_id = ?, descricao = ?, codigo_barras = ?, imagem_url = ? WHERE id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$nome, $preco, $estoque, $sessao_id, $descricao, $codigo_barras, $image_path, $id]);
                    $productId = $id;
                } else {
                    $sql = "INSERT INTO produtos (nome, preco, estoque, sessao_id, descricao, codigo_barras, imagem_url) VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$nome, $preco, $estoque, $sessao_id, $descricao, $codigo_barras, $image_path]);
                    $productId = $pdo->lastInsertId();
                }

                $stmt_delete_tags = $pdo->prepare("DELETE FROM produto_tags WHERE produto_id = ?");
                $stmt_delete_tags->execute([$productId]);

                if (!empty($tags_string)) {
                    $tags = array_unique(array_filter(array_map('trim', explode(',', $tags_string))));
                    foreach ($tags as $tagName) {
                        $stmt_find_tag = $pdo->prepare("SELECT id FROM tags WHERE nome = ?");
                        $stmt_find_tag->execute([$tagName]);
                        $tagId = $stmt_find_tag->fetchColumn();
                        if (!$tagId) {
                            $stmt_insert_tag = $pdo->prepare("INSERT INTO tags (nome) VALUES (?)");
                            $stmt_insert_tag->execute([$tagName]);
                            $tagId = $pdo->lastInsertId();
                        }
                        $stmt_link_tag = $pdo->prepare("INSERT INTO produto_tags (produto_id, tag_id) VALUES (?, ?)");
                        $stmt_link_tag->execute([$productId, $tagId]);
                    }
                }

                $pdo->commit();
                echo json_encode(['success' => true, 'message' => 'Produto salvo com sucesso!']);
                break;
            
            case 'update_site_config':
                $sql = "UPDATE configuracoes_site SET horario_abertura = ?, horario_fechamento = ?, status_manual = ?, mensagem_status = ?, encomendas_ativas = ?, justificativa_encomendas = ? WHERE id = 1";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([ $data['horario_abertura'], $data['horario_fechamento'], $data['status_manual'], $data['mensagem_status'], $data['encomendas_ativas'], $data['justificativa_encomendas'] ]);
                echo json_encode(['success' => true, 'message' => 'Configurações do site atualizadas com sucesso!']);
                break;

            case 'delete_product':
                $sql = "DELETE FROM produtos WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$data['id']]);
                echo json_encode(['success' => true, 'message' => 'Produto deletado!']);
                break;

            case 'update_encomenda_status':
                $sql = "UPDATE encomendas SET status = ? WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$data['status'], $data['encomenda_id']]);
                echo json_encode(['success' => true, 'message' => 'Status da encomenda atualizado!']);
                break;

            case 'add_funcionario':
                check_permission('gerenciar_funcionarios');
                $senha_hash = password_hash($data['senha'], PASSWORD_DEFAULT);
                $pdo->beginTransaction();
                
                $sql = "INSERT INTO funcionarios (nome_completo, email, senha, cargo, ativo) VALUES (?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$data['nome_completo'], $data['email'], $senha_hash, $data['cargo'], $data['ativo']]);
                $id = $pdo->lastInsertId();

                if (!empty($data['permissoes'])) {
                    $stmt_insert = $pdo->prepare("INSERT INTO funcionario_permissoes (funcionario_id, permissao_id) VALUES (?, ?)");
                    foreach ($data['permissoes'] as $perm_id) {
                        $stmt_insert->execute([$id, $perm_id]);
                    }
                }

                $pdo->commit();
                echo json_encode(['success' => true, 'message' => 'Funcionário adicionado!']);
                break;

            case 'update_funcionario':
                check_permission('gerenciar_funcionarios');
                $pdo->beginTransaction();

                if (!empty($data['senha'])) {
                    $senha_hash = password_hash($data['senha'], PASSWORD_DEFAULT);
                    $sql = "UPDATE funcionarios SET nome_completo = ?, email = ?, cargo = ?, ativo = ?, senha = ? WHERE id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$data['nome_completo'], $data['email'], $data['cargo'], $data['ativo'], $senha_hash, $data['id']]);
                } else {
                    $sql = "UPDATE funcionarios SET nome_completo = ?, email = ?, cargo = ?, ativo = ? WHERE id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$data['nome_completo'], $data['email'], $data['cargo'], $data['ativo'], $data['id']]);
                }

                $stmt_delete = $pdo->prepare("DELETE FROM funcionario_permissoes WHERE funcionario_id = ?");
                $stmt_delete->execute([$data['id']]);

                if (!empty($data['permissoes'])) {
                    $stmt_insert = $pdo->prepare("INSERT INTO funcionario_permissoes (funcionario_id, permissao_id) VALUES (?, ?)");
                    foreach ($data['permissoes'] as $perm_id) {
                        $stmt_insert->execute([$data['id'], $perm_id]);
                    }
                }

                $pdo->commit();
                echo json_encode(['success' => true, 'message' => 'Funcionário atualizado!']);
                break;

            default:
                http_response_code(400);
                echo json_encode(['error' => 'Ação POST inválida.']);
                break;
        }
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    http_response_code(500);
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erro interno no servidor.', 'details' => $e->getMessage()]);
}
?>
