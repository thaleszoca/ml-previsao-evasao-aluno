<?php
/*
 * Configuracao padrao da API -- VERSIONADA (vai para o git de proposito,
 * mesma decisao da API do presencial, para facilitar os testes).
 *
 * O token fixo abaixo e o que todo mundo usa para testar: quem baixa a pasta
 * ja consegue chamar a API, sem criar arquivo nenhum. Ele barra quem esta de
 * fora (internet), mas qualquer pessoa com acesso ao repositorio o conhece.
 * E fixo, como o do presencial, mas com valor PROPRIO (nome do EAD) -- cada
 * API tem o seu token, nunca o mesmo.
 *
 * Para trocar num servidor sem mexer aqui: api/lib/config.local.php (fora do
 * git) ou SetEnv EVASAO_API_TOKEN -- ambos valem mais que este arquivo.
 * Ordem: variavel de ambiente > config.local.php > config.php.
 */
return [
    'EVASAO_API_TOKEN' => 'COLOQUE-SEU-TOKEN-AQUI',
];
