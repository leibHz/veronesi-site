<?php
// ARQUIVO: api/api_cliente_cadastro.php (VERSÃO HEREDOC)

// Força a exibição de todos os erros de PHP
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header("Access-control-allow-origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

try {
    $data = json_decode(file_get_contents("php://input"));

    if (!isset($data->nome_completo) || !isset($data->email) || !isset($data->senha)) {
        http_response_code(400);
        echo json_encode(['message' => 'Todos os campos são obrigatórios.']);
        exit();
    }

    $check_endpoint = $supabase_url . '/rest/v1/clientes?select=email&email=eq.' . urlencode($data->email);
    $ch_check = curl_init($check_endpoint);
    $headers = ['apikey: ' . $supabase_secret_key, 'Authorization: Bearer ' . $supabase_secret_key];
    curl_setopt($ch_check, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch_check, CURLOPT_HTTPHEADER, $headers);
    $response_check = curl_exec($ch_check);
    curl_close($ch_check);

    if (count(json_decode($response_check, true)) > 0) {
        http_response_code(409);
        echo json_encode(['message' => 'Este e-mail já está cadastrado.']);
        exit();
    }

    $codigo_verificacao = rand(100000, 999999);
    $agora = new DateTime();
    $agora->add(new DateInterval('PT15M'));
    $codigo_expira_em = $agora->format('Y-m-d H:i:s');
    $senha_hash = password_hash($data->senha, PASSWORD_DEFAULT);

    $insert_endpoint = $supabase_url . '/rest/v1/clientes';
    $postData = [
        'nome_completo' => $data->nome_completo,
        'email' => $data->email,
        'senha_hash' => $senha_hash,
        'codigo_verificacao' => (string)$codigo_verificacao,
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

    if ($httpcode == 201) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USERNAME;
            $mail->Password   = SMTP_PASSWORD;
            $mail->SMTPSecure = SMTP_SECURE === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = SMTP_PORT;
            $mail->CharSet    = 'UTF-8';
            $mail->setFrom(SMTP_USERNAME, 'Supermercado Veronesi');
            $mail->addAddress($data->email, $data->nome_completo);
            $mail->isHTML(true);
            $mail->Subject = 'Seu código de verificação - Supermercado Veronesi';
            
            // --- CORREÇÃO: Usando a sintaxe Heredoc para o corpo do e-mail ---
            $nomeCliente = $data->nome_completo;
            $mail->Body = <<<HTML
                <div style='font-family: Arial, sans-serif; color: #333;'>
                    <h2>Olá, {$nomeCliente}!</h2>
                    <p>Seu código de verificação é: <strong>{$codigo_verificacao}</strong></p>
                </div>
HTML;

            $mail->AltBody = "Seu código de verificação é: {$codigo_verificacao}";
            $mail->send();
            
            http_response_code(201);
            echo json_encode(['status' => 'success', 'message' => 'Cadastro realizado! Enviamos um código para seu e-mail.']);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['message' => "Cadastro salvo, mas houve uma falha ao enviar o e-mail de verificação.", 'error_details' => $mail->ErrorInfo]);
        }
    } else {
        throw new Exception('Ocorreu um erro ao salvar o cadastro no banco de dados.');
    }

} catch (Throwable $th) {
    http_response_code(500);
    echo json_encode([
        'message' => 'Ocorreu um erro crítico no servidor.',
        'error_details' => $th->getMessage(),
        'file' => $th->getFile(),
        'line' => $th->getLine()
    ]);
}
?>