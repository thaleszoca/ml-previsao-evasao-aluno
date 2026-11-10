# Notebooks de modelagem (`ml/`)

Um notebook por modalidade da graduação. A saída das células foi apagada; os notebooks
leem bases que não são publicadas (ver [README principal](../README.md#dados)).

| Notebook | Modalidade | Modelos |
|---|---|---|
| [`grad-presencial-mensal/modelo_evasao_presencial_mensal.ipynb`](grad-presencial-mensal/modelo_evasao_presencial_mensal.ipynb) | Presencial | 5, um por mês letivo (31/08 → 31/12) |
| [`grad-ead/modelo_evasao_ead_mensal.ipynb`](grad-ead/modelo_evasao_ead_mensal.ipynb) | EAD | 3, um por corte do trimestre (+30d, +60d, fechamento) |
| [`grad-hib/modelo_evasao_hibrido_mensal.ipynb`](grad-hib/modelo_evasao_hibrido_mensal.ipynb) | Híbrida | 3, um por corte do trimestre (+30d, +60d, fechamento) |

Os outros dois arquivos, `grad-ead/modelo_evasao_ead.ipynb` e
`grad-hib/modelo_evasao_hibrido.ipynb`, são as versões anteriores, de corte único. Ficam
como registro da evolução; os mensais os substituíram.

## Roteiro de cada notebook

Os três seguem a mesma ordem de seções:

1. **Carga e elegibilidade:** quem entra na base é decidido só com o que existia na data
   do corte; o rótulo (rematriculou ou não) pode olhar o futuro.
2. **Engenharia de features:** blocos acadêmico, AVA, financeiro e perfil, todos
   filtrados pela data do corte.
3. **Separação de `X` e `y`:** colunas de desfecho, identificadores e datas ficam fora.
4. **Encoding e ausentes:** categorias com a cauda longa agrupada; nulo de período mantido
   como informação.
5. **Validação:** *out-of-fold* no presencial; origem deslizante entre coortes no EAD e
   no híbrido.
6. **Treino:** `RandomForestClassifier` sensível a custo, um modelo por corte.
7. **Fila:** limiar, tamanho da fila e quantos evasores ela alcança (`recall@200`,
   `recall@10%`, PR-AUC).
8. **Importância das features:** como o sinal muda ao longo do período.
9. **Exportação:** o `.pkl` com os modelos, as colunas esperadas e a política de fila,
   lido pelos scripts de [`job/`](../job/).
10. **Regra de uma coluna:** o modelo de cada corte contra "ordenar por dias sem acessar
    o AVA". Corte que não supera a regra não vai para produção.

O EAD e o híbrido têm ainda a replicação na origem seguinte, a estabilidade entre
coortes, a análise de quem escapa da fila e a auditoria de viés por sexo.

As decisões por trás de cada etapa estão em [`METODOLOGIA.md`](../METODOLOGIA.md).
