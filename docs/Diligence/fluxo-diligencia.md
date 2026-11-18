# Fluxo de Diligências

Diligência é o mecanismo de comunicação formal entre a equipe interna e o proponente do projeto durante uma etapa específica. O sistema suporta envio (OUTBOUND) e recebimento (INBOUND) de mensagens via e-mail, com rastreamento completo pela tabela `diligence_messages`.

---

## Modelo de dados

### `DiligenceMessage`

Polimórfico via `diligenceable_type` / `diligenceable_id`. Qualquer etapa do projeto pode ser a origem de uma diligência, bastando implementar a relação `diligenceMessages()`.

| Campo | Descrição |
|---|---|
| `diligenceable_type / id` | Etapa dona da diligência (ex: `Monitoring`) |
| `direction` | `OUTBOUND` (enviada) ou `INBOUND` (recebida) |
| `imap_message_id` | ID único do e-mail no padrão RFC 2822 (`<id@efomento>`) |
| `in_reply_to` | `imap_message_id` da mensagem à qual esta responde |
| `sent_at` | Data/hora do envio ou recebimento real |

A cadeia `imap_message_id` ↔ `in_reply_to` é o elo que vincula respostas externas de volta à diligência correta no sistema.

---

## `DiligenceMessageService`

### `send()`

Envia uma diligência para o proponente.

```
Controller → DiligenceMessageService::send()
               ├── Gera um imap_message_id único
               ├── Busca o último imap_message_id da thread (in_reply_to)
               ├── Persiste DiligenceMessage com direction = OUTBOUND
               └── Dispara o e-mail via Mail::to() + DiligenceMail
```

O `in_reply_to` encadeia as mensagens em thread — tanto no cliente de e-mail do proponente quanto na consulta do sistema.

### `syncIncoming()`

Sincroniza respostas recebidas via IMAP. Chamado pelo comando Artisan `app:sync-diligence-emails`.

```
SyncDiligenceEmailsCommand
  └── DiligenceMessageService::syncIncoming()
        ├── Conecta ao servidor IMAP (conta 'default')
        ├── Busca mensagens não lidas (unseen) da INBOX
        ├── Para cada mensagem:
        │     ├── Ignora se imap_message_id já existe (deduplicação)
        │     ├── Localiza a DiligenceMessage pelo in_reply_to
        │     ├── Ignora se não houver correspondência (e-mail externo sem contexto)
        │     └── Persiste nova DiligenceMessage com direction = INBOUND
        └── Garante disconnect() via try/finally (mesmo em caso de exceção)
```

> **Atenção:** o comando `app:sync-diligence-emails` ainda **não está agendado** em `routes/console.php`. É necessário adicioná-lo ao scheduler para que a sincronização ocorra automaticamente.

### Tratamento de erros

O `syncIncoming()` deixa as exceções propagarem naturalmente. O command captura qualquer `Throwable`, chama `report($e)` (que enviará ao Sentry quando instalado) e retorna `Command::FAILURE`, permitindo que monitoramentos externos detectem a falha pelo exit code.

---

## Rotas HTTP

```
GET  /projetos/{project}/diligencias/{stage}   → DiligenceMessageController@index
POST /projetos/{project}/diligencias/{stage}   → DiligenceMessageController@store
```

O parâmetro `{stage}` é o valor do enum `ProjectStageSlug` (ex: `MONITORAMENTO`).

---

## Interface `DiligenceableResolver` e o Registry

### O problema original

O controller resolvía qual modelo era o "dono" da diligência com um `match` fechado:

```php
// Antes — fechado para extensão
return match ($slug) {
    ProjectStageSlug::MONITORAMENTO => $project->monitoring,
    default => abort(404),
};
```

Adicionar uma nova fase exigia modificar o controller diretamente, violando o **Princípio Aberto/Fechado** (OCP).

### A solução

Cada fase que suporta diligências implementa a interface `App\Contracts\DiligenceableResolver`:

```php
interface DiligenceableResolver
{
    public function resolve(Project $project): Model;
}
```

A implementação atual para Monitoramento:

```php
// App\Services\Diligenceable\MonitoringDiligenceableResolver
public function resolve(Project $project): Model
{
    return $project->monitoring;
}
```

O `DiligenceableResolverRegistry` (singleton registrado no `AppServiceProvider`) mantém o mapa `slug → resolver` e centraliza o `abort(404)`:

```php
// AppServiceProvider::register()
$this->app->singleton(DiligenceableResolverRegistry::class, fn () => new DiligenceableResolverRegistry([
    ProjectStageSlug::MONITORAMENTO->value => new MonitoringDiligenceableResolver,
]));
```

O controller apenas delega:

```php
$diligenceable = $this->registry->resolve($project, ProjectStageSlug::from($stage));
```

### Como adicionar uma nova fase

1. Criar `app/Services/Diligenceable/NomeDaFaseDiligenceableResolver.php` implementando `DiligenceableResolver`
2. Garantir que o model da fase possua a relação `diligenceMessages()` via `morphMany`
3. Registrar no `AppServiceProvider`:

```php
ProjectStageSlug::NOVA_FASE->value => new NomeDaFaseDiligenceableResolver,
```

Nenhum outro arquivo precisa ser alterado.
