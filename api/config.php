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

?>