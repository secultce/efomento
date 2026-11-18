# NotificationService

Serviço central para disparo de notificações do sistema. Toda notificação enviada a usuários deve ser feita através deste serviço, mantendo os destinatários e o conteúdo de cada evento em um único lugar.

Internamente usa `Notify` (fluent helper) + `AppNotification` (Laravel Notification via canal `database`), que persiste os dados na tabela `notifications`.

---

## Estrutura da tabela `notifications`

Cada notificação gravada tem a coluna `data` com o seguinte formato:

```json
{
    "title": "Título da notificação",
    "message": "Mensagem descritiva do evento.",
    "type": "warning",
    "meta": {
        "chave": "valor extra específico do evento"
    }
}
```

Tipos disponíveis: `default`, `success`, `error`, `warning`, `info`.

---

## Métodos de evento

### `notifyProcessReturned(Project $project, string $reason, array $roles): void`

Notifica usuários de roles específicas quando um processo é devolvido para ajustes.

**Destinatários:** definidos pelo chamador via `$roles`  
**Tipo:** `warning`

**`data` gravado:**
```json
{
    "title": "Processo devolvido",
    "message": "O processo \"Nome do projeto\" foi devolvido para ajustes.",
    "type": "warning",
    "meta": {
        "reason": "Motivo digitado pelo usuário no modal de devolução."
    }
}
```

**Uso no controller:**
```php
$this->notificationService->notifyProcessReturned(
    $project,
    $request->validated('reason'),
    ['fomentation', 'coord_fomentation']
);
```

---

## Como adicionar um novo evento

1. Crie um método público no `NotificationService` seguindo o padrão `notify<NomeDoEvento>`.
2. Defina os destinatários via `User::role([...])->get()`.
3. Chame o método adequado do `Notify`: `.success()`, `.warning()`, `.info()`, `.error()` ou `.default()`.
4. Passe dados extras no terceiro parâmetro como array (aparece em `meta` no `data`).
5. Injete o `NotificationService` no controller ou service que dispara o evento.
6. Documente o novo método neste arquivo.

> **Por que o retorno é `void`?**
> Os métodos de envio do `Notify` (`.warning()`, `.success()`, etc.) já retornam `void` — o disparo acontece dentro do método do serviço. Retornar `Notify` forçaria o chamador a completar o envio (ex.: `.warning(...)`), o que quebraria o encapsulamento: o controller voltaria a saber o tipo e o conteúdo da notificação, que é exatamente o que queremos evitar.

**Exemplo:**
```php
public function notifyStageAdvanced(Project $project, ProjectStage $stage): void
{
    $users = User::role($stage->responsible_sector)->get();

    $this->notify->users($users)->success(
        'A etapa "'.$stage->slug->label().'" do processo "'.$project->title_project.'" foi tramitada.',
        'Etapa tramitada'
    );
}
```

---

## Responsible sector por etapa

O `responsible_sector` de cada etapa define quais roles têm acesso a ela (tramitar, devolver, etc.). Esses valores são definidos em dois lugares:

| Arquivo | Quando é usado |
|---|---|
| `app/Observers/ProjectObserver.php` | Criação real de projetos — fonte de verdade para produção |
| `database/factories/ProjectStageFactory.php` | Testes automatizados |

> Sempre que alterar as roles de uma etapa, atualize os **dois** arquivos.

### Roles por etapa (estado atual)

| Etapa | `responsible_sector` |
|---|---|
| `abertura` | `super_admin`, `fomentation`, `coord_fomentation` |
| `analise_juridica` | `super_admin`, `coord_financial`, `legal_analysis`, `coord_legal` |
| `formalizacao` | `super_admin`, `legal_analysis`, `coord_legal` |
| `orcamento` | `super_admin`, `budgetary`, `coord_budgetary` |
| `pagamento` | `super_admin`, `financial`, `coord_financial` |
| `monitoramento` | `super_admin`, `monitoring`, `coord_monitoring` |

> `super_admin` está presente em todas as etapas pois tem acesso irrestrito ao sistema.

---

## Métodos de leitura/gerenciamento

Além dos eventos, o serviço expõe métodos para os controllers de notificação:

| Método | Descrição |
|---|---|
| `getUserNotifications(User, filters, pagination)` | Retorna notificações paginadas do usuário com contagem de não lidas |
| `getUnreadCount(User)` | Retorna a contagem de notificações não lidas |
| `markAsRead(User, notificationId)` | Marca uma notificação específica como lida |
| `markAllAsRead(User)` | Marca todas as notificações do usuário como lidas |
