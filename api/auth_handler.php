<?php
// api/auth_handler.php
session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../db_connect.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);

        switch ($action) {
            case 'register':
                $nome_completo = $data['nome_completo'] ?? '';
                $email = $data['email'] ?? '';
                $senha = $data['senha'] ?? '';

                if (empty($nome_completo) || empty($email) || empty($senha)) { throw new Exception('Todos os campos são obrigatórios.'); }
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { throw new Exception('Formato de e-mail inválido.'); }

                $stmt = $pdo->prepare("SELECT id FROM clientes WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) { throw new Exception('Este e-mail já está cadastrado.'); }

                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                $sql = "INSERT INTO clientes (nome_completo, email, senha) VALUES (?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$nome_completo, $email, $senha_hash]);
                echo json_encode(['success' => true, 'message' => 'Cadastro realizado com sucesso!']);
                break;

            case 'login':
                $email = $data['email'] ?? '';
                $senha = $data['senha'] ?? '';
                $stmt = $pdo->prepare("SELECT * FROM clientes WHERE email = ?");
                $stmt->execute([$email]);
                $cliente = $stmt->fetch();

                if ($cliente && password_verify($senha, $cliente['senha'])) {
                    $_SESSION['cliente_id'] = $cliente['id'];
                    $_SESSION['cliente_nome'] = $cliente['nome_completo'];
                    echo json_encode(['success' => true, 'message' => 'Login bem-sucedido!', 'cliente' => ['id' => $cliente['id'], 'nome' => $cliente['nome_completo']]]);
                } else {
                    throw new Exception('E-mail ou senha incorretos.');
                }
                break;

            case 'logout':
                session_destroy();
                echo json_encode(['success' => true, 'message' => 'Logout realizado com sucesso.']);
                break;
            
            default:
                http_response_code(400);
                echo json_encode(['error' => 'Ação POST inválida.']);
                break;
        }
    } elseif ($method === 'GET' && $action === 'check_session') {
        if (isset($_SESSION['cliente_id'])) {
            echo json_encode(['loggedIn' => true, 'cliente' => ['id' => $_SESSION['cliente_id'], 'nome' => $_SESSION['cliente_nome']]]);
        } else {
            echo json_encode(['loggedIn' => false]);
        }
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Método não permitido.']);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
