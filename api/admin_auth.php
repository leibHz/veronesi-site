<?php
// api/admin_auth.php
require_once '../db_connect.php';
session_start();

$action = $_GET['action'] ?? '';

if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    $remember = isset($_POST['remember']);

    try {
        $stmt = $pdo->prepare("SELECT * FROM funcionarios WHERE email = ? AND ativo = TRUE");
        $stmt->execute([$email]);
        $funcionario = $stmt->fetch();

        if ($funcionario && password_verify($senha, $funcionario['senha'])) {
            // Busca as permissões do funcionário
            $stmt_perms = $pdo->prepare("SELECT p.nome FROM permissoes p JOIN funcionario_permissoes fp ON p.id = fp.permissao_id WHERE fp.funcionario_id = ?");
            $stmt_perms->execute([$funcionario['id']]);
            $permissoes = $stmt_perms->fetchAll(PDO::FETCH_COLUMN);

            // Armazena tudo na sessão
            $_SESSION['funcionario_id'] = $funcionario['id'];
            $_SESSION['funcionario_nome'] = $funcionario['nome_completo'];
            $_SESSION['funcionario_permissoes'] = $permissoes;

            if ($remember) {
                $token = bin2hex(random_bytes(32));
                $stmt_token = $pdo->prepare("UPDATE funcionarios SET remember_token = ? WHERE id = ?");
                $stmt_token->execute([$token, $funcionario['id']]);
                setcookie('remember_me', $token, time() + (86400 * 30), "/"); 
            }

            header('Location: ../admin/index.php');
            exit;
        } else {
            throw new Exception('E-mail ou senha incorretos.');
        }
    } catch (Exception $e) {
        header('Location: ../admin/login.php?error=' . urlencode($e->getMessage()));
        exit;
    }
} elseif ($action === 'logout') {
    if (isset($_SESSION['funcionario_id'])) {
        $stmt = $pdo->prepare("UPDATE funcionarios SET remember_token = NULL WHERE id = ?");
        $stmt->execute([$_SESSION['funcionario_id']]);
    }
    session_destroy();
    setcookie('remember_me', '', time() - 3600, "/");
    header('Location: ../admin/login.php');
    exit;
}
?>