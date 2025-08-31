<?php
// ARQUIVO: api/api_cliente_cadastro.php
require 'config.php';

// --- INCLUSÃO DO PHPMailer ---
// É necessário instalar o PHPMailer via Composer: `composer require phpmailer/phpmailer`
// e depois executar `composer install` na pasta do projeto.
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->nome_completo) || !isset($data->email) || !isset($data->senha)) {
    http_response_code(400);
    echo json_encode(['message' => 'Todos os campos são obrigatórios.']);
    exit();
}

// 1. Verificar se o e-mail já existe
$check_endpoint = $supabase_url . '/rest/v1/clientes?select=email&email=eq.' . urlencode($data->email);
$ch_check = curl_init($check_endpoint);
$headers = ['apikey: ' . $supabase_secret_key, 'Authorization: Bearer ' . $supabase_secret_key];
curl_setopt($ch_check, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch_check, CURLOPT_HTTPHEADER, $headers);
$response_check = curl_exec($ch_check);
curl_close($ch_check);

if (count(json_decode($response_check, true)) > 0) {
    http_response_code(409); // Conflict
    echo json_encode(['message' => 'Este e-mail já está cadastrado.']);
    exit();
}

// 2. Gerar código de verificação
$codigo_verificacao = rand(100000, 999999);
$agora = new DateTime();
$agora->add(new DateInterval('PT15M')); // Código expira em 15 minutos
$codigo_expira_em = $agora->format('Y-m-d H:i:s');

// 3. Criar o novo cliente com o código
$senha_hash = password_hash($data->senha, PASSWORD_DEFAULT);

$insert_endpoint = $supabase_url . '/rest/v1/clientes';
$postData = [
    'nome_completo' => $data->nome_completo,
    'email' => $data->email,
    'senha_hash' => $senha_hash,
    'codigo_verificacao' => $codigo_verificacao,
    'codigo_expira_em' => $codigo_expira_em,
    'email_verificado' => false
];

$ch_insert = curl_init($insert_endpoint);
curl_setopt($ch_insert, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch_insert, CURLOPT_POST, true);
curl_setopt($ch_insert, CURLOPT_POSTFIELDS, json_encode($postData));
curl_setopt($ch_insert, CURLOPT_HTTPHEADER, array_merge($headers, ['Content-Type: application/json', 'Prefer: return=minimal']));

curl_exec($ch_insert);
$httpcode = curl_getinfo($ch_insert, CURLINFO_HTTP_CODE);
curl_close($ch_insert);

if ($httpcode == 201) { // Created
    // --- ENVIO REAL DE E-MAIL COM PHPMailer ---
    $mail = new PHPMailer(true);

    try {
        //Configurações do servidor (puxadas do config.php)
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';

        //Remetente e Destinatário
        $mail->setFrom(SMTP_USERNAME, 'Supermercado Veronesi');
        $mail->addAddress($data->email, $data->nome_completo);

        //Conteúdo do E-mail
        $mail->isHTML(true);
        $mail->Subject = 'Seu código de verificação - Supermercado Veronesi';
        $mail->Body    = "
            <div style='font-family: Arial, sans-serif; color: #333;'>
                <h2>Olá, {$data->nome_completo}!</h2>
                <p>Obrigado por se cadastrar no Supermercado Veronesi.</p>
                <p>Seu código de verificação é:</p>
                <p style='font-size: 24px; font-weight: bold; letter-spacing: 5px; background: #f0f0f0; padding: 10px; border-radius: 5px; text-align: center;'>
                    {$codigo_verificacao}
                </p>
                <p>Este código expira em 15 minutos.</p>
                <p>Atenciosamente,<br>Equipe Supermercado Veronesi</p>
            </div>
        ";
        $mail->AltBody = "Seu código de verificação é: {$codigo_verificacao}";

        $mail->send();
        
        http_response_code(201);
        echo json_encode(['status' => 'success', 'message' => 'Cadastro realizado! Enviamos um código para seu e-mail.']);

    } catch (Exception $e) {
        // Se o e-mail falhar, o ideal seria registrar o erro.
        http_response_code(500);
        echo json_encode(['message' => "Cadastro realizado, mas houve uma falha ao enviar o e-mail de verificação. Por favor, contate o suporte."]);
    }
} else {
    http_response_code(500);
    echo json_encode(['message' => 'Ocorreu um erro ao realizar o cadastro.']);
}
?>