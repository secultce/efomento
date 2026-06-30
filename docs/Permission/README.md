# Sistema de Permissões — e-Fomento

## Visão geral

O sistema usa duas camadas complementares:

| Camada | Tecnologia | Arquivo-chave |
|--------|------------|---------------|
| Backend | `spatie/laravel-permission` + `App\Enums\Role` | `app/Enums/Role.php` |
| Frontend | Directive `v-permission` + composable `usePermissions` | `resources/js/Composables/usePermissions.js` |

O backend é a camada **autoritativa** — toda requisição HTTP deve ser validada aqui.  
O frontend é a camada de **UX** — desabilita elementos para usuários sem permissão, sem substituir a guarda do backend.

---

## Roles disponíveis

Definidas em `App\Enums\Role` e seedadas por `database/seeders/PermissionSeeder.php`.

| Enum case | Valor (string) | Descrição |
|-----------|----------------|-----------|
| `FOMENTATION` | `fomentation` | Fomento |
| `COORD_FOMENTATION` | `coord_fomentation` | Coord. Fomento |
| `FINANCIAL` | `financial` | Financeiro |
| `COORD_FINANCIAL` | `coord_financial` | Coord. Financeiro |
| `LEGAL_ANALYSIS` | `legal_analysis` | Análise Jurídica |
| `COORD_LEGAL` | `coord_legal` | Coord. Jurídico |
| `BUDGETARY` | `budgetary` | Orçamentário |
| `COORD_BUDGETARY` | `coord_budgetary` | Coord. Orçamentário |
| `MONITORING` | `monitoring` | Monitoramento |
| `COORD_MONITORING` | `coord_monitoring` | Coord. Monitoramento |
| `TRACKING` | `tracking` | Acompanhamento |
| `SUPER_ADMIN` | `super_admin` | Super Admin |

---

## Grupos de roles por etapa

`App\Enums\Role` expõe grupos estáticos prontos para uso. Cada grupo inclui sempre o `super_admin`.

| Método | Roles incluídas | Etapa correspondente |
|--------|-----------------|----------------------|
| `Role::fomentoRoles()` | fomentation, coord_fomentation, super_admin | Abertura |
| `Role::legalAnalysisRoles()` | legal_analysis, coord_legal, coord_financial, super_admin | Análise Jurídica |
| `Role::formalizationRoles()` | legal_analysis, coord_legal, super_admin | Formalização |
| `Role::budgetRoles()` | budgetary, coord_budgetary, super_admin | Orçamento |
| `Role::paymentRoles()` | financial, coord_financial, super_admin | Pagamento |
| `Role::monitoringRoles()` | monitoring, coord_monitoring, super_admin | Monitoramento / Prestação de Contas |

---

## Backend

### Regra: autorização vai no `authorize()` do Form Request

Nunca use `abort_unless` no controller. O lugar correto é o `authorize()` do Form Request correspondente — o Laravel lança 403 automaticamente quando retorna `false`.

```php
// app/Http/Requests/Notice/NoticeUpdateRequest.php
use App\Enums\Role;

public function authorize(): bool
{
    return $this->user()->hasAnyRole(Role::fomentoRoles());
}
```

O controller fica limpo:

```php
// app/Http/Controllers/NoticeController.php
public function update(NoticeUpdateRequest $request, Notice $notice)
{
    $notice->update($request->validated()); // authorize() já validou
}
```

### Verificação pontual em services ou controllers

Quando não há Form Request (ex: lógica interna de service), use `hasAnyRole` com o grupo do enum:

```php
use App\Enums\Role;

if (!$user->hasAnyRole(Role::fomentoRoles())) {
    throw new AuthorizationException('Você não tem permissão para esta ação.');
}
```

### Buscar usuários por role

```php
use App\Enums\Role;
use App\Models\User;

// Busca usuários de um grupo
$supervisors = User::role(Role::monitoringRoles())->get();

// Busca todos os roles do sistema (útil para seeders e listas)
$allRoles = Role::values(); // ['fomentation', 'coord_fomentation', ...]
```

### Adicionar um novo grupo de roles

1. Adicione o(s) case(s) necessário(s) ao enum `App\Enums\Role` se ainda não existirem
2. Adicione um método estático ao enum:

```php
public static function meuNovoGrupo(): array
{
    return [self::MEU_ROLE->value, self::SUPER_ADMIN->value];
}
```

3. Use `Role::meuNovoGrupo()` no `authorize()` do Form Request correspondente

---

## Frontend

### Como as roles chegam ao Vue

O middleware `HandleInertiaRequests` compartilha as roles do usuário logado via Inertia shared props:

```php
// app/Http/Middleware/HandleInertiaRequests.php
'auth' => [
    'roles' => $request->user()?->getRoleNames() ?? [],
    'permissions' => $request->user()?->getAllPermissions()->pluck('name') ?? [],
    // ...
],
```

Esses dados ficam disponíveis em qualquer componente Vue via `page.props.auth` — você **não precisa passar por props manuais**.

### `usePermissions` — computed semânticos prontos

Importe `usePermissions` para obter computed properties prontas, sem repetir arrays de roles:

```js
import { usePermissions } from '@/Composables/usePermissions';

const { canManageNotices } = usePermissions();
// canManageNotices é um computed<boolean> — reativo automaticamente
```

| Computed | Roles verificadas |
|----------|-------------------|
| `canManageNotices` | fomentation, coord_fomentation, super_admin |
| `canManageLegalAnalysis` | legal_analysis, coord_legal, coord_financial, super_admin |
| `canManageFormalization` | legal_analysis, coord_legal, super_admin |
| `canManageBudget` | budgetary, coord_budgetary, super_admin |
| `canManagePayment` | financial, coord_financial, super_admin |
| `canManageMonitoring` | monitoring, coord_monitoring, super_admin |
| `isSuperAdmin` | super_admin |

### `v-permission` — desabilitar elemento na UI

Use a directive `v-permission` com `condition` apontando para o computed de `usePermissions`:

```vue
<script setup>
import { usePermissions } from '@/Composables/usePermissions';

const { canManageNotices } = usePermissions();
</script>

<template>
    <v-btn
        v-permission="{
            condition: canManageNotices,
            message: 'Você não tem permissão para realizar esta ação',
        }"
        @click="openDialog"
    >
        Informe os dados
    </v-btn>
</template>
```

Quando `condition` é `false`, a directive:
- Adiciona `opacity-60` e `cursor-not-allowed` ao elemento
- Cria um overlay que intercepta cliques
- Exibe o `message` via snackbar ao clicar

### `useAuth` — verificações pontuais

Use `useAuth` diretamente quando precisar combinar role com permissão específica (`canPerform`), ou para verificações que não se encaixam nos grupos de `usePermissions`:

```js
import { useAuth } from '@/Composables/useAuth';

const { canPerform, hasRole } = useAuth();

// Combina super_admin com permissão granular
const canCreateCI = computed(() => hasRole('super_admin') || canPerform('ci.create'));

// Verifica role avulsa
const isTracking = computed(() => hasRole('tracking'));
```

### Adicionar um novo grupo de roles no frontend

Adicione um novo computed em `resources/js/Composables/usePermissions.js`:

```js
canManageMeuModulo: computed(() => hasRole(['meu_role', 'coord_meu_role', 'super_admin'])),
```

Os valores devem ser idênticos aos `value` dos cases em `App\Enums\Role`.

---

## Fluxo completo

```
PermissionSeeder
    └── cria roles no banco via Role::values()
           │
    App\Enums\Role
    ├── grupos (fomentoRoles, legalAnalysisRoles, ...)
    │       │
    │   FormRequest::authorize()          ← guarda HTTP (backend autoritativo)
    │   Service / Observer                ← lógica de negócio
    │
    HandleInertiaRequests
    └── compartilha auth.roles via Inertia shared props
               │
        useAuth().roles                   ← fonte de dados no Vue
               │
        usePermissions()                  ← computeds semânticos
               │
        v-permission / :disabled          ← feedback visual ao usuário
```

---

## O que NÃO fazer

| Errado | Certo |
|--------|-------|
| `abort_unless($user->hasAnyRole(['fomentation', ...]), 403)` no controller | `authorize()` no Form Request com `Role::fomentoRoles()` |
| `hasRole(['fomentation', 'coord_fomentation', 'super_admin'])` inline no componente | `const { canManageNotices } = usePermissions()` |
| Strings de role literais espalhadas | `Role::fomentoRoles()` no backend, `canManageNotices` no frontend |
| Confiar apenas no `v-permission` como guarda de segurança | Sempre validar no backend — frontend é só UX |
