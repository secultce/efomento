# Estratégia de Ciclo de Parcelas — Design Pattern Strategy

## Contexto

Em `ProjectStageService::requestNextInstallment()`, a transação hardcodeia quais etapas
formam o ciclo repetível e qual é o ponto de reinício:

```php
$project->stages()
    ->whereIn('slug', [
        ProjectStageSlug::ORCAMENTO,
        ProjectStageSlug::PAGAMENTO,
        ProjectStageSlug::MONITORAMENTO,
    ])
    ->update([...BLOQUEADO...]);

$project->stages()
    ->where('slug', ProjectStageSlug::ORCAMENTO)
    ->update([...EM_ANDAMENTO...]);
```

**Problema:** o Service conhece quais slugs formam o ciclo. Se uma nova etapa for inserida
no ciclo ou um tipo de edital tiver um ciclo diferente, o developer precisa lembrar de
atualizar essa lista na camada de serviço — responsabilidade que não pertence a ela.

---

## Por que Strategy

O padrão Strategy encapsula **um algoritmo variável** em uma classe separada.
O consumidor (Service) fala com uma interface e não sabe qual implementação está por baixo.

```
ProjectStageService
      │ usa
      ▼
InstallmentCycleStrategy  (interface)
      │
      ├─► StandardInstallmentCycleStrategy   ← ciclo atual: ORC → PAG → MON
      └─► OutraNoticeTypeStrategy            ← ciclo diferente por tipo de edital
```

**Benefícios:**
- Aberto para extensão, fechado para modificação (OCP): nova variação de ciclo = nova classe, o Service não muda
- O conhecimento de "quais etapas formam o ciclo" vai para a implementação da strategy, não para o Service
- Testável de forma isolada: cada strategy pode ser testada unitariamente

---

## Implementação

### 1. Interface

```php
// app/Contracts/InstallmentCycleStrategy.php

namespace App\Contracts;

use App\Enums\ProjectStageSlug;

interface InstallmentCycleStrategy
{
    /** @return array<ProjectStageSlug> etapas que serão resetadas para BLOQUEADO */
    public function stagesToReset(): array;

    /** etapa que inicia o novo ciclo (recebe status EM_ANDAMENTO) */
    public function activationStage(): ProjectStageSlug;
}
```

### 2. Implementação concreta

```php
// app/Services/Strategies/StandardInstallmentCycleStrategy.php

namespace App\Services\Strategies;

use App\Contracts\InstallmentCycleStrategy;
use App\Enums\ProjectStageSlug;

class StandardInstallmentCycleStrategy implements InstallmentCycleStrategy
{
    public function stagesToReset(): array
    {
        return [
            ProjectStageSlug::ORCAMENTO,
            ProjectStageSlug::PAGAMENTO,
            ProjectStageSlug::MONITORAMENTO,
        ];
    }

    public function activationStage(): ProjectStageSlug
    {
        return ProjectStageSlug::ORCAMENTO;
    }
}
```

### 3. Service recebe a strategy via injeção

```php
// app/Services/ProjectStageService.php

class ProjectStageService
{
    public function __construct(
        private Notify $notify,
        private InstallmentCycleStrategy $cycleStrategy,
    ) {}

    public function requestNextInstallment(Project $project, User $user): void
    {
        // ... validações inalteradas ...

        DB::transaction(function () use ($project, $monitoringStage) {
            $monitoringStage->markApproved();
            $project->increment('current_installment_cycle');

            $project->stages()
                ->whereIn('slug', $this->cycleStrategy->stagesToReset())
                ->update([
                    'status'           => ProjectStageStatus::BLOQUEADO,
                    'started_at'       => null,
                    'concluded_at'     => null,
                    'rejection_reason' => null,
                ]);

            $project->stages()
                ->where('slug', $this->cycleStrategy->activationStage())
                ->update([
                    'status'     => ProjectStageStatus::EM_ANDAMENTO,
                    'started_at' => now(),
                ]);
        });
    }
}
```

### 4. Binding no AppServiceProvider

```php
// app/Providers/AppServiceProvider.php

$this->app->bind(
    \App\Contracts\InstallmentCycleStrategy::class,
    \App\Services\Strategies\StandardInstallmentCycleStrategy::class,
);
```

---

## Evolução futura

Se um novo tipo de edital exigir um ciclo diferente (ex: incluir AUDITORIA entre PAGAMENTO
e MONITORAMENTO):

1. Criar `AuditInstallmentCycleStrategy` implementando a mesma interface
2. Resolver qual strategy usar via factory ou binding condicional baseado em `$project->notice->type`
3. O `ProjectStageService` **não precisa ser alterado**

---

## Arquivos envolvidos

| Arquivo | Ação |
|---|---|
| `app/Contracts/InstallmentCycleStrategy.php` | criar interface |
| `app/Services/Strategies/StandardInstallmentCycleStrategy.php` | criar implementação |
| `app/Services/ProjectStageService.php` | injetar strategy, remover slugs hardcoded |
| `app/Providers/AppServiceProvider.php` | registrar binding |

## Verificação

Os testes de `requestNextInstallment` em `ProjectStageFlowTest` e `ProjectStageControllerTest`
cobrem o comportamento observável e **não precisam mudar** — testam resultado, não implementação interna.

```bash
docker compose exec app php artisan test tests/Feature/ProjectStageFlowTest.php tests/Feature/ProjectStageControllerTest.php
```
