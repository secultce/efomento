# Planejamento: Fluxo Cíclico de Parcelas (Orçamento → Pagamento → Monitoramento × N)

> Documento interno de planejamento — não versionar.

---

## Contexto e motivação

O fluxo atual de etapas de um projeto é **linear**: ao ser criado, o `ProjectObserver` gera 7 registros
em `project_stages` com `order` de 1 a 7:

```
ABERTURA(1) → ANALISE_JURIDICA(2) → FORMALIZACAO(3) → ORCAMENTO(4) → PAGAMENTO(5) → MONITORAMENTO(6) → PRESTACAO_DE_CONTAS(7)
```

A equipe de negócio definiu uma nova regra: se `notices.installments > 1`, ao concluir o MONITORAMENTO
do ciclo N o projeto deve **retornar às 3 etapas do sub-ciclo** (ORCAMENTO → PAGAMENTO → MONITORAMENTO)
para o ciclo N+1, repetindo até que todos os ciclos sejam concluídos. Somente após o último MONITORAMENTO
o fluxo avança para PRESTACAO_DE_CONTAS.

Exemplo com `installments = 2`:
```
ABERTURA → ANALISE_JURIDICA → FORMALIZACAO → ORCAMENTO → PAGAMENTO → MONITORAMENTO(ciclo 1)
                                                                              ↓ [SOLICITAR PRÓXIMA PARCELA]
                                                          ORCAMENTO → PAGAMENTO → MONITORAMENTO(ciclo 2)
                                                                                          ↓
                                                                            PRESTACAO_DE_CONTAS → FIM
```

---

## Diagnóstico da arquitetura atual

### Modelos envolvidos

| Model | Relação com Project | Observação |
|---|---|---|
| `Budget` | 1:1 | Tem `HasMany Installment` — já suporta múltiplas parcelas por `installment_number` |
| `Installment` | via Budget | Campo `installment_number` (unique por budget) |
| `Payment` | 1:1 | Único por projeto — dados sobrescritos a cada ciclo |
| `Monitoring` | 1:1 | Único por projeto — dados sobrescritos a cada ciclo |
| `ProjectStage` | 1:N | 7 registros fixos; status é resetado por ciclo |

### Como a progressão funciona hoje

- `ProjectStageService::advance()` marca a etapa atual como `APROVADO` e ativa a próxima via `getNextStage()` (busca `order + 1`).
- `canAdvance()` garante que todas as etapas com `order` menor estejam `APROVADO`.
- `project_stages` tem `unique(['project_id', 'slug'])` — cada slug aparece **uma única vez** por projeto.
- `ProjectObserver::created()` cria as 7 etapas.
- `ProjectStageService::reject()` e `returnStage()` bloqueiam etapas posteriores.

---

## Solução escolhida: Reuso dos registros existentes + botão explícito

**Sem criar novos registros.** A abordagem é **resetar os status** das etapas ORCAMENTO, PAGAMENTO e
MONITORAMENTO quando o usuário solicitar a próxima parcela, reiniciando o sub-ciclo com os mesmos
registros de `project_stages`. O ciclo atual é rastreado por uma única coluna em `projects`.

### Por que esta abordagem

- **Sem novas migrations** de tabelas `payments` e `monitorings` — os registros existentes são sobrescritos.
- A constraint `unique(['project_id', 'slug'])` em `project_stages` é compatível: nunca há slugs duplicados.
- Nenhuma alteração em `canAdvance()`, `getNextStage()`, `TramitButton.vue` ou rotas existentes.
- O ciclo atual é visível e simples de consultar: `project.current_installment_cycle`.
- Ação explícita do usuário (botão com confirmação) — o avanço de ciclo não é automático ao tramitar.

### Como os dados ficam entre ciclos

| Entidade | Comportamento |
|---|---|
| `project_stages` (ORCAMENTO/PAGAMENTO/MONITORAMENTO) | Status resetado; mesmos registros reusados |
| `Budget` | Mantido; ciclo 2 adiciona `Installment` com `installment_number = 2` ao mesmo Budget |
| `Payment` | Dados sobrescritos no ciclo 2 (único registro por projeto) |
| `Monitoring` | Dados sobrescritos no ciclo 2 (único registro por projeto) |

---

## Implementação

### 1. Migration — `projects` (editar diretamente)

Adicionar coluna em `database/migrations/2026_03_16_161002_create_projects_table.php`:

```php
$table->unsignedTinyInteger('current_installment_cycle')->default(1)->after('notice_id');
```

Nenhuma outra migration precisa ser alterada.

---

### 2. Model — `app/Models/Project.php`

```php
protected $fillable = [
    // ... existentes ...
    'current_installment_cycle',   // ADICIONAR
];

protected $casts = [
    // ... existentes ...
    'current_installment_cycle' => 'integer',  // ADICIONAR
];
```

---

### 3. Novo método — `app/Services/ProjectStageService.php`

Adicionar método `requestNextInstallment(Project $project)`:

```php
public function requestNextInstallment(Project $project): void
{
    DB::transaction(function () use ($project) {
        $slugsToReset = [
            ProjectStageSlug::ORCAMENTO,
            ProjectStageSlug::PAGAMENTO,
            ProjectStageSlug::MONITORAMENTO,
        ];

        foreach ($slugsToReset as $slug) {
            $stage = $project->stages()->where('slug', $slug->value)->first();
            if (! $stage) continue;

            $isFirst = $slug === ProjectStageSlug::ORCAMENTO;
            $stage->update([
                'status'       => $isFirst ? ProjectStageStatus::EM_ANDAMENTO : ProjectStageStatus::PENDENTE,
                'started_at'   => $isFirst ? now() : null,
                'concluded_at' => null,
            ]);
        }

        $project->increment('current_installment_cycle');
    });
}
```

---

### 4. Novo endpoint — `app/Http/Controllers/ProjectStageController.php`

```php
public function requestNextInstallment(Project $project, ProjectStageService $service): \Illuminate\Http\RedirectResponse
{
    $notice = $project->notice;

    abort_if(
        ! $notice || $notice->installments <= 1,
        403, 'Projeto não possui múltiplas parcelas.'
    );

    abort_if(
        $project->current_installment_cycle >= $notice->installments,
        403, 'Todos os ciclos de parcelas já foram concluídos.'
    );

    $service->requestNextInstallment($project);

    return back();
}
```

---

### 5. Rota — `routes/web.php`

```php
Route::patch('/projetos/{project}/solicitar-proxima-parcela', [ProjectStageController::class, 'requestNextInstallment'])
    ->name('projects.stages.request-next-installment');
```

---

### 6. Frontend — `MonitoringTab.vue`

**Visibilidade do botão** (3 condições AND):

```js
const canRequestNextInstallment = computed(() =>
    props.project.notice?.installments > 1 &&
    props.project.current_installment_cycle < props.project.notice?.installments &&
    monitoringStage.value?.status === 'em_andamento'
)
```

> `project.notice.installments` já está disponível: `ProjectController::projectDetail()` carrega a relação
> `notice` e `ProjectResource` a repassa via `parent::toArray()`.
> `project.current_installment_cycle` é repasado pelo mesmo mecanismo após ser adicionado ao model.

**Ação do botão** (usar `useAlert` — SweetAlert2 não está instalado):

```js
import { useAlert } from '@/Composables/useAlert';
const { showAlert } = useAlert();

function requestNextInstallment() {
    showAlert({
        alertTitle: 'Solicitar próxima parcela',
        alertMessage: 'Ao confirmar, o ciclo de Orçamento, Pagamento e Monitoramento será reiniciado para a próxima parcela.',
        confirmText: 'Confirmar',
        action: () => {
            router.patch(
                route('projects.stages.request-next-installment', { project: props.project.id }),
                {},
                {
                    preserveScroll: true,
                    onSuccess: () => router.visit(window.location.pathname, { preserveState: false }),
                    onError: (errors) => {
                        const msg = Object.values(errors).flat().join(', ') || 'Erro ao solicitar próxima parcela';
                        showSnackbar(msg, 'error');
                    },
                }
            );
        },
    });
}
```

**Template** (botão renderizado acima do botão "Salvar Alterações"):

```html
<v-btn
    v-if="canRequestNextInstallment"
    variant="outlined"
    color="primary"
    @click="requestNextInstallment"
>
    Solicitar próxima parcela
</v-btn>
```

---

## O que NÃO muda

| Componente | Motivo |
|---|---|
| `ProjectObserver::created()` | Continua criando as 7 etapas fixas |
| `ProjectStage::canAdvance()` | Lógica por `order` não é afetada |
| `ProjectStage::getNextStage()` | Idem |
| `ProjectStageService::advance()` | Não intercepta MONITORAMENTO — o avanço de ciclo é via botão explícito |
| `ProjectStageService::reject()` / `returnStage()` | Inalterados |
| `TramitButton.vue` e rota `advance` | Sem acoplamento ao ciclo |
| `Budget` e `Installment` | Ciclo 2 adiciona `installment_number: 2` ao Budget existente |
| `payments` / `monitorings` (migrations) | Sem `installment_cycle` — dados sobrescritos por ciclo |

---

## Verificação / testes

```bash
# 1. Recriar banco com a migration editada
docker compose exec app php artisan migrate:fresh --seed

# 2. Suite de testes existente — nada deve quebrar
docker compose exec app php artisan test

# 3. Testes manuais

# Cenário A: installments = 1
# - Botão "SOLICITAR PRÓXIMA PARCELA" NÃO aparece no MonitoringTab

# Cenário B: installments = 2
# - Botão aparece quando MONITORAMENTO está EM_ANDAMENTO (cycle 1)
# - Ao confirmar: ORCAMENTO → EM_ANDAMENTO; project.current_installment_cycle = 2
# - Botão DESAPARECE (cycle 2 = installments 2 → não há próximo)
# - Tramitar normalmente ORCAMENTO → PAGAMENTO → MONITORAMENTO do ciclo 2
# - Tramitar MONITORAMENTO ciclo 2 → avança para PRESTACAO_DE_CONTAS (order 7, fluxo normal)

# Cenário C: installments = 3
# - Botão aparece no ciclo 1 → cycle = 2 após clicar
# - Botão aparece no ciclo 2 → cycle = 3 após clicar
# - Botão desaparece no ciclo 3
```

---

## Riscos e pontos de atenção

| Risco | Mitigação |
|---|---|
| Dados de Payment/Monitoring ciclo 1 sobrescritos no ciclo 2 | Comportamento esperado e aceito nesta abordagem |
| `getProgressPercentage()` em `Project.php` pode contar etapas aprovadas de forma errada ao resetar | Revisar o cálculo após implementar |
| Testes existentes que esperam exatamente 7 stages podem quebrar se o Observer mudar | Verificar factories e seeders de teste |
| Botão acessível antes de salvar o parecer técnico | Considerar chamar `saveMonitoring()` antes de `requestNextInstallment()` no frontend |
