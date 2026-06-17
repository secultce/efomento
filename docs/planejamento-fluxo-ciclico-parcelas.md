# Planejamento: Fluxo Cíclico de Parcelas (Orçamento → Pagamento → Monitoramento × N)

> Documento interno de planejamento — não versionar.

---

## Contexto e motivação

O fluxo atual de etapas de um projeto é **linear**: ao ser criado, o `ProjectObserver` gera 7 registros 
em `project_stages` com `order` de 1 a 7:

```
ABERTURA(1) → ANALISE_JURIDICA(2) → FORMALIZACAO(3) → ORCAMENTO(4) → PAGAMENTO(5) → MONITORAMENTO(6) → PRESTACAO_DE_CONTAS(7)
```

Ao tramitar MONITORAMENTO (ciclo único) a próxima etapa é PRESTACAO_DE_CONTAS — o projeto só encerra após ela.

A equipe de negócio definiu uma nova regra: se `notices.installments > 1`, ao tramitar MONITORAMENTO do ciclo N o projeto deve **retornar às 3 últimas etapas** (ORCAMENTO → PAGAMENTO → MONITORAMENTO) para o ciclo N+1, repetindo até que todos os ciclos sejam concluídos. Somente após o último MONITORAMENTO o fluxo avança para PRESTACAO_DE_CONTAS.

Exemplo com `installments = 2`:
```
ABERTURA → ANALISE_JURIDICA → FORMALIZACAO → ORCAMENTO(ciclo 1) → PAGAMENTO(ciclo 1) → MONITORAMENTO(ciclo 1)
                                                                                               ↓
                                                                   ORCAMENTO(ciclo 2) → PAGAMENTO(ciclo 2) → MONITORAMENTO(ciclo 2)
                                                                                                                       ↓
                                                                                                         PRESTACAO_DE_CONTAS → FIM
```

---

## Diagnóstico da arquitetura atual

### Modelos envolvidos

| Model | Relação com Project | Observação |
|---|---|---|
| `Budget` | 1:1 | Tem `HasMany Installment` |
| `Installment` | via Budget | Campo `installment_number` (unique por budget) |
| `Payment` | 1:1 (unique em project_id) | Problema: não suporta múltiplos ciclos |
| `Monitoring` | 1:1 (sem unique, mas tratado assim) | Problema: não suporta múltiplos ciclos |
| `AccountabilityReport` | 1:1 (nova entidade) | Prestação de contas — etapa final única |
| `ProjectStage` | 1:N | Progressão por `order` + `status` |

### Como a progressão funciona hoje

- `ProjectStageService::advance()` marca a etapa atual como `APROVADO` e ativa a próxima via `getNextStage()` (busca `order + 1`).
- `canAdvance()` garante que todas as etapas com `order` menor estejam `APROVADO`.
- `getNextStage()` / `getPreviousStage()` navegam puramente por `order`.
- `ProjectObserver::created()` cria as 6 etapas do ciclo 1.
- `ProjectStageService::reject()` e `returnStage()` bloqueiam etapas posteriores.

### O que o `Budget` / `Installment` já suporta

O `Budget` já prevê múltiplas parcelas via `Installment` (com `installment_number` e unique em `(budget_id, installment_number)`). O ORCAMENTO do ciclo 2 apenas adiciona `installment_number: 2` ao Budget existente — **sem necessidade de alterar Budget ou Installment**.

---

## Solução escolhida: Extensão dinâmica da fila de etapas

**Sem nova tabela.** A abordagem é estender a tabela `project_stages` com um campo `installment_cycle` e criar dinamicamente as etapas dos ciclos seguintes dentro do `ProjectStageService`, no momento exato em que MONITORAMENTO é tramitado.

### Por que esta abordagem

- `canAdvance()` e `getNextStage()` já funcionam por `order` — **não precisam de nenhuma alteração**.
- O histórico completo de cada ciclo fica preservado em registros distintos.
- O `TramitButton.vue` e a rota `PATCH /projetos/{project}/etapas/{stage}/tramitar` **não mudam**.
- A lógica de notificações e auditoria existente cobre os novos registros automaticamente.
- Não há risco de sobrescrever dados de ciclos anteriores.

### Como a tabela ficará com 2 parcelas

| order | slug                  | installment_cycle | status       |
|-------|-----------------------|-------------------|--------------|
| 1     | abertura              | 1                 | aprovado     |
| 2     | analise_juridica      | 1                 | aprovado     |
| 3     | formalizacao          | 1                 | aprovado     |
| 4     | orcamento             | 1                 | aprovado     |
| 5     | pagamento             | 1                 | aprovado     |
| 6     | monitoramento         | 1                 | aprovado     |
| 7     | orcamento             | 2                 | aprovado     |
| 8     | pagamento             | 2                 | aprovado     |
| 9     | monitoramento         | 2                 | aprovado     |
| 10    | prestacao_de_contas   | 1                 | em_andamento |

> `installment_cycle` é irrelevante para PRESTACAO_DE_CONTAS (sempre 1), pois é etapa única ao final de todos os ciclos.

---

## Implementação detalhada

### 1. Migrations (4 novas)

#### `add_installment_cycle_to_project_stages_table`
```php
$table->unsignedTinyInteger('installment_cycle')->default(1)->after('order');
```

#### `add_installment_cycle_to_payments_table`
```php
// Remover unique simples de project_id
$table->dropUnique(['project_id']);
// Adicionar coluna e nova unique composta
$table->unsignedTinyInteger('installment_cycle')->default(1)->after('project_id');
$table->unique(['project_id', 'installment_cycle']);
```

#### `add_installment_cycle_to_monitorings_table`
```php
$table->unsignedTinyInteger('installment_cycle')->default(1)->after('project_id');
$table->unique(['project_id', 'installment_cycle']);
```

#### `create_accountability_reports_table` (nova tabela — etapa final)
```php
$table->id();
$table->foreignId('project_id')->constrained()->cascadeOnDelete();
$table->unsignedTinyInteger('installment_cycle')->default(1); // sempre 1
$table->unique(['project_id', 'installment_cycle']);
// demais campos da prestação de contas
$table->timestamps();
```

---

### 2. Models

#### `app/Models/ProjectStage.php`
```php
protected $fillable = [
    // ... existentes ...
    'installment_cycle',   // ADICIONAR
];

protected $casts = [
    // ... existentes ...
    'installment_cycle' => 'integer',  // ADICIONAR
];
```

#### `app/Models/Payment.php`
```php
protected $fillable = [
    // ... existentes ...
    'installment_cycle',   // ADICIONAR
];

protected $casts = [
    // ... existentes ...
    'installment_cycle' => 'integer',  // ADICIONAR
];
```

#### `app/Models/Monitoring.php`
```php
protected $fillable = [
    // ... existentes ...
    'installment_cycle',   // ADICIONAR
];

protected $casts = [
    // ... existentes ...
    'installment_cycle' => 'integer',  // ADICIONAR
];
```

---

### 3. `app/Services/ProjectStageService.php` — método `advance()`

Após `$stage->markApproved()` e antes da lógica de ativar a próxima etapa, inserir:

```php
use Illuminate\Support\Facades\DB;

// Dentro de advance(), após $stage->markApproved():

if ($stage->slug === ProjectStageSlug::MONITORAMENTO) {
    $totalInstallments = $stage->project->notice->installments ?? 1;
    $nextCycle = $stage->installment_cycle + 1;

    if ($nextCycle <= $totalInstallments) {
        // Busca os responsible_sectors do ciclo 1 para reutilizar
        $sectors = $stage->project->stages()
            ->whereIn('slug', [
                ProjectStageSlug::ORCAMENTO->value,
                ProjectStageSlug::PAGAMENTO->value,
                ProjectStageSlug::MONITORAMENTO->value,
            ])
            ->where('installment_cycle', 1)
            ->pluck('responsible_sector', 'slug');

        $maxOrder = $stage->project->stages()->max('order');

        $newStages = [
            [ProjectStageSlug::ORCAMENTO,    $maxOrder + 1, ProjectStageStatus::EM_ANDAMENTO, now()],
            [ProjectStageSlug::PAGAMENTO,    $maxOrder + 2, ProjectStageStatus::PENDENTE,     null],
            [ProjectStageSlug::MONITORAMENTO, $maxOrder + 3, ProjectStageStatus::PENDENTE,    null],
        ];

        DB::transaction(function () use ($stage, $nextCycle, $newStages, $sectors) {
            foreach ($newStages as [$slug, $order, $status, $startedAt]) {
                $stage->project->stages()->create([
                    'slug'               => $slug,
                    'order'              => $order,
                    'installment_cycle'  => $nextCycle,
                    'responsible_sector' => $sectors[$slug->value] ?? [],
                    'status'             => $status,
                    'started_at'         => $startedAt,
                ]);
            }
        });

        return $stage->project->stages()
            ->where('order', $maxOrder + 1)
            ->first();
    }

    // Sem ciclos restantes → avança para PRESTACAO_DE_CONTAS (última etapa do projeto)
    $next = $stage->getNextStage(); // order + 1 aponta para prestacao_de_contas
    if ($next) {
        $next->update([
            'status'     => ProjectStageStatus::EM_ANDAMENTO,
            'started_at' => now(),
        ]);
    }
    return $next?->fresh();
}

// Fluxo normal para as demais etapas:
$next = $stage->getNextStage();
if ($next) {
    $next->update([
        'status'     => ProjectStageStatus::EM_ANDAMENTO,
        'started_at' => now(),
    ]);
}
return $next?->fresh();
```

> **Atenção:** o `project->notice` precisa estar carregado. Verificar se `advance()` recebe o projeto com `notice` eager-loaded ou adicionar `$stage->project->load('notice')` se necessário.

---

### 4. `app/Http/Requests/Payment/PaymentStoreRequest.php`

Trocar a regra unique:

```php
// ANTES:
Rule::unique('payments', 'project_id')

// DEPOIS:
Rule::unique('payments')->where(fn ($q) => $q->where('installment_cycle', $this->installment_cycle))

// ADICIONAR campo nas regras:
'installment_cycle' => ['required', 'integer', 'min:1'],
```

Mesma alteração em `PaymentUpdateRequest.php`, ajustando o `ignore` para o registro atual.

---

### 5. `app/Http/Controllers/ProjectController.php` — `projectDetail()`

Adicionar nas props do Inertia:

```php
'noticeInstallments' => $project->notice->installments ?? 1,
```

Garantir que `payment`, `monitoring` e `notice` estejam no `load()`:

```php
$project->load([
    // ... existentes ...
    'notice',
    'payment',
    'monitoring',
]);
```

---

### 6. Frontend — tabs ORCAMENTO, PAGAMENTO, MONITORAMENTO

A aba recebe `project.stages` com todos os ciclos. Para identificar a etapa ativa do ciclo correto:

```js
// Pega a etapa ativa (em_andamento) de um determinado slug
const activeStage = project.stages.find(s => s.slug === 'orcamento' && s.status === 'em_andamento')

// installment_cycle da etapa ativa indica qual parcela exibir
const currentCycle = activeStage?.installment_cycle ?? 1

// Na aba ORCAMENTO: filtra a parcela do ciclo atual
const currentInstallment = project.budget?.installments?.find(i => i.installment_number === currentCycle)
```

Para exibição de histórico (ver ciclos anteriores), filtrar por `installment_cycle`:

```js
const stagesForCycle = (cycle) => project.stages.filter(s => s.installment_cycle === cycle)
```

O `TramitButton.vue` **não muda** — continua passando a action que chama `route('projects.stages.advance', {project, stage})`.

---

## O que NÃO muda

| Componente | Motivo |
|---|---|
| `ProjectObserver::created()` | Passa a criar 7 etapas: as 6 originais + `prestacao_de_contas` (order 7, `installment_cycle = 1`) |
| `ProjectStage::canAdvance()` | Lógica por `order` cobre os novos registros naturalmente |
| `ProjectStage::getNextStage()` | Idem — `order + 1` funciona para qualquer ciclo |
| `ProjectStageService::reject()` | Bloqueia etapas posteriores por `order` — sem impacto |
| `ProjectStageService::returnStage()` | Idem |
| `Budget` e `Installment` | O ciclo 2 adiciona `installment_number: 2` ao Budget existente |
| `TramitButton.vue` | Interface genérica — sem acoplamento ao ciclo |
| Rotas | Nenhuma rota nova necessária |

---

## Verificação / testes

```bash
# 1. Rodar as migrations
docker compose exec app php artisan migrate

# 2. Rodar suite de testes existente — nada deve quebrar
docker compose exec app php artisan test

# 3. Testes manuais

# Cenário A: installments = 1 (comportamento original)
# - Tramitar MONITORAMENTO ciclo 1 → avança para PRESTACAO_DE_CONTAS (order 7)
# - Tramitar PRESTACAO_DE_CONTAS → projeto encerra

# Cenário B: installments = 2
# - Tramitar MONITORAMENTO ciclo 1 → surgem etapas order 7, 8, 9 com installment_cycle = 2
# - Etapa 7 (ORCAMENTO ciclo 2) fica EM_ANDAMENTO
# - Tramitar ORCAMENTO, PAGAMENTO, MONITORAMENTO do ciclo 2
# - Ao tramitar MONITORAMENTO ciclo 2 → avança para PRESTACAO_DE_CONTAS (order 10)
# - Tramitar PRESTACAO_DE_CONTAS → projeto encerra

# Cenário C: installments = 3
# - Idem, com um terceiro ciclo sendo criado ao tramitar MONITORAMENTO ciclo 2
# - Ao tramitar MONITORAMENTO ciclo 3 → avança para PRESTACAO_DE_CONTAS

# 4. Verificar que canAdvance() impede tramitar ORCAMENTO ciclo 2
#    se MONITORAMENTO ciclo 1 não estiver APROVADO
```

---

## Riscos e pontos de atenção

| Risco | Mitigação |
|---|---|
| `project->notice` não eager-loaded em `advance()` | Verificar e adicionar `load('notice')` se necessário, ou garantir no controller |
| `getProgressPercentage()` em `Project.php` soma etapas aprovadas / total — com ciclos extras o denominador muda | Revisar o cálculo para refletir o total planejado real (base: 7 + 3×(N-1) etapas) |
| Testes existentes de `ProjectStageService` podem não cobrir MONITORAMENTO com ciclos | Adicionar casos de teste para os novos cenários, incluindo avanço para PRESTACAO_DE_CONTAS |
| `PaymentStoreRequest` unique quebra se `installment_cycle` não vier no request | Garantir que o frontend sempre envie `installment_cycle` ao criar Payment |
| `ProjectObserver::created()` cria 6 etapas hoje — precisa incluir `prestacao_de_contas` (order 7) | Atualizar o observer e os factories/seeders de teste que esperam exatamente 6 etapas |
| PRESTACAO_DE_CONTAS não tem `installment_cycle` relevante, mas a coluna existe | Definir convenção (sempre 1) e garantir que `canAdvance()` não seja afetado |
