<?php
// api/config.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../db_connect.php';

try {
    $stmt = $pdo->query("SELECT * FROM configuracoes_site WHERE id = 1");
    $config = $stmt->fetch();

    if ($config) {
        date_default_timezone_set('America/Sao_Paulo');
        $agora = time();
        $abertura = strtotime($config['horario_abertura']);
        $fechamento = strtotime($config['horario_fechamento']);
        
        $status_texto = "Fechado";
        $cor_status = "red";

        if ($config['status_manual'] === 'aberto_manual') {
            $status_texto = !empty($config['mensagem_status']) ? $config['mensagem_status'] : "Aberto";
            $cor_status = "green";
        } elseif ($config['status_manual'] === 'fechado_manual') {
            $status_texto = !empty($config['mensagem_status']) ? $config['mensagem_status'] : "Fechado";
            $cor_status = "red";
        } else {
            if ($agora >= $abertura && $agora <= $fechamento) {
                $status_texto = "Aberto agora";
                $cor_status = "green";
            }
        }
        
        $config['status_calculado'] = ['texto' => $status_texto, 'cor' => $cor_status];
    }

    echo json_encode($config);

} catch (PDOException $e) {
    http_response_code(500);
    error_log($e->getMessage());
    echo json_encode(['error' => 'Erro ao buscar configurações do site.']);
}
?>
