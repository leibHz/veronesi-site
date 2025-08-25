<!-- admin/index.php -->
<?php
session_start();

// Se não houver sessão, tenta logar via cookie
if (!isset($_SESSION['funcionario_id']) && isset($_COOKIE['remember_me'])) {
    require_once '../db_connect.php';
    $token = $_COOKIE['remember_me'];
    $stmt = $pdo->prepare("SELECT * FROM funcionarios WHERE remember_token = ? AND ativo = TRUE");
    $stmt->execute([$token]);
    $funcionario = $stmt->fetch();

    if ($funcionario) {
        // Token válido, cria a sessão
        $_SESSION['funcionario_id'] = $funcionario['id'];
        $_SESSION['funcionario_nome'] = $funcionario['nome_completo'];
        
        // Adiciona busca de permissões
        $stmt_perms = $pdo->prepare("SELECT p.nome FROM permissoes p JOIN funcionario_permissoes fp ON p.id = fp.permissao_id WHERE fp.funcionario_id = ?");
        $stmt_perms->execute([$funcionario['id']]);
        $_SESSION['funcionario_permissoes'] = $stmt_perms->fetchAll(PDO::FETCH_COLUMN);
    } else {
        // Token inválido, limpa o cookie
        setcookie('remember_me', '', time() - 3600, "/");
    }
}

// Se, mesmo após a verificação do cookie, não houver sessão, redireciona para o login.
if (!isset($_SESSION['funcionario_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo - Supermercado Veronesi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <!-- Bibliotecas para o Gerador de Crachá -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
</head>
<body class="bg-gray-100 font-sans flex">
    <!-- BARRA LATERAL DE NAVEGAÇÃO -->
    <aside class="w-64 bg-gray-800 text-white flex flex-col fixed h-full">
        <div class="p-4 border-b border-gray-700">
            <h1 class="text-xl font-bold">Veronesi Admin</h1>
            <span class="text-sm text-gray-400">Bem-vindo, <?php echo htmlspecialchars(explode(' ', $_SESSION['funcionario_nome'])[0]); ?></span>
        </div>
        <nav id="sidebar-nav" class="flex-grow p-2">
            <a href="#dashboard" class="nav-link active">
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" /></svg>
                <span>Dashboard</span>
            </a>
            <a href="#produtos" class="nav-link" data-permission="gerenciar_produtos">
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zM11 13a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>
                <span>Produtos</span>
            </a>
            <a href="#encomendas" class="nav-link" data-permission="gerenciar_encomendas">
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" /><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd" /></svg>
                <span>Encomendas</span>
            </a>
            <a href="#funcionarios" class="nav-link" data-permission="gerenciar_funcionarios">
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" /></svg>
                <span>Funcionários</span>
            </a>
            <a href="#cracha" class="nav-link" data-permission="gerenciar_funcionarios">
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"/></svg>
                <span>Gerador de Crachá</span>
            </a>
            <a href="#configuracoes" class="nav-link" data-permission="gerenciar_site">
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd" /></svg>
                <span>Configurações</span>
            </a>
        </nav>
        <div class="p-4 mt-auto border-t border-gray-700">
            <a href="../api/admin_auth.php?action=logout" class="flex items-center gap-2 text-red-400 hover:text-red-300">
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 102 0V4a1 1 0 00-1-1zm10.293 9.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L14.586 9H7a1 1 0 100 2h7.586l-1.293 1.293z" clip-rule="evenodd" /></svg>
                <span>Sair</span>
            </a>
        </div>
    </aside>

    <!-- CONTEÚDO PRINCIPAL -->
    <main id="main-content" class="ml-64 flex-grow p-8">
        <!-- O conteúdo das seções será carregado aqui -->
    </main>
    
    <script src="script.js"></script>
</body>
</html>