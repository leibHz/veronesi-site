<?php
// db_connect.php

// --- Configurações do Banco de Dados ---
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '5008');
define('DB_NAME', 'supermercado_veronesi');

// --- Opções do PDO ---
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// --- Criação da Conexão ---
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // Em caso de falha, a API que incluiu este arquivo irá tratar o erro.
    // Isso evita que este script envie uma resposta de erro por conta própria.
    // Lançamos a exceção para que o script chamador possa capturá-la.
    throw new PDOException($e->getMessage(), (int)$e->getCode());
}
?>
