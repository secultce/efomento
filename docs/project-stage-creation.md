# Criação automática de etapas do projeto (ProjectStage)

## Visão geral

Todo projeto cultural no e-Fomento possui um fluxo sequencial de **6 etapas**, cada uma responsável por um setor da SECULT-CE. Essas etapas são criadas automaticamente sempre que um novo `Project` é inserido no banco de dados, via `ProjectObserver`.

## Origens de criação de projetos

A criação de etapas pode ocorrer em dois momentos distintos:

### 1. Importação de planilha (implementado)

Executado manualmente via comando Artisan:

```bash
php artisan app:import-spreadsheet {path} --notice-id={id}
```

O `SpreadsheetImportService` lê o arquivo, mapeia as colunas e chama `Project::create()` para cada linha válida.

### 2. Sync com a API do Mapa Cultural (em desenvolvimento)

O e-Fomento consultará periodicamente a API do Mapa Cultural e, para cada projeto retornado:

- **Projeto já existe** → executa `update()`. O Observer **não** dispara. As etapas existentes permanecem intactas no estado em que estavam.
- **Projeto novo** → executa `create()` (via `firstOrCreate` ou `updateOrCreate`). O Observer dispara e cria as 6 etapas.

> **Atenção:** o serviço de sync deve usar obrigatoriamente `firstOrCreate` ou `updateOrCreate`, nunca `create()` direto, para evitar duplicar etapas em projetos já existentes.

## O Observer

`app/Observers/ProjectObserver.php` — método `created()`:

Ao detectar a criação de um `Project`, insere as 6 etapas na tabela `project_stages` com a seguinte configuração inicial:

| Order | Slug              | Setor responsável | Status inicial |
|-------|-------------------|-------------------|----------------|
| 1     | abertura          | C. Finalística    | `em_andamento` |
| 2     | analise_juridica  | ASJUR             | `pendente`     |
| 3     | formalizacao      | C. Finalística    | `pendente`     |
| 4     | orcamento         | CODIP             | `pendente`     |
| 5     | pagamento         | CEGEF             | `pendente`     |
| 6     | monitoramento     | Monitoramento     | `pendente`     |

Somente a primeira etapa inicia com `em_andamento` e com `started_at` preenchido. As demais ficam bloqueadas até a aprovação sequencial via `ProjectStageService`.

## Progressão de etapas

O avanço entre etapas é controlado por `ProjectStageService`:

- `advance(ProjectStage $stage)` — aprova a etapa atual e ativa a próxima.
- `reject(ProjectStage $stage, string $reason)` — rejeita a etapa atual e bloqueia todas as seguintes.

A regra de negócio está em `ProjectStage::canAdvance()`: uma etapa só pode ser aprovada se todas as anteriores já estiverem com status `aprovado`.

## Diagrama de atividade

`docs/diagrams/activities/project-stage-creation.puml`
