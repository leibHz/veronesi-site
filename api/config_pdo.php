<?php
// ARQUIVO: api/config_pdo.php (MODIFICADO PARA USAR VARIÁVEIS DE AMBIENTE)
// A conexão com o banco de dados agora utiliza credenciais carregadas
// de forma segura a partir das variáveis de ambiente do servidor.

if (defined('CONFIG_PDO_INCLUDED')) {
    return;
}
define('CONFIG_PDO_INCLUDED', true);

// --- LEITURA DAS CREDENCIAIS DO BANCO DE DADOS DAS VARIÁVEIS DE AMBIENTE ---
$host = getenv('DB_HOST') ?: 'aws-1-sa-east-1.pooler.supabase.com'; 
$dbname = getenv('DB_NAME') ?: 'postgres';
$user = getenv('DB_USER') ?: '';
$password = getenv('DB_PASSWORD') ?: ''; 
$port = getenv('DB_PORT') ?: '5432';

// DSN (Data Source Name) para a conexão PostgreSQL
$dsn = "pgsql:host=$host;port=$port;dbname=$dbname;user=$user;password=$password";

try {
    $pdo = new PDO($dsn);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    header('Content-Type: application/json; charset=UTF-8');
    http_response_code(503); // 503 Service Unavailable
    echo json_encode([
        'status' => 'error',
        'message' => 'Erro crítico: Não foi possível conectar ao serviço de banco de dados.'
        // Em modo de desenvolvimento, você poderia adicionar: 'debug_info' => $e->getMessage()
    ]);
    exit();
}
?>
