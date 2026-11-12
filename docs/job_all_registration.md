# Job de sincronização de todas as inscrições (fases) do Mapas Cultural

Branch: `feature/job_all_registration`
Status: **mergeada na `develop`** (PR #641, merge `ec04bac`, em 2026-11-09)
Issues relacionadas: #636 (modelo e dados), #637 (job diário e download de arquivos), #641 (ajustes de revisão do job)

Este documento descreve como o job funciona hoje nesta branch. Ele é dividido em duas partes: a **parte técnica**, para o time de desenvolvimento, e um **resumo para a gestão** no final.

---

# Parte técnica

## 1. Objetivo

Para cada projeto (inscrição) do e-Fomento, manter na base local **todas as fases** da inscrição cadastrada no Mapas Cultural do Ceará, e baixar os **arquivos** enviados em cada fase. A sincronização roda uma vez por dia, só reconsulta o que mudou e para de consultar quando a inscrição chega a um resultado final.

## 2. Visão geral do fluxo

```
Scheduler (04:00, America/Fortaleza)
└── SyncProjectPhasesJob                       fila: details
    ├── seleciona projetos vencidos (next_check_at <= fim do dia de hoje, sync_closed_at IS NULL)
    └── divide em lotes de N projetos (Bus::batch, allowFailures)
        └── SyncProjectPhasesProjectJob ×N     fila: details, tries 10
            └── ProjectPhaseSyncService::sync($project)
                ├── GET resumo do Mapas (until_latest=1&summary=1)
                ├── compara summary_hash
                │     ├── sem mudança → só atualiza datas de controle
                │     └── com mudança →
                │           ├── para cada fase nova/alterada: GET detalhe da fase
                │           ├── grava project_registration_phases (transação)
                │           ├── atualiza controle em projects (transação)
                │           └── após o commit: SyncPhaseFilesJob por fase gravada
                │                 └── fila: files
                │                     └── DownloadMapasProjectFileJob por arquivo novo
                └── em falha: postponeAfterFailure() e relança a exceção (retry da fila)
```

## 3. Agendamento e filas

| Componente | Agendamento / fila | Configuração principal |
|---|---|---|
| `SyncProjectPhasesJob` | `routes/console.php`: diário às **04:00**, fuso `efomento.phase_sync_timezone`; `withoutOverlapping(60)`; `onOneServer` | fila `details`, `tries = 3`, `timeout = 120`, `WithoutOverlapping('sync-project-phases')` (libera após 300s, expira em 3600s), backoff `[1, 5, 10]` |
| `SyncProjectPhasesProjectJob` | despachado em lote pelo job acima | fila `details`, `tries = 10`, `maxExceptions = 3`, `timeout = 300`, `retryUntil = agora + 12h`, `WithoutOverlapping("sync-project-phases:{projectId}")`, backoff `[1, 5, 10, 30, 60]` |
| `SyncPhaseFilesJob` | disparado após cada fase gravada | fila `files`, `tries = 3`, `timeout = 120`, backoff `[1, 5, 10]` |
| `DownloadMapasProjectFileJob` | já existente; reutilizado | recebe `projectId`, `registrationId`, `file`, `objectType`, `objectId` |
| `app:sync-phase-files` | manual | reenfileira o download dos arquivos de fases já gravadas (`--project=` opcional) |

Lotes: o tamanho do lote vem de `MAPAS_REGISTRATION_DETAIL_CHUNK` (padrão 100). Cada lote é um `Bus::batch` nomeado `sync-project-phases:chunk:N`, com `allowFailures()`, para que a falha de um projeto não cancele os demais.

## 4. Componentes

| Arquivo | Responsabilidade |
|---|---|
| `app/Jobs/SyncProjectPhasesJob.php` | Despachante diário. Seleciona os projetos vencidos e monta os lotes. |
| `app/Jobs/SyncProjectPhasesProjectJob.php` | Sincroniza um projeto. Ignora projetos já encerrados. Em falha, adia a próxima verificação e relança o erro. |
| `app/Services/ProjectPhaseSyncService.php` | Toda a regra de negócio: resumo, hash, comparação, gravação, agendamento e rate limit. |
| `app/Services/MapasClient.php` | Método `registrationAllPhases(int $registrationId, bool $untilLatest, bool $summary)`, que chama `/pdf/inscricao_json_todas_as_fases/{id}`. |
| `app/Services/PhaseFileExtractor.php` | Extrai, do JSON de uma fase, os campos do tipo `file` no formato usado pelo download. |
| `app/Jobs/SyncPhaseFilesJob.php` | Filtra arquivos já baixados e cria o lote de downloads da fase. |
| `app/Console/Commands/SyncPhaseFilesCommand.php` | Comando `app:sync-phase-files`, para reprocessar downloads. |
| `app/Models/ProjectRegistrationPhase.php` | Model da fase (usa `HasFiles`; **sem** `Auditable`, por causa do tamanho do JSON). |
| `app/Models/Project.php` | Relação `registrationPhases()`. |
| `app/Providers/AppServiceProvider.php` | Define o rate limiter `mapas-api`, com `MAPAS_RATE_LIMIT_PER_MINUTE` (padrão 60/min). |
| `config/efomento.php` | Chaves `phase_sync_timezone`, `registration_detail_chunk` e `mapas_rate_limit_per_minute`. |
| `routes/console.php` | Agendamento do `SyncProjectPhasesJob`. |

## 5. Modelo de dados

### Migration `2026_10_27_000001_add_phase_sync_columns_to_projects_table.php`
Adiciona em `projects`:

| Coluna | Tipo | Uso |
|---|---|---|
| `latest_registration_id` | bigint, nullable | id da fase mais recente, devolvido pelo resumo |
| `total_phases` | smallint, nullable | quantidade de fases no Mapas |
| `summary_hash` | string(64), nullable | SHA-256 do resumo normalizado; detecta mudança |
| `last_checked_at` | timestamp, nullable | última consulta ao Mapas |
| `last_changed_at` | timestamp, nullable | última vez em que o resumo mudou |
| `next_check_at` | timestamp, nullable | próxima verificação agendada |
| `sync_closed_at` | timestamp, nullable | quando a sincronização foi encerrada (resultado final) |

Índice: `(sync_closed_at, next_check_at)`.

### Migration `2026_10_27_000002_create_project_registration_phases_table.php`
Cria `project_registration_phases`:

| Coluna | Tipo | Uso |
|---|---|---|
| `id` | bigint PK | |
| `project_id` | FK `projects`, `cascadeOnDelete` | |
| `registration_id` | bigint | id da fase no Mapas |
| `opportunity_id` | bigint, nullable | oportunidade (edital) da fase |
| `phase_order` | smallint | ordem da fase |
| `is_first` | boolean, default false | primeira fase |
| `status` | smallint, nullable | status da fase no Mapas (ver tabela da seção 7) |
| `sent_at` | date, nullable | data de envio |
| `registration_from` / `registration_to` | timestamp, nullable | período de inscrição, em `config('app.timezone')` |
| `data` | jsonb, nullable | JSON completo da fase |
| `content_hash` | string(64), nullable | SHA-256 do `data` |
| `fetched_at` | timestamp, nullable | quando o detalhe foi buscado |
| timestamps | | |

Chave única: `(project_id, registration_id)`. Cada fase é uma linha, atualizada com `updateOrCreate`.

Observação: as duas migrations são **novas** (não editam migrations antigas), porque o projeto já está em homologação e produção.

## 6. Algoritmo de `ProjectPhaseSyncService::sync()`

1. **Rate limit:** `awaitMapasSlot()` espera uma vaga no limite `mapas-api` antes de cada chamada ao Mapas.
2. **Resumo:** `registrationAllPhases(registration_id, untilLatest: true, summary: true)`.
3. **Validação:** `assertValidSummary()` exige `phases` (array), `totalPhases` (inteiro) e a chave `latestRegistrationId`. Se faltar algo, lança `UnexpectedValueException` **antes de qualquer gravação**.
4. **Hash:** `summaryHash()` gera SHA-256 de um resumo normalizado com `latestRegistrationId`, `totalPhases` e, para cada fase, somente a impressão digital (`registrationId`, `category`, `status`, `sentDate`, `registrationFrom`/`registrationTo` normalizados para o fuso da aplicação, `hasNextPhase`, `nextPhase.opportunityId`, `nextPhase.registrationFrom`).
5. **Sem mudança** (`hash === projects.summary_hash`): calcula o agendamento com `schedule($summary, false)`, grava `last_checked_at`, `next_check_at` e, se for o caso, `sync_closed_at`. Termina aqui.
6. **Com mudança:**
   - Para cada fase do resumo, `resolvePhase()`:
     - se já existe uma linha com a mesma impressão digital do resumo, **pula** (não chama o Mapas);
     - senão, busca o detalhe (`registrationAllPhases($registrationId)`), que é a chamada que consome o rate limit;
     - se o detalhe não trouxer a fase pedida, lança exceção (não grava o resumo, para não “esconder” a fase do próximo ciclo);
     - se `content_hash` e `status` continuarem iguais ao que está gravado, **pula**.
   - Em **transação**: `storePhase()` para cada fase resolvida; depois `updateControl()` com `latest_registration_id`, `total_phases`, `summary_hash`, `last_checked_at`, `last_changed_at`, `next_check_at` e `sync_closed_at`.
   - As chamadas HTTP ficam **fora** da transação; ela só faz leituras e escritas no banco.
7. **Pós-commit:** `dispatchFileDownloads()` despacha um `SyncPhaseFilesJob` por fase **gravada nesta rodada**. Falha de despacho só gera log (`sync.project_phases.files_dispatch_failed`); o comando `app:sync-phase-files` recupera depois.
8. Log `sync.project_phases.changed`.

`updateControl()` usa `Project::query()->toBase()->update(...)`. Isso evita auditoria, observers e a alteração de `updated_at` nas consultas diárias.

## 7. Regras de agendamento (`schedule()`)

Status da fase (constantes em `ProjectPhaseSyncService`):

| Código | Constante | Significado |
|---|---|---|
| 0 | `STATUS_DRAFT` | rascunho |
| 1 | `STATUS_SENT` | enviada |
| 2 | `STATUS_INVALID` | inválida (final) |
| 3 | `STATUS_NOT_SELECTED` | não selecionada (final) |
| 8 | `STATUS_WAITLIST` | suplente |
| 10 | `STATUS_SELECTED` | selecionada (final) |

Regras, na ordem em que o código as avalia, sobre a fase mais recente (`latestRegistrationId`):

| Situação | Próxima verificação | Encerra? |
|---|---|---|
| Fase mais recente não encontrada no resumo | amanhã | não |
| Status 0 (rascunho) ou 1 (enviada) | amanhã | não |
| Status final (2, 3 ou 10) **e** sem próxima fase | — | **sim** |
| Houve mudança nesta rodada | amanhã | não |
| Sem mudança, com próxima fase cuja inscrição abre em breve | o dia anterior à abertura, ou em 7 dias se for antes | não |
| Sem mudança, demais casos (incluindo suplente 8) | em 7 dias | não |

Nunca agenda para antes de amanhã. Se a data calculada cair antes de amanhã, usa amanhã.

## 8. Seleção de projetos (despachante)

`SyncProjectPhasesJob` seleciona `sync_closed_at IS NULL` e (`next_check_at IS NULL` ou `next_check_at <= fim do dia de hoje`), usando o fuso `PHASE_SYNC_TIMEZONE` (padrão `America/Fortaleza`) e convertendo para o fuso da aplicação.

O fim do dia é usado de propósito: `next_check_at` guarda o horário em que o projeto foi processado, que varia com a fila e o rate limit. Comparar com `now()` puro perderia a execução do dia seguinte (ex.: `04:00:10 > 04:00:01`).

## 9. Falhas e retries

- **Falha no Mapas ou em qualquer etapa** de `SyncProjectPhasesProjectJob`: `postponeAfterFailure()` grava `next_check_at = agora + max(1, tentativa)` horas, e a exceção é relançada para a fila tentar de novo.
- O número de tentativas usado no adiamento é `min($this->attempts(), 24)`, então o adiamento não passa de 24 horas. `attempts()` conta também as liberações do `WithoutOverlapping` e do rate limiter.
- `retryUntil()` é calculado **no despacho** (`agora + 12h`), para cobrir o tempo de espera na fila `details`. Antes era 2h.
- Projetos com `sync_closed_at` preenchido são ignorados em `handle()`.
- `failed()` registra `sync.project_phases.project.failed` (nível error).
- Falha no download de arquivo não desfaz a sincronização. O `SyncPhaseFilesJob` roda depois do commit, e `app:sync-phase-files` reenfileira o que ficou para trás.

## 10. Download dos arquivos das fases

`PhaseFileExtractor::extract($phaseData)` percorre `sections[].fields[]` e pega os campos com `type === 'file'`. Para cada arquivo gera:

- `external_id` (id no Mapas), `url`, `name`, `title` (título do campo), `grp` = `'rfc_' . id_do_campo`, `description`, `private = true`.

Só entram arquivos com `external_id`, `url` e `name` preenchidos, sem duplicar o `external_id`.

`SyncPhaseFilesJob::handle()`:

1. Carrega a fase. Se não existir ou não tiver arquivos, termina.
2. Remove os arquivos cujo `external_id` já está em `files` com `source = 'mapas'` (inclusive soft-deleted), **qualquer que seja o dono**. Isso evita baixar o mesmo arquivo duas vezes, inclusive pelo fluxo de detalhes da inscrição.
3. Cria um `Bus::batch` (fila `files`, `allowFailures`) com um `DownloadMapasProjectFileJob` por arquivo pendente, com `objectType = 'project_registration_phase'` e `objectId = phase.id`. Assim o arquivo fica vinculado à fase.

## 11. Rate limit do Mapas

- O middleware `RateLimited` conta **jobs**, não requisições. Um projeto com várias fases faria várias chamadas dentro de um único job, então o controle foi feito dentro do serviço.
- `awaitMapasSlot()` usa a mesma chave do middleware (`md5('mapas-api' . $limit->key)`), então o orçamento é **compartilhado** com os demais jobs que usam `mapas-api`.
- Quando não há vaga, dorme `max(1, RateLimiter::availableIn($key))` segundos e tenta de novo.
- Limite padrão: 60 requisições/minuto (`MAPAS_RATE_LIMIT_PER_MINUTE`).

## 12. Variáveis de ambiente

| Variável | Padrão | Uso |
|---|---|---|
| `PHASE_SYNC_TIMEZONE` | `America/Fortaleza` | fuso do despachante diário e de “hoje” |
| `MAPAS_REGISTRATION_DETAIL_CHUNK` | `100` | tamanho do lote de projetos |
| `MAPAS_RATE_LIMIT_PER_MINUTE` | `60` | limite de requisições ao Mapas por minuto |
| `MAPAS_DOMAIN`, `MAPAS_TOKEN`, `MAPAS_SEAL` | — | conexão com o Mapas (já existentes) |
| `MAPAS_HTTP_TIMEOUT`, `MAPAS_HTTP_RETRIES`, `MAPAS_HTTP_RETRY_SLEEP_MS` | 10, 3, 1000 | comportamento HTTP do `MapasClient` (já existentes) |

## 13. Testes

- `tests/Feature/ProjectPhaseSyncTest.php`: cobre criação, mudança de status, fechamento por resultado final, suplente (nunca fecha), agendamento (7 dias, dia anterior à abertura), falha do Mapas com adiamento, fuso horário, resumo sem a estrutura esperada, detalhe sem a fase pedida, troca de categoria, seleção de projetos vencidos no fuso configurado e disparo de download só para fases alteradas.
- `tests/Feature/ProjectPhaseFilesTest.php`: cobre o extrator (só campos `file`), o lote de downloads, a deduplicação por `external_id`, a idempotência, o comando `app:sync-phase-files` e o vínculo do arquivo com a fase.

Rodar (dentro do Docker, conforme o CLAUDE.md do projeto):

```bash
docker compose exec app php artisan test --filter=ProjectPhase
```

Para testar o job manualmente em ambiente local, é possível executar `SyncProjectPhasesProjectJob` diretamente (sem passar pelo despachante) e apontar `MAPAS_DOMAIN` para um Mapas de teste. Para refazer a sincronização de um projeto, zere `next_check_at` e `sync_closed_at` com `toBase()->update()`.

## 14. Pontos de atenção e limitações

- **Mudança só no detalhe não é detectada.** A decisão de rebuscar uma fase depende do resumo. Se o Mapas alterar o conteúdo de uma fase sem alterar nenhum campo do resumo, a rodada não percebe. Vale monitorar isso.
- **Fase mais recente ausente no resumo** faz o projeto ser reagendado para amanhã indefinidamente, sem nunca encerrar. Vale um alerta se isso acontecer.
- **Encerramento:** o código encerra a sincronização para os status 2, 3 e 10 quando não há próxima fase. O suplente (8) nunca encerra. A nota de memória do projeto dizia que só o status 10 encerrava; o código atual é a referência.
- **Despachante carrega todos os IDs vencidos em memória** (`pluck('id')`). Hoje é aceitável para o volume atual; se o número de projetos crescer muito, convém paginar.
- **Interface ainda não existe nesta branch.** Os arquivos alterados não incluem Vue nem controllers. A visualização das fases na ficha da inscrição (#636) é trabalho seguinte e depende dos dados gravados aqui.

## 15. Commits principais

- `799c94f`, `6d42e53`, `03dee26`: migrations, job inicial, model e serviço (#636).
- `93d9864`, `b02aff4`, `2375fc3`, `ba339c6`: repetição do job, transação, `updated_at` e detalhe de fase (#637).
- `efc1dd4`, `5e7f397`, `0e6e40d`, `c340901`, `0c1eb98`: extração de arquivos, comando de sincronização de arquivos, categoria, limites de requisição e validação do JSON (#637).
- `3efe35d`, `55ee41d`: ajuste dos campos para não repetir o job sem necessidade, e do tempo de `retryUntil` de 2h para 12h (#641).

---

# Resumo para a gestão

**O que é:** um processo automático que, todos os dias de madrugada, consulta no Mapas Cultural as inscrições dos projetos do e-Fomento e guarda no nosso sistema todas as fases de cada inscrição, junto com os documentos enviados em cada uma delas.

**Por que importa:** hoje, para acompanhar a situação de uma inscrição, a equipe depende de consultar o Mapas manualmente. Com o processo, o e-Fomento passa a ter o histórico completo de cada inscrição (todas as fases, com status e datas) e os arquivos enviados já disponíveis dentro do sistema.

**Como funciona, em linguagem simples:**

1. **Todo dia, às 4h (horário do Ceará),** o sistema revisa os projetos que precisam ser verificados.
2. **Para cada projeto, pergunta ao Mapas** se houve alguma novidade. Se não houve, apenas registra que a verificação foi feita e marca a próxima data.
3. **Se houve mudança,** busca os detalhes das fases novas ou alteradas e guarda tudo no sistema.
4. **Baixa os documentos** enviados em cada fase e os vincula à fase correspondente.
5. **Para de verificar** quando a inscrição chega a um resultado final (selecionada, inválida ou não selecionada) e não há mais fases. Projetos suplentes continuam sendo verificados a cada semana.
6. **Se o Mapas estiver fora do ar,** o sistema não perde nada: tenta novamente depois e adia a verificação do projeto por até 24 horas.

**Cuidados já tomados:**
- Respeita o limite de consultas do Mapas, para não sobrecarregar o serviço nem ser bloqueado.
- Não baixa o mesmo documento duas vezes.
- Se algo falhar no meio do caminho, o que já estava certo não é apagado.

**Situação atual:**
- O processo de sincronização e o armazenamento estão implementados e cobertos por testes automatizados. Já foram mergeados na `develop` (PR #641).
- **Ainda não há tela** para a equipe visualizar as fases na ficha da inscrição. Essa parte é o próximo passo.

**Decisão necessária da gestão:** nenhuma neste momento. Se a equipe quiser um alerta automático quando uma inscrição sumir do Mapas ou o processo falhar com frequência, isso pode ser definido como próxima etapa.
