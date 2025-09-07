<?php
// ARQUIVO: api/config_pdo.php (CORRIGIDO E OTIMIZADO)
// Este arquivo agora centraliza a criação da conexão com o banco de dados (PDO).
// Outros scripts que precisam de transações (como criar encomenda) apenas incluem este arquivo.

// Previne a inclusão múltipla, que poderia causar erros.
if (defined('CONFIG_PDO_INCLUDED')) {
    return;
}
define('CONFIG_PDO_INCLUDED', true);

// Credenciais de conexão para o Supabase via Pooler (IPv4)
$host = 'aws-1-sa-east-1.pooler.supabase.com'; 
$dbname = 'postgres';
$user = 'postgres.atlevvcnquxtczsksuyv';
$password = 'Wz91cHlYYFSMLMdy'; 
$port = '5432';

// DSN (Data Source Name) para a conexão PostgreSQL
$dsn = "pgsql:host=$host;port=$port;dbname=$dbname;user=$user;password=$password";

try {
    // Cria a instância do PDO
    $pdo = new PDO($dsn);
    
    // Define o modo de erro para lançar exceções, facilitando a depuração.
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    // Se a conexão falhar, interrompe o script e retorna um erro JSON padronizado.
    // Isso evita expor detalhes sensíveis do erro.
    header('Content-Type: application/json; charset=UTF-8');
    http_response_code(503); // 503 Service Unavailable
    echo json_encode([
        'status' => 'error',
        'message' => 'Erro crítico: Não foi possível conectar ao serviço de banco de dados.'
        // Em modo de desenvolvimento, você poderia adicionar: 'debug_info' => $e->getMessage()
    ]);
    exit(); // Garante que nenhum outro código seja executado.
}
?>