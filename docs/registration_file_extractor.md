# Plano: baixar para o e-Fomento os arquivos das fases (SyncProjectPhasesJob)

> **Status:** as seções 1 a 5 (download) estão implementadas na `feature/job_all_registration`, sem commit: `PhaseFileExtractor`, `SyncPhaseFilesJob`, `app:sync-phase-files`, morph map, `HasFiles` e gatilho em `ProjectPhaseSyncService`. A seção 6 (renderização no modal) continua pendente e depende da #636.
>
> **Fase 1:** o job novo trata todas as fases, inclusive a 1. No dev nenhum arquivo da fase 1 existia em `files` (0 de 484), então não se confirmou a premissa de que o fluxo antigo já os baixou. O filtro por `external_id` evita baixar de novo o que já estiver registrado, mas, se o job novo baixar um arquivo da fase 1 antes do fluxo antigo, o fluxo antigo (que só confere os arquivos do próprio projeto) pode registrar uma segunda linha.
>
> **Teste manual no dev:** o Mapas local responde 500 para qualquer `/arquivos/privateFile/{id}/`, inclusive arquivos que o fluxo antigo registrou (não há arquivos físicos na instância). O caminho completo foi validado em `ProjectPhaseFilesTest` com o Mapas simulado.

## Contexto

Hoje existem dois fluxos independentes:

| | `SyncNoticesJob` → … → `SyncProjectFilesJob` | `SyncProjectPhasesJob` (#637) |
|---|---|---|
| Fonte | `GET /registration/detalhes/{id}` (`registration.files` + `fileConfigurations`) | `GET /pdf/inscricao_json_todas_as_fases/{id}` |
| Arquivos | baixados (`DownloadMapasProjectFileJob`, fila `files`) e gravados no disco + tabela `files` | só o JSON em `project_registration_phases.data`; as URLs continuam apontando para o Mapas (`.../arquivos/privateFile/{id}/`) |

A #636 vai exibir as fases num modal. Sem download, o usuário depende de login no Mapas para abrir cada arquivo (são `privateFile`), e o e-Fomento não controla acesso nem disponibilidade.

**Veredito: viável e pequeno.** Não precisa de migration nem de endpoint novo no Mapas. Os dados já estão no JSON: cada campo `type: "file"` traz `value: [{id, url, name}]`, `title` e `description` (no dev: 1.361 campos de arquivo em 198 fases). O `id` do campo equivale ao `grp` do fluxo antigo (`rfc_{id}`).

## Abordagem recomendada

### 1. Dono dos arquivos: a própria fase (`project_registration_phase`), não o `project`
- Gravar com `object_type = 'project_registration_phase'`, `object_id = phase.id` (tabela `files` já é polimórfica).
- **Por que não `project`:** `LegalAnalysisController::index` lista `$project->files()` agrupado por `grp`. Arquivos das fases 2/3 apareceriam sozinhos na análise da ASJUR, mudando comportamento sem ninguém ter decidido.
- Registrar `'project_registration_phase' => ProjectRegistrationPhase::class` no morph map (`AppServiceProvider.php:48`) e usar `HasFiles` no model (`app/Traits/HasFiles.php`).

### 2. Extração: novo extrator para o formato da fase
`RegistrationFileExtractor` espera `files` indexado por grupo + `fileConfigurations`; a fase tem outro formato. Criar `PhaseFileExtractor::extract(array $phaseData): Collection` que percorre `sections[].fields[]` com `type === 'file'` e devolve **o mesmo array normalizado** que o job de download já consome:
`external_id=value[].id, url, name, title=field.title, grp="rfc_{field.id}", description=field.description, private=true`.

### 3. Job novo `SyncPhaseFilesJob(phaseId)` (fila `files`)
- Recebe só o id (o `data` é grande), carrega a fase, extrai, **filtra os `external_id` que já existem** em `files` (`source = mapas`, `withTrashed`; o id de arquivo do Mapas é único globalmente) e despacha um batch de `DownloadMapasProjectFileJob` com `objectType = 'project_registration_phase'`.
- **Reaproveita `DownloadMapasProjectFileJob` sem alterá-lo** (rate limit, `WithoutOverlapping`, download em streaming, limpeza do temporário, gravação no disco `efomento.file_disk` em `projects/{id}/mapas/{external_id}.ext`).
- Filtrar *antes* de criar os jobs importa: hoje o skip "já registrado" fica dentro do `handle`, depois do `RateLimited('mapas-api')`, então cada arquivo repetido gastaria uma das 60 requisições/min à toa.
- Resolve a duplicação da fase 1: os arquivos da fase 1 já foram baixados pelo fluxo antigo (donos = `project`); como o filtro é por `external_id`, não são baixados de novo. O modal resolve o arquivo local por `external_id`, qualquer que seja o dono.

### 4. Gatilho: só quando a fase mudou
Em `ProjectPhaseSyncService::sync()`, depois do `DB::transaction`, despachar `SyncPhaseFilesJob` para cada fase gravada em `$resolved` (as que são novas ou mudaram). Fase sem mudança não dispara nada, então o custo diário continua sendo o do resumo. `storePhase()` passa a devolver o model/id.

### 5. Carga inicial (backfill)
As 198 fases do dev (e as de homolog/produção, quando o PR #641 entrar) são gravadas pela primeira vez na primeira rodada, então os downloads sobem em rajada. Duas proteções:
- O limitador `mapas-api` (60/min) é compartilhado com a sincronização de detalhes. Milhares de arquivos levam horas; é aceitável por rodar na fila `files`, mas deve ser comunicado.
- Comando `app:sync-phase-files {--project=}` para reprocessar fases já gravadas (útil também se um download falhar de vez) e para a primeira carga controlada.

### 6. Renderizar o arquivo local no modal (sem sair do e-Fomento)

**Como é hoje.** `RegistrationPhasesModal.vue` usa `useRegistrationFields.formatField()`. Para campos `file`, `linkItem()` transforma cada `{id, url, name}` do JSON em `<a href="{url do Mapas}" target="_blank">`. O JSON fica exatamente como o Mapas devolve; é por isso que o clique leva ao Mapas e exige login lá.

**Como fica.** O `data` salvo **não é alterado** (ele é a cópia fiel do Mapas e entra no `content_hash`). A troca do link acontece na resposta, em três peças:

1. **Mapa de arquivos locais (backend).** Em `ProjectController@show`, depois de carregar `registrationPhases`, fazer **uma** consulta em `files` (`source = mapas`, `external_id` IN ids dos campos `file` das fases, dono = a fase ou o próprio projeto, que cobre os arquivos da fase 1 baixados pelo fluxo antigo). Devolver em cada fase `local_files: { "<id do Mapas>": { id, name, mime_type, view_url, download_url } }`. A chave é o `id` do arquivo no JSON, igual ao `File.external_id`.

2. **Rota que serve o arquivo (backend).** `GET /projetos/{project}/fases/{phase}/arquivos/{file}` (nome `projects.registration-phases.files.serve`, `scopeBindings`, no mesmo grupo autenticado das demais rotas de arquivo), em um `RegistrationPhaseFileController`. Segue o `MonitoringController::serveFile` (que valida o dono, mais restrito que o da análise jurídica):
   - `abort_unless` o arquivo pertencer à fase ou ao projeto (fase 1 legada) e a fase ser do projeto;
   - `abort_unless($disk->exists($file->path), 404)`;
   - `?download=1` → `$disk->download(...)`; sem o parâmetro → `$disk->response(...)` (inline).
   - **Só serve inline `application/pdf` e imagens `png/jpeg/gif/webp`**; qualquer outro tipo (principalmente HTML e SVG) sai forçado como download, com `X-Content-Type-Options: nosniff`. Sem isso, um HTML enviado pelo proponente seria executado na origem do e-Fomento (XSS armazenado).

3. **Visualização (frontend).**
   - `useRegistrationFields.formatField(field, localFiles)`: no `file`, se `localFiles[entry.id]` existir, o item vira `{ text, local: { view_url, download_url, mime_type } }` em vez de `href` do Mapas. Se não existir (download ainda não terminou ou falhou), mantém o link do Mapas, com o rótulo "(abrir no Mapas)".
   - Novo `FilePreviewDialog.vue` (reaproveita o padrão do `DocumentViewerDialog.vue`): `v-dialog` empilhado sobre o modal das fases, com `<iframe :src="view_url">` para PDF, `<img>` para imagem e, para outros tipos, a mensagem "Pré-visualização indisponível" com o botão "Baixar". Botão "Baixar" sempre presente (`download_url`).
   - `RegistrationPhasesModal.vue`: no item `local`, trocar o `<a target="_blank">` por um botão-link que abre o `FilePreviewDialog`. O usuário continua na tela do projeto e fecha o preview para voltar ao modal.
   - O `<iframe>` é da mesma origem, então o `X-Frame-Options: SAMEORIGIN` do nginx de produção (`docker/production/nginx.conf:7`) permite.

**Ponto de segurança que precisa de confirmação.** `FILE_DISK` não está no `.env`, então o padrão é o disco `public` (`storage/app/public`). Se o link simbólico `/storage` estiver ativo no nginx, o arquivo fica acessível por `/storage/projects/{id}/mapas/{id_do_mapas}.pdf` **sem autenticação** (os ids do Mapas são sequenciais, logo adivinháveis). A rota autenticada só protege de verdade se o disco for privado. Isso já vale para os arquivos da fase 1 hoje; confirmar o `FILE_DISK` de homologação/produção antes de dizer que o arquivo está "protegido".

## Riscos e pontos de atenção
- **Disco:** arquivos das fases 2/3 se somam aos da fase 1. Medir o tamanho médio em produção antes de ligar (no dev os arquivos não estão no disco, então não deu para estimar aqui).
- **Arquivo substituído no Mapas:** vem com id novo e é baixado como novo; o antigo permanece local (mesmo comportamento do fluxo atual, que também não apaga).
- **Arquivo removido no Mapas:** o download dá 404 e o job falha após as tentativas; não deve derrubar a sincronização da fase (o download é job separado, em batch com `allowFailures`).
- **Token do Mapas:** `downloadFileTo` já envia o `authorization`; as URLs `privateFile` do fluxo antigo funcionam do mesmo jeito.
- **Sem migration:** `files` já é polimórfica; só entra o morph map.

## Arquivos
- Novo: `app/Services/PhaseFileExtractor.php`, `app/Jobs/SyncPhaseFilesJob.php`, `app/Console/Commands/SyncPhaseFilesCommand.php`
- Alterar: `app/Services/ProjectPhaseSyncService.php` (devolver fase gravada + despacho pós-commit), `app/Models/ProjectRegistrationPhase.php` (`HasFiles`), `app/Providers/AppServiceProvider.php` (morph map)
- Reaproveitar sem mudar: `DownloadMapasProjectFileJob`, `MapasClient::downloadFileTo`, `File`, `HasFiles`
- Renderização (#636): `app/Http/Controllers/RegistrationPhaseFileController.php` (novo), rota em `routes/web.php`, `local_files` em `ProjectController@show`, `resources/js/Composables/useRegistrationFields.js`, `resources/js/Components/RegistrationPhasesModal.vue`, `resources/js/Components/FilePreviewDialog.vue` (novo)

## Verificação
- Testes novos em `tests/Feature` (PHPUnit, `Bus::fake`/`Http::fake` + `Storage::fake`): extrator (campos `file`, múltiplos arquivos, campo sem arquivo), `SyncPhaseFilesJob` (filtra os já registrados, inclusive fase 1 já dono `project`), gatilho no `sync()` (dispara só para fase nova/alterada, não no ramo "sem mudança"), idempotência (rodar duas vezes não duplica).
- `docker compose exec app php artisan test --filter=ProjectPhaseSync` + testes novos; `./vendor/bin/pint`.
- Rota de arquivo (feature test): usuário autenticado vê PDF inline; HTML/SVG sai como download; arquivo de outro projeto/fase → 404; arquivo ausente no disco → 404; sem login → redireciona. `local_files` traz arquivos da fase e da fase 1 (dono `project`).
- Manual no dev com `-e MAPAS_DOMAIN=http://10.7.0.1:8088`: rodar o comando para o projeto 137, conferir linhas em `files` (`object_type = project_registration_phase`) e os arquivos no disco; repetir e confirmar que nada é baixado de novo.
