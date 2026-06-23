# Planejamento: Diligências via Email (Monitoramento + Prestação de Contas)

## Contexto

O design da tela de Monitoramento (`public/monitoramento.png`) introduz um fluxo de **diligência por e-mail**: a equipe envia uma notificação formal ao proponente/agente de dentro do Efomento. Quando o agente responde, o sistema captura a resposta via IMAP e exibe o fio da conversa na tela.

A mesma necessidade existe na nova fase **Prestação de Contas** (7ª etapa do fluxo). Por isso a estrutura de mensagens é desenhada de forma **polimórfica**, desacoplada de qualquer stage específico.

Estado atual: zero integração de e-mail — notificações são apenas `['database']`.

---

## Novas etapas do fluxo de projeto

O enum `ProjectStageSlug` ganha uma 7ª etapa:

```
1. ABERTURA
2. ANALISE_JURIDICA
3. FORMALIZACAO
4. ORCAMENTO
5. PAGAMENTO
6. MONITORAMENTO
7. PRESTACAO_DE_CONTAS  ← nova
```

**Arquivos afetados:**
- `app/Enums/ProjectStageSlug.php` — adicionar case `PRESTACAO_DE_CONTAS`
- `app/Observers/ProjectObserver.php` — adicionar a criação automática do 7º `ProjectStage` ao criar um projeto
- `app/Models/Project.php` — adicionar `hasOne(AccountabilityReport::class)` quando o model existir

---

## Estrutura de diligências (polimórfica)

### Decisão de design: Opção A — Polimórfica

`DiligenceMessage` pertence a qualquer entidade via morph map. Hoje `Monitoring`, amanhã `AccountabilityReport`. O morph map já é um padrão estabelecido no projeto (`AppServiceProvider`).

```
Monitoring         ──hasMany──▶ DiligenceMessage (diligenceable_type = 'monitoring')
AccountabilityReport ──hasMany──▶ DiligenceMessage (diligenceable_type = 'accountability_report')
```

---

## O que muda / o que é novo no backend

### 1. Novo pacote: IMAP

**Instalar:** `webklex/laravel-imap`

Nenhum pacote de IMAP existe hoje. Requisito de infra mais crítico.

---

### 2. Novo Model: `DiligenceMessage`

**Arquivo:** `app/Models/DiligenceMessage.php`
**Tabela:** `diligence_messages`

| Campo | Tipo | Descrição |
|---|---|---|
| `diligenceable_type` | string | Classe do dono (`monitoring`, `accountability_report`…) |
| `diligenceable_id` | unsignedBigInteger | ID do dono |
| `direction` | enum: `OUTBOUND` / `INBOUND` | Enviado pelo Efomento ou recebido do agente |
| `from_email` | string | Remetente |
| `to_email` | string | Destinatário |
| `subject` | string | Assunto |
| `body` | text | Corpo completo do e-mail |
| `imap_message_id` | string, nullable, unique | Message-ID do IMAP (evita duplicatas na sync) |
| `in_reply_to` | string, nullable | Header `In-Reply-To` para encadeamento |
| `sent_at` | datetime | Quando foi enviado/recebido |
| `read_at` | datetime, nullable | Quando a equipe leu (só INBOUND) |
| `created_by` | FK → users | Usuário que disparou (só OUTBOUND) |

**Traits:** `SoftDeletes`, `Auditable`, `HasCreatedBy`

**Relacionamento:**
```php
public function diligenceable(): MorphTo {
    return $this->morphTo();
}
```

---

### 3. Enum: `DiligenceDirection`

**Arquivo:** `app/Enums/DiligenceDirection.php`

```php
enum DiligenceDirection: string {
    case OUTBOUND = 'OUTBOUND';
    case INBOUND  = 'INBOUND';
}
```

---

### 4. Morph map — `AppServiceProvider`

Registrar o alias do novo model:

```php
Relation::morphMap([
    'agent'                => Agent::class,
    'notice'               => Notice::class,
    'project'              => Project::class,
    'opening'              => Opening::class,
    'monitoring'           => Monitoring::class,           // já existe
    'accountability_report'=> AccountabilityReport::class, // quando criado
]);
```

---

### 5. Monitoring model — mudança mínima

**Arquivo:** `app/Models/Monitoring.php`

Adicionar:
```php
public function diligenceMessages(): MorphMany {
    return $this->morphMany(DiligenceMessage::class, 'diligenceable')->orderBy('sent_at');
}
```

---

### 6. Nova Migration

**Arquivo:** `database/migrations/YYYY_MM_DD_create_diligence_messages_table.php`

```php
Schema::create('diligence_messages', function (Blueprint $table) {
    $table->id();
    $table->morphs('diligenceable');          // type + id + index
    $table->string('direction');
    $table->string('from_email');
    $table->string('to_email');
    $table->string('subject');
    $table->text('body');
    $table->string('imap_message_id')->nullable()->unique();
    $table->string('in_reply_to')->nullable();
    $table->datetime('sent_at');
    $table->datetime('read_at')->nullable();
    $table->foreignId('created_by')->nullable()->constrained('users');
    $table->timestamps();
    $table->softDeletes();
});
```

---

### 7. Novo Mailable: `DiligenceMail`

**Arquivo:** `app/Mail/DiligenceMail.php`

- Template Blade com corpo da diligência
- Header `Reply-To` apontando para o endereço IMAP monitorado
- Header `Message-ID` único gerado na criação (salvo em `imap_message_id`)

---

### 8. Novo Service: `DiligenceMessageService`

**Arquivo:** `app/Services/DiligenceMessageService.php`

```php
// Envia e-mail e persiste OUTBOUND
public function send(Model $diligenceable, string $body, User $sender): DiligenceMessage

// Sincroniza respostas do IMAP → persiste INBOUND
public function syncIncoming(): int
```

`$diligenceable` aceita qualquer model que use `MorphMany(DiligenceMessage)`.

---

### 9. Novo Controller: `DiligenceMessageController`

**Arquivo:** `app/Http/Controllers/DiligenceMessageController.php`

| Método | Rota | Descrição |
|---|---|---|
| `index(Project $project, string $stage)` | `GET /projetos/{project}/diligencias/{stage}` | Fio de mensagens do stage |
| `store(Request $request, Project $project, string $stage)` | `POST /projetos/{project}/diligencias/{stage}` | Envia nova diligência |

`{stage}` recebe o slug (`monitoramento`, `prestacao-de-contas`). O controller resolve o model correto a partir do slug.

Form Request: valida `body` (required, string, min:20).

---

### 10. Novas Rotas

**Arquivo:** `routes/web.php`

```php
Route::get('/projetos/{project}/diligencias/{stage}', [DiligenceMessageController::class, 'index'])
    ->name('diligences.index');

Route::post('/projetos/{project}/diligencias/{stage}', [DiligenceMessageController::class, 'store'])
    ->name('diligences.store');
```

---

### 11. Novo Command: `SyncDiligenceEmailsCommand`

**Arquivo:** `app/Console/Commands/SyncDiligenceEmailsCommand.php`

Comando: `app:sync-diligence-emails`

Chama `DiligenceMessageService::syncIncoming()`. Agendado em `routes/console.php` para rodar a cada poucos minutos.

---

### 12. Configuração de ambiente

Adicionar ao `.env.example`:

```env
IMAP_HOST=
IMAP_PORT=993
IMAP_USERNAME=
IMAP_PASSWORD=
IMAP_ENCRYPTION=ssl
DILIGENCE_REPLY_TO=diligencias@efomento.ce.gov.br
```

---

## Resumo dos arquivos afetados/criados

| Arquivo | Situação |
|---|---|
| `app/Enums/ProjectStageSlug.php` | **MODIFICAR** (add `PRESTACAO_DE_CONTAS`) |
| `app/Enums/DiligenceDirection.php` | **NOVO** |
| `app/Models/DiligenceMessage.php` | **NOVO** |
| `app/Models/Monitoring.php` | **MODIFICAR** (add `morphMany`) |
| `app/Mail/DiligenceMail.php` | **NOVO** |
| `app/Services/DiligenceMessageService.php` | **NOVO** |
| `app/Http/Controllers/DiligenceMessageController.php` | **NOVO** |
| `app/Http/Requests/Diligence/StoreDiligenceMessageRequest.php` | **NOVO** |
| `app/Console/Commands/SyncDiligenceEmailsCommand.php` | **NOVO** |
| `app/Observers/ProjectObserver.php` | **MODIFICAR** (add 7º stage) |
| `app/Providers/AppServiceProvider.php` | **MODIFICAR** (add morph map entries) |
| `database/migrations/..._create_diligence_messages_table.php` | **NOVO** |
| `routes/web.php` | **MODIFICAR** (add rotas) |
| `routes/console.php` | **MODIFICAR** (add schedule) |
| `composer.json` | **MODIFICAR** (add webklex/laravel-imap) |
| `.env.example` | **MODIFICAR** (add IMAP vars) |

> `AccountabilityReport` model e migration ficam para o planejamento de Prestação de Contas — quando criados, só precisam implementar `morphMany(DiligenceMessage)` para ganhar diligências automaticamente.

---

## Ambiente de desenvolvimento: e-mail real com GreenMail + Roundcube

> Decisão registrada em 10/06/2026. Estado na época: backend de diligência já implementado (`DiligenceMessage`, `DiligenceMessageService`, `DiligenceMail`, `SyncDiligenceEmailsCommand`, `webklex/laravel-imap`), porém `MAIL_MAILER=log` — nenhum disparo real e o loop INBOUND nunca exercitado.

### Por que não Mailpit

O Mailpit (`axllent/mailpit`) foi avaliado e **descartado como solução única**: ele apenas captura SMTP de saída (UI de visualização + POP3 opcional). **Não possui servidor IMAP nem permite responder e-mails** — o recurso foi recusado pelo mantenedor (issues [#72](https://github.com/axllent/mailpit/issues/72) e [#249](https://github.com/axllent/mailpit/issues/249)). Com ele seria possível ver a diligência enviada, mas nunca testar o `syncIncoming()` nem simular a resposta do agente.

### Abordagem escolhida

- **GreenMail** (`greenmail/standalone`) — servidor SMTP (3025) + IMAP (3143) de teste, uso interno na rede Docker. Com `-Dgreenmail.auth.disabled`, qualquer login/senha funciona e mailboxes são criadas sob demanda.
- **Roundcube** (`roundcube/roundcubemail`) — webmail em http://localhost:8025 para atuar como o agente: ler o e-mail da diligência e **respondê-lo**. A resposta cai na caixa monitorada (`diligencias@efomento.ce.gov.br`) no GreenMail e o `app:sync-diligence-emails` ingere via IMAP.

Loop completo em dev: app envia via SMTP → Roundcube lê e responde → sync IMAP captura → thread aparece na UI.

### Serviços no `docker-compose.yml`

```yaml
greenmail:
  image: greenmail/standalone:latest
  container_name: efomento-greenmail
  environment:
    GREENMAIL_OPTS: >-
      -Dgreenmail.setup.test.all
      -Dgreenmail.hostname=0.0.0.0
      -Dgreenmail.auth.disabled
      -Dgreenmail.verbose
  networks: [efomento]

roundcube:
  image: roundcube/roundcubemail:latest
  container_name: efomento-roundcube
  ports:
    - "8025:80"
  environment:
    ROUNDCUBEMAIL_DEFAULT_HOST: greenmail
    ROUNDCUBEMAIL_DEFAULT_PORT: 3143
    ROUNDCUBEMAIL_SMTP_SERVER: greenmail
    ROUNDCUBEMAIL_SMTP_PORT: 3025
  depends_on: [greenmail]
  networks: [efomento]
```

### Variáveis de ambiente (dev)

```env
MAIL_MAILER=smtp
MAIL_HOST=greenmail
MAIL_PORT=3025
MAIL_FROM_ADDRESS=diligencias@efomento.ce.gov.br

IMAP_HOST=greenmail
IMAP_PORT=3143
IMAP_USERNAME=diligencias@efomento.ce.gov.br
IMAP_PASSWORD=dev
IMAP_ENCRYPTION=false
IMAP_VALIDATE_CERT=false
```

Em produção, os valores apontam para a caixa institucional real (ssl/993). O `config/imap.php` já suporta `encryption => false` e `validate_cert` via env.

### ✅ Bug do `$messageId` (resolvido em 11/06/2026)

`app/Services/DiligenceMessageService.php` (método `send()`) usava `$messageId` no `create()` sem nunca defini-lo. **Já corrigido**: o Message-ID é gerado no formato `<diligence_{uuid}@domínio>` antes do envio, então o encadeamento `In-Reply-To` casa normalmente no `syncIncoming()`. Nenhuma ação pendente.

### Verificação do loop de ponta a ponta

1. `docker compose up -d` (greenmail e roundcube sobem) + `docker compose exec app php artisan config:clear`
2. Enviar diligência pela UI de Monitoramento para `agente@example.com` → registro `OUTBOUND` com `imap_message_id` preenchido
3. Abrir http://localhost:8025, logar como `agente@example.com` (qualquer senha) → responder o e-mail
4. `docker compose exec app php artisan app:sync-diligence-emails` → registro `INBOUND` com `in_reply_to` casando com o `OUTBOUND`
5. Conferir o fio na tela de Monitoramento

---

## Verificação

1. `docker compose exec app php artisan migrate` — cria `diligence_messages`
2. `docker compose exec app php artisan test --filter=DiligenceMessage` — testes unitários
3. Enviar diligência no Monitoramento → verificar registro `OUTBOUND` com `diligenceable_type = monitoring`
4. Rodar `app:sync-diligence-emails` → verificar registro `INBOUND`
5. Verificar thread exibida corretamente na UI de Monitoramento
6. Repetir passos 3–5 para Prestação de Contas quando implementado
