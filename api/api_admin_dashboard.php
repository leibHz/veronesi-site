<?php
// ARQUIVO: api/api_admin_dashboard.php (ATUALIZADO)
// Utiliza PDO para uma contagem de registros mais confiável e direta.

require 'config_pdo.php'; // Usa a conexão PDO

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

try {
    // Função para contar registros em uma tabela usando a conexão PDO
    function getCount($pdo, $table) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM public.{$table}");
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    // Busca as contagens de cada tabela
    $total_produtos = getCount($pdo, 'produtos');
    $total_clientes = getCount($pdo, 'clientes');
    $total_encomendas = getCount($pdo, 'encomendas');

    // Monta o array de resposta
    $stats = [
        'total_produtos' => (int)$total_produtos,
        'total_clientes' => (int)$total_clientes,
        'total_encomendas' => (int)$total_encomendas
    ];

    // Retorna os dados em JSON
    http_response_code(200);
    echo json_encode($stats);

} catch (PDOException $e) {
    // Em caso de erro na consulta, retorna uma resposta de erro genérica
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Erro ao buscar estatísticas do banco de dados.',
        'debug_info' => $e->getMessage() // Opcional: para depuração
    ]);
}
?>