<?php
// ARQUIVO: api/load_env.php (NOVO)
// Este script é responsável por carregar a biblioteca phpdotenv
// e ler as variáveis do arquivo .env na raiz do projeto.

// Garante que o autoload do Composer seja incluído.
// O __DIR__ garante que o caminho seja sempre relativo a este arquivo.
require_once __DIR__ . '/../vendor/autoload.php';

try {
    // Cria uma instância do Dotenv, apontando para o diretório raiz do projeto (um nível acima de 'api').
    // O createImmutable garante que as variáveis não possam ser sobrescritas.
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    
    // Carrega as variáveis de ambiente do arquivo .env.
    // Se o arquivo não existir, ele vai lançar uma exceção que podemos tratar.
    $dotenv->load();

} catch (\Dotenv\Exception\InvalidPathException $e) {
    // Este erro acontece se o arquivo .env não for encontrado.
    // Em um ambiente de produção, as variáveis devem ser definidas no servidor,
    // então não encontrar o .env não é necessariamente um erro fatal lá.
    // Para desenvolvimento local, isso indica que o .env precisa ser criado.
    
    // Você pode adicionar um log de erro aqui se desejar.
    // error_log("Arquivo .env não encontrado. Carregando variáveis do ambiente do servidor. Detalhes: " . $e->getMessage());
}
