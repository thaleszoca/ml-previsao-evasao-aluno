<?php
/*
 * Configuracao padrao da API -- VERSIONADA (vai para o git de proposito,
 * decisao do dono do projeto em 25/09/2026, para facilitar os testes).
 *
 * O token fixo abaixo e o que todo mundo usa para testar: quem baixa a pasta
 * ja consegue chamar a API, sem criar arquivo nenhum. Ele barra quem esta de
 * fora (internet), mas qualquer pessoa com acesso ao repositorio o conhece.
 *
 * Para trocar num servidor sem mexer aqui: api/lib/config.local.php (fora do
 * git) ou SetEnv EVASAO_API_TOKEN -- ambos valem mais que este arquivo.
 * Ordem: variavel de ambiente > config.local.php > config.php.
 */
return [
    'EVASAO_API_TOKEN' => 'COLOQUE-SEU-TOKEN-AQUI',
];
