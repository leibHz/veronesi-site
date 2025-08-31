<?php
// ARQUIVO: api/config.php (MODIFICADO PARA USAR VARIÁVEIS DE AMBIENTE)
// -----------------------------------------------------------------
// As chaves de API e senhas foram removidas deste arquivo.
// Agora, o sistema lê essas informações das variáveis de ambiente do servidor,
// o que é uma prática de segurança muito mais robusta.

// URL do seu projeto Supabase (pode continuar aqui, não é um segredo)
$supabase_url = 'https://atlevvcnquxtczsksuyv.supabase.co';

// Chave pública do Supabase (pode continuar aqui, é segura para exposição no frontend)
$supabase_publishable_key = 'sb_publishable_ZNV55bP_klSoZ6mB92YopQ_TIHjpRYO';

// --- LEITURA DAS CHAVES SECRETAS DAS VARIÁVEIS DE AMBIENTE ---

// getenv() busca a variável de ambiente correspondente.
// O operador '?:' define um valor padrão caso a variável não seja encontrada,
// prevenindo erros, mas em produção, elas DEVEM estar definidas.
$supabase_secret_key = getenv('SUPABASE_SECRET_KEY') ?: '';

// --- CONFIGURAÇÃO DE E-MAIL (PHPMailer) LENDO DE VARIÁVEIS DE AMBIENTE ---
define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.gmail.com');
define('SMTP_USERNAME', getenv('SMTP_USERNAME') ?: '');
define('SMTP_PASSWORD', getenv('SMTP_PASSWORD') ?: '');
define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
define('SMTP_SECURE', getenv('SMTP_SECURE') ?: 'tls');

?>
