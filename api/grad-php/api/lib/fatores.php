<?php
/*
 * Leitura personalizada por aluno -- "onde ele tem mais dificuldade".
 * Versao da API unificada: funciona nas tres modalidades (presencial, ead, hibrido).
 *
 * Duas fontes:
 *   1. MOTIVO/MOTIVO2/MOTIVO3 na tabela de risco da modalidade -- gravados pelo
 *      JOB (heuristica "pior que a turma" sobre AVA + academico). Fonte principal.
 *      As tres tabelas ML_RISCO_* tem as colunas MOTIVO* e job gravando (o do
 *      hibrido e de um colega). Para somar uma modalidade nova: incluir na lista
 *      MODALIDADES_COM_MOTIVO quando a tabela dela tiver MOTIVO* preenchido.
 *   2. AO VIVO no banco -- FINANCEIRO (que o job do presencial nao calcula) e
 *      FALLBACK para quem nao tem motivo gravado (aluno fora da fila, fila antiga,
 *      ou modalidade sem motivo como o EAD hoje).
 *
 * DESCRITIVO (nao e a atribuicao exata do modelo), uso interno da equipe -- NUNCA
 * para o aluno (LGPD). Consultas ao vivo sao best-effort: falha -> fator omitido.
 */

declare(strict_types=1);

require_once __DIR__ . '/fila.php';   // consultar(), tabela(), modalidade(), Banco, ErroBanco

// Modalidades cuja tabela de risco JA tem MOTIVO* preenchido pelo job.
const MODALIDADES_COM_MOTIVO = ['presencial', 'ead', 'hibrido'];

const FATOR_AVA_ALTO = 21;    // dias sem acessar o AVA -> sinal alto (fallback ao vivo)
const FATOR_AVA_MEDIO = 8;    // 8..21 dias -> sinal medio
const AVA_INICIO_PROD = '2025-08-01';   // o log so e dado de producao a partir daqui

/**
 * Motivos gravados pelo job na fila daquele dia (MOTIVO, MOTIVO2, MOTIVO3).
 * So consulta se a modalidade tiver as colunas -- senao devolve [] e o chamador
 * cai no fallback ao vivo.
 * @return array<int, string>
 */
function motivos_da_tabela(string $ra, string $data): array
{
    if (!in_array(modalidade(), MODALIDADES_COM_MOTIVO, true)) {
        return [];
    }
    $r = consultar('SELECT MOTIVO, MOTIVO2, MOTIVO3 FROM ' . tabela() . ' WHERE DT_CORTE = ? AND ALUNO = ?',
                   [$data, $ra]);
    if (!$r) {
        return [];
    }
    $out = [];
    foreach (['MOTIVO', 'MOTIVO2', 'MOTIVO3'] as $c) {
        $v = $r[0][$c] ?? null;
        if (is_string($v) && trim($v) !== '') {
            $out[] = trim($v);
        }
    }
    return $out;
}

/** Financeiro AO VIVO: mensalidade vencida e sem baixa ate hoje. null se em dia. */
function fator_financeiro_texto(string $ra): ?string
{
    $r = fator_consulta(
        'SELECT COUNT(*) AS qtd,
                MAX(DATEDIFF(day, cb.DATA_DE_VENCIMENTO, CAST(GETDATE() AS date))) AS dias_atraso
         FROM erp_db.dbo.ERP_COBRANCA cb
         CROSS APPLY (SELECT SUM(i.VALOR) AS v FROM erp_db.dbo.ERP_ITEM_LANC i
                      WHERE i.COBRANCA = cb.COBRANCA AND i.ITEM_ESTORNADO IS NULL
                        AND i.CODIGO_LANC IN (?, ?)) vt
         WHERE cb.ALUNO = ? AND (cb.ESTORNO IS NULL OR cb.ESTORNO <> ?)
           AND cb.DATA_DE_VENCIMENTO <= CAST(GETDATE() AS date) AND vt.v > 0
           AND NOT EXISTS (SELECT 1 FROM erp_db.dbo.ERP_COBRANCA_MOVIMENTACAO mv
                           WHERE mv.COBRANCA = cb.COBRANCA AND mv.MOVIMENTACAO LIKE ?
                             AND CAST(mv.DATA_MOVIMENTACAO AS date) <= CAST(GETDATE() AS date))',
        ['MENS', 'PARC_MENS', $ra, 'S', 'BAIXA%']);
    if ($r === null) {
        return null;
    }
    $qtd = (int) ($r[0]['qtd'] ?? 0);
    if ($qtd <= 0) {
        return null;
    }
    $atraso = (int) ($r[0]['dias_atraso'] ?? 0);
    $plural = $qtd > 1 ? 's' : '';
    return "mensalidade em atraso ({$qtd} parcela{$plural}" . ($atraso > 0 ? ", ha ate {$atraso} dias" : '') . ')';
}

/** AVA AO VIVO: dias desde o ultimo acesso. null se acessou ha pouco. */
function fator_ava_texto(string $ra): ?string
{
    $r = fator_consulta(
        'SELECT MAX(CAST(l.Timestamp AS date)) AS ultimo,
                DATEDIFF(day, MAX(l.Timestamp), CAST(GETDATE() AS date)) AS dias
         FROM ava_db.dbo.AVA_USERS u
         JOIN ava_db.dbo.LOG_ACESSOS_AVA l ON l.UserId = u.ID_AVA
         WHERE u.TIPO_INTEGRACAO = ? AND u.USER_ERP = ? AND l.Timestamp >= ?',
        ['Aluno', $ra, AVA_INICIO_PROD]);
    if ($r === null) {
        return null;
    }
    $ava = $r[0] ?? null;
    if (!$ava || $ava['ultimo'] === null) {
        return 'sem registro de acesso ao AVA';
    }
    $dias = (int) $ava['dias'];
    if ($dias > FATOR_AVA_ALTO) {
        return "sem acessar o AVA ha {$dias} dias";
    }
    if ($dias >= FATOR_AVA_MEDIO) {
        return "poucos acessos recentes ao AVA (ultimo ha {$dias} dias)";
    }
    return null;
}

/** Fallback ao vivo (sem motivo gravado): AVA + financeiro. */
function fatores_ao_vivo_textos(string $ra): array
{
    return array_values(array_filter([fator_ava_texto($ra), fator_financeiro_texto($ra)],
                                     function ($x) { return $x !== null; }));
}

/** consulta best-effort: erro de banco nao derruba a consulta principal, so zera o fator. */
function fator_consulta(string $sql, array $params): ?array
{
    try {
        return Banco::get()->consultar($sql, $params);
    } catch (ErroBanco $e) {
        error_log('api evasao fatores: ' . $e->getMessage());
        return null;
    }
}
