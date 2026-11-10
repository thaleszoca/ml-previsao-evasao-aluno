<?php
/*
 * Configuracao padrao da API -- VERSIONADA (vai para o git de proposito,
 * decisao do dono do projeto em 25/09/2026, reafirmada em 05/10/2026).
 *
 * TOKEN FIXO E IGUAL PARA TODOS: um unico valor para as duas modalidades
 * (presencial e EAD, atendidas pela mesma porta) e para todos os consumidores.
 * Nao ha token por usuario nem por modalidade -- a modalidade vem na URL. Quem
 * baixa a pasta ja chama a API sem configurar nada; em troca, quem tem o
 * repositorio conhece o token.
 *
 * (Opcional, por servidor) da para sobrescrever em api/lib/config.local.php
 * (fora do git) ou SetEnv EVASAO_API_TOKEN. Ordem: ambiente > config.local.php >
 * config.php. Mas o PADRAO e este fixo -- nao precisa criar nada.
 */
return [
    'EVASAO_API_TOKEN' => 'TOKEN-REMOVIDO',
];
