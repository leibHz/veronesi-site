<?php
// ARQUIVO: api/teste_conexao.php

echo "Tentando conectar ao banco de dados...<br>";

// Inclui o seu arquivo de configuração da conexão
require 'config_pdo.php';

// Se o script continuar até aqui sem erros, a conexão foi bem-sucedida.
// A variável $pdo foi criada com sucesso dentro do config_pdo.php.
if (isset($pdo)) {
    echo "<b>Conexão bem-sucedida!</b><br>";
    echo "A conexão com o banco de dados PostgreSQL foi estabelecida.";
    // Encerra a conexão
    $pdo = null;
} else {
    // Esta mensagem raramente aparecerá, pois o try-catch no config_pdo.php
    // provavelmente já terá interrompido o script com uma mensagem de erro JSON.
    echo "<b>Falha na conexão.</b> A variável \$pdo não foi criada.";
}
?>