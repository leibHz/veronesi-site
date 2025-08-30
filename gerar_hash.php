<?php
// Defina a senha que você quer usar para o administrador aqui, devo apagar isso depois
$senhaParaAdmin = 'admin123'; 

$hash = password_hash($senhaParaAdmin, PASSWORD_DEFAULT);

echo 'Seu hash de senha é: <br><br>';
echo '<b>' . $hash . '</b>';
?>