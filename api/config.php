<?php
// ARQUIVO: api/config.php
// -----------------------------------------------------------------
// Este arquivo agora contém as configurações para a API REST do Supabase.
// A chave secreta (service_role) é usada aqui para operações de backend,
// pois ela pode contornar as políticas de segurança de linha (RLS),
// o que é necessário para um servidor de aplicação confiável.

// URL do seu projeto Supabase
$supabase_url = 'https://atlevvcnquxtczsksuyv.supabase.co';

$supabase_publishable_key = 'sb_publishable_ZNV55bP_klSoZ6mB92YopQ_TIHjpRYO';

// Chave de API Secreta (Service Role) - Mantenha esta chave segura e nunca a exponha no frontend.
$supabase_secret_key = 'sb_secret_kntnItGKTuNhJkEBPZmOkw_HWPTTf3D'; 

// IMPORTANTE: Substitua os valores abaixo pelas suas credenciais de e-mail reais.
// --- CONFIGURAÇÃO DE E-MAIL (PHPMailer) ---
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USERNAME', 'mercadoveronesi.naoresponda@gmail.com'); // Seu endereço de e-mail do Gmail
define('SMTP_PASSWORD', 'dvqhczsarycfykpt'); // A senha que você vai gerar no passo 2
define('SMTP_PORT', 587); // Porta para TLS
define('SMTP_SECURE', 'tls'); // Segurança TLS

define('VAPID_PUBLIC_KEY', 'BL-VAB4fZOhyco0eMUvU1uUevvs0ctR5mSI-kRHrMLmyIS2BoUb4iGwZ_l2bCct8JdxwI5XMKqPoG2a_eA2UjBY');
define('VAPID_PRIVATE_KEY', 'onPs5lRzf6LNUf-udV8jF7YFWm3Ayum4KVTsESy-XG4');
?>