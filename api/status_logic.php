<?php
// ARQUIVO: api/status_logic.php
// Centraliza a lógica para determinar se a loja está aberta ou fechada.

/**
 * Calcula o status atual da loja com base nas configurações do banco de dados.
 *
 * @param object $config O objeto de configurações vindo da tabela 'configuracoes_site'.
 * @return array Um array contendo 'status_real' (bool) e 'mensagem_real' (string).
 */
function calcularStatusLoja($config) {
    // Define o fuso horário para São Paulo para garantir a consistência dos horários.
    date_default_timezone_set('America/Sao_Paulo');

    // Se o status manual foi forçado para 'aberto' ou 'fechado', ele tem prioridade máxima.
    if (isset($config->status_manual) && in_array($config->status_manual, ['aberto', 'fechado'])) {
        $estaAberto = $config->status_manual === 'aberto';
        // Usa a mensagem customizada se a loja for forçada a fechar, senão usa uma padrão.
        $mensagem = $estaAberto ? 'Loja aberta para encomendas!' : ($config->mensagem_status ?: 'Fechado no momento');
        return [
            'status_real' => $estaAberto,
            'mensagem_real' => $mensagem
        ];
    }

    // Caso contrário, usa a lógica automática baseada no horário.
    $agora = new DateTime();
    $diaDaSemana = (int)$agora->format('N'); // 1 (Segunda) a 7 (Domingo)
    $horario_hoje = null;

    if ($diaDaSemana >= 1 && $diaDaSemana <= 5) { // Segunda a Sexta
        $horario_hoje = $config->horario_seg_sex;
    } elseif ($diaDaSemana == 6) { // Sábado
        $horario_hoje = $config->horario_sab;
    } else { // Domingo ou Feriado (lógica simplificada, poderia ser expandida)
        // Por padrão, domingos e feriados estarão fechados, a menos que um horário seja definido.
        return [
            'status_real' => false,
            'mensagem_real' => 'Fechado aos Domingos e Feriados'
        ];
    }

    // Se não há horário definido ou está explicitamente 'Fechado'
    if (!$horario_hoje || strtolower(trim($horario_hoje)) === 'fechado' || !strpos($horario_hoje, ' - ')) {
        return [
            'status_real' => false,
            'mensagem_real' => 'Fechado no momento'
        ];
    }

    // Extrai os horários de abertura e fechamento
    try {
        list($horaAbre, $horaFecha) = array_map('trim', explode(' - ', $horario_hoje));
        $horarioAbertura = DateTime::createFromFormat('H:i', $horaAbre);
        $horarioFechamento = DateTime::createFromFormat('H:i', $horaFecha);

        // Verifica se o horário atual está dentro do intervalo de funcionamento
        if ($horarioAbertura && $horarioFechamento && $agora >= $horarioAbertura && $agora < $horarioFechamento) {
            return [
                'status_real' => true,
                'mensagem_real' => 'Loja aberta para encomendas!'
            ];
        } else {
             return [
                'status_real' => false,
                'mensagem_real' => 'Fechado no momento. Nosso horário hoje é das ' . $horaAbre . ' às ' . $horaFecha
            ];
        }
    } catch (Exception $e) {
        // Se o formato do horário for inválido
        return [
            'status_real' => false,
            'mensagem_real' => 'Fechado (horário não configurado)'
        ];
    }
}
?>
