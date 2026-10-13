# Catálogo de bancos

`banks.json` é uma cópia local da [listagem de bancos da BrasilAPI](https://brasilapi.com.br/api/banks/v1), obtida em 13/10/2026. A aplicação não consulta esse serviço para carregar ou pesquisar bancos.

Cada instituição tem ISPB, código COMPE (quando disponível), nome completo (`name`) e nome abreviado (`short_name`). Os códigos COMPE são strings com três dígitos para preservar zeros à esquerda. Códigos ausentes ou iguais a zero na fonte são armazenados como `null`; o ISPB identifica a instituição de forma única.

A migration cria e preenche a tabela `banks`, independentemente de `SEED_MODE`, e substitui `openings.bank` por `openings.bank_id`, sem converter nomes antigos. Para atualizar o catálogo, revise o JSON e execute:

```sh
php artisan db:seed --class=BankSeeder
```

O seeder atualiza pelo ISPB, preservando os IDs referenciados pelas aberturas. Instituições removidas do JSON não são excluídas automaticamente da tabela.

A importação de planilhas aceita código COMPE ou nome completo/abreviado, desconsiderando caixa, acentos, pontuação e o sufixo S.A. Bancos desconhecidos são registrados no log e ficam sem seleção para correção no formulário.
