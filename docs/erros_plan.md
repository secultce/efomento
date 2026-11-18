# Diagnóstico e Plano: Hierarquia de Tratamento de Erros (efomento)

## Contexto

O projeto trata erros de forma ad-hoc, tanto no backend quanto no frontend, apesar de já ter o Sentry integrado (só no backend) e 22 FormRequests com validações de negócio espalhadas pelas etapas do fluxo (Opening, LegalAnalysis, Formalization, Budget, Payment, Monitoring). Sem uma hierarquia de exceções, cada Controller reimplementa `try/catch` genérico, e cada tela Vue reimplementa a extração de mensagem de erro. Isso gera inconsistência de UX (mensagens diferentes para o mesmo tipo de falha), duplicação de código, captura manual e redundante do Sentry, e nenhuma visibilidade de erros puramente client-side (bugs de render, falhas de rede não tratadas).

Objetivo: desenhar um/a hierarquia de classes de exceção no backend + um composable central no frontend, que padronizem como erros de negócio, autorização e integração externa são reportados, logados e exibidos ao usuário — sem quebrar o fluxo Inertia existente (`form.errors`, `back()->withErrors()`).

Este documento nasceu como **diagnóstico + plano de implementação para aprovação**. Desde então, a fundação e boa parte da migração (passos 1-4, ver seção 3.2) já foram implementadas e mescladas — o documento agora funciona como **registro histórico da decisão + checklist do que ainda falta**. As seções 1-2 (hierarquia de exceções, handler central) descrevem uma implementação já concluída, não mais uma proposta; a seção 5 (estratégia de migração) mantém a numeração original dos passos apenas como referência de ordem, não como trabalho pendente — o estado real de cada etapa está na seção 3.2.

## Diagnóstico atual (verificado no código)

**Backend**
- `app/Exceptions/` não existe. Nenhuma exception customizada — tudo usa `\InvalidArgumentException`/`\Throwable`/`\Exception` genéricas.
- `bootstrap/app.php:29-36` — único tratamento: `render()` para `InvalidArgumentException` (JSON 422 se `expectsJson()`), sem `reportable()` customizado. `Integration::handles($exceptions)` cobre o resto via Sentry automático.
- `ProjectStageController.php` (métodos `advance`, `requestNextInstallment`, `return`) repete o padrão `catch (ValidationException)` / `catch (AuthorizationException)` com `\Sentry\captureException` manual / `catch (\InvalidArgumentException)` com `report()` / `catch (\Throwable)` — ~25 linhas de boilerplate só nesse controller.
- Mesmo padrão em `UserController`, `OpeningController`, `InstallmentController`, `ProjectController`.
- Services (`OpeningUpdateService`, `GoogleSheetsService`, `SpreadsheetImportService`, `PUMLGeneratorService`) capturam falhas técnicas (ex: Guzzle `RequestException`) e relançam como `\InvalidArgumentException` genérica, perdendo semântica do erro original.
- Nenhuma convenção de resposta JSON de erro (`{message, code}`) — a maior parte do app é Inertia (`back()->withErrors()`).

**Frontend**
- Nenhum `try/catch`/`.catch()` centralizado — cada tela usa `onError` inline (`MonitoringTab.vue:141-189`, `useLegalAnalysis.js:83`), reimplementando `Object.values(errors).join(', ')`.
- `useSnackbar.js` existe (toast global) mas não formata erro, só exibe texto pronto.
- Validação de campo (422) já funciona bem via `form.errors.<campo>` nativo do Inertia `useForm` + `error-messages` do Vuetify — **não mexer nisso**.
- Sem interceptor axios global, sem páginas de erro customizadas (403/500), sem Sentry no frontend (`@sentry/vue` não instalado).
- Existe `InputError.vue` legado (estilo Breeze/Tailwind), resquício de scaffolding, pouco usado no padrão Vuetify atual.

## Princípio orientador da hierarquia

Duas fronteiras claras (hierarquia rasa, não uma classe por regra):

- **Exception de domínio**: regra de negócio que o usuário entende e pode agir (ex.: "documentos pendentes para tramitar"). Mensagem pt-BR pronta para exibir.
- **Exception técnica/de integração**: falha de infraestrutura/integração (Guzzle, timeout do Google Sheets/Mapas Cultural). É logada com detalhe técnico, mas ao usuário mostra mensagem genérica — a exception técnica original (Guzzle `RequestException`/`ConnectionException`) é **envolvida** (wrapped, com `$previous`) em `ExternalServiceException` (`app/Exceptions/Integration/`) antes de subir ao controller.

`ValidationException` e `AuthorizationException` do Laravel **continuam com o comportamento padrão** — não entram na hierarquia nova, preservando 100% do fluxo `form.errors` já testado.

Trade-off assumido: hierarquia rasa (base + ~4 subclasses) em vez de uma classe por regra de negócio — PHP não tem checked exceptions, então o ganho de granularidade por tipo não compensa a manutenção. Granularidade real fica em `context()` e na mensagem, não na classe.

## 1. Hierarquia de exceções — `app/Exceptions/`

```
app/Exceptions/
├── AppException.php                      (abstract base)
├── Domain/
│   ├── StageTransitionException.php      (regra de tramitação de etapa — usa enums ProjectStageSlug/Status)
│   ├── DomainAuthorizationException.php  (autorização de negócio, distinta de Policy/Gate)
│   └── BusinessRuleException.php         (substituto direto de \InvalidArgumentException genérica)
└── Integration/
    └── ExternalServiceException.php      (Google Sheets, Mapas Cultural, etc.)
```

```php
abstract class AppException extends \RuntimeException
{
    public function __construct(
        string $userMessage,
        private readonly int $httpStatus = 422,
        private readonly array $context = [],
        ?\Throwable $previous = null,
    ) {
        parent::__construct($userMessage, 0, $previous);
    }

    public function getHttpStatus(): int { return $this->httpStatus; }
    public function context(): array { return $this->context; }
    public function shouldReport(): bool { return true; } // override para false em erros de fluxo esperado
}
```

Subclasses ilustrativas (reaproveitando os enums existentes em vez de reinventar):

```php
final class StageTransitionException extends AppException
{
    public static function invalidStatus(ProjectStage $stage, ProjectStageStatus $required): self
    {
        return new self(
            "A etapa \"{$stage->slug->label()}\" precisa estar \"{$required->label()}\" para ser tramitada.",
            httpStatus: 422,
            context: ['stage_id' => $stage->id, 'slug' => $stage->slug->value, 'status' => $stage->status->value],
        );
    }

    public function shouldReport(): bool { return false; } // erro de fluxo esperado, não bug
}

final class ExternalServiceException extends AppException
{
    public static function unavailable(string $service, \Throwable $previous): self
    {
        return new self(
            "Não foi possível se comunicar com {$service}. Tente novamente em instantes.",
            httpStatus: 503,
            context: ['service' => $service],
            previous: $previous,
        );
    }
}
```

`BusinessRuleException` fica como substituto direto e mecânico de `\InvalidArgumentException` nos Services que hoje lançam mensagem de negócio genérica, sem precisar de subclasse dedicada para cada regra pequena — evita explosão de classes.

## 2. Handler central — `bootstrap/app.php`

```php
->withExceptions(function (Exceptions $exceptions): void {
    // Reporte condicional ao Sentry — substitui Sentry::captureException manual espalhado pelos controllers
    $exceptions->reportable(fn (AppException $e) => $e->shouldReport());

    // Render único para toda a hierarquia de domínio
    $exceptions->render(function (AppException $e, Request $request) {
        if ($request->expectsJson() && ! $request->header('X-Inertia')) {
            return response()->json([
                'message' => $e->getMessage(),
                'code' => class_basename($e),
            ], $e->getHttpStatus());
        }

        return back()->withErrors(['message' => $e->getMessage()]);
    });

    // manter render(InvalidArgumentException) legado durante a migração (passo 3 remove)
    $exceptions->render(function (InvalidArgumentException $e, Request $request) {
        if ($request->expectsJson()) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    });

    Integration::handles($exceptions); // continua por último, cobre tudo que não for AppException
})
```

Pontos importantes:
- `ValidationException`/`AuthorizationException` não ganham `render()` novo — Inertia já trata nativamente (`$e->errors()` → `form.errors`), nada muda para os 22 FormRequests existentes.
- `reportable()` retornando `false` suprime o report por instância (via `shouldReport()`), eliminando a necessidade de `\Sentry\captureException($e)` manual nos controllers.

## 3. Convenção Services/Controllers

**Services**: trocar `throw new \InvalidArgumentException(...)` por `throw BusinessRuleException::...(...)` ou `throw StageTransitionException::invalidStatus(...)`, e envolver exceções de integração:

```php
// GoogleSheetsService — hoje:
throw new RuntimeException("Failed to fetch Google Sheet: {$e->getMessage()}", previous: $e);
// depois:
throw ExternalServiceException::unavailable('Google Sheets', $e);
```

### 3.1 Inventário real pós-varredura (após merge com develop)

A varredura em `app/Http/Controllers/` e `app/Services/` (feita após o merge, com a fundação do passo 1 já mesclada) revelou um escopo maior que a estimativa inicial. Segue o mapeamento completo antes de qualquer alteração:

**Controllers com `catch` a remover (5):**

| Controller | Catches atuais |
|---|---|
| `ProjectStageController` (`advance`, `requestNextInstallment`, `return`) | `ValidationException` / `AuthorizationException` (+ `\Sentry\captureException` manual) / `\InvalidArgumentException` / `\Throwable` |
| `UserController` (`store`, `assignRole`, `removeRole`) | 3× `catch (Throwable)` genérico |
| `InstallmentController::import` | `\InvalidArgumentException` / `\Throwable` |
| `OpeningController::update` | `catch (\Throwable)` |
| `ProjectController` (`assignProjectSupervisor`, `createDocument`) | 2× `catch (\Throwable)` |

`MonitoringController`, `NotificationController`, `PaymentController` não têm `catch` — fora de escopo. `FormalizationController` já usa `ValidationException::withMessages()` corretamente — **não mexer**.

**Services com exceções genéricas a migrar (inventário maior que o previsto):**

- **`ProjectStageService`** — não estava no plano original, mas é o motor de todas as regras de tramitação (o `ProjectStageController` é fino e delega para cá). 8× `InvalidArgumentException` (status de etapa, ciclo de parcelas) → `StageTransitionException`; 2× `AuthorizationException` (`ensureIsPrincipalSupervisor`, `ensureUserHasRole`) → `DomainAuthorizationException`. **Este é o arquivo central do piloto, não o controller.**
- `OpeningUpdateService::handle` — `catch (Exception) { throw new Exception(...) }` é um wrap redundante (mesma classe, sem ganho semântico) — simplificar removendo o wrap; `ensureCanAdvance` já usa `ValidationException::withMessages` corretamente, não mexer.
- `ProjectSupervisorService::assign` — `\InvalidArgumentException` (máx. 2 fiscais) + `\Exception` genérica ("projeto não possui abertura") → ambos `BusinessRuleException`.
- `InstallmentImportService::import` — 2× `\InvalidArgumentException` (planilha vazia / sem projetos vinculados) → `BusinessRuleException`.
- `Import::validateRequiredColumns` — não estava no inventário original. `\InvalidArgumentException` (colunas obrigatórias faltando na planilha) → `BusinessRuleException`, para que `InstallmentController::import` preserve a mensagem específica em vez de cair no catch genérico de `\Throwable`.
- `ProjectDocumentService::createDocument` — 2× `\Exception` genérica (conteúdo vazio, nenhum projeto selecionado) → `BusinessRuleException`.
- `Documents/DocumentTypeRegistry::resolve` — `\InvalidArgumentException` (combinação tipo/fase inválida) → `BusinessRuleException`.
- `NoticeService::createFromMapasIfMissing` — `InvalidArgumentException` (edital sem id externo) → `BusinessRuleException`.
- `GoogleSheetsService::fetchSheet`/segundo ponto de import — `RequestException` → `RuntimeException` (2 pontos) → `ExternalServiceException::unavailable('Google Sheets', $e)`, como já previsto.
- **`MapasClient`** — não estava explícito no plano original. 4× `RuntimeException` em chamadas HTTP/download da API Mapas Cultural → `ExternalServiceException::unavailable('Mapas Cultural', ...)`.

**Fora de escopo (catches técnicos/internos sem relação com UX de erro do usuário, não migrar):**
- `PUMLGeneratorService::getModelRelations` — sonda de reflection, engole silenciosamente por design.
- `SpreadsheetImportService::parseDate` — parse de data com fallback `null`, comportamento interno.
- `UserService::create` — falha de upload de avatar é degradação aceitável (já reportada via `report($e)`, não bloqueia o cadastro do usuário).

### 3.2 Status da migração

- ✅ **`ProjectStageService` + `ProjectStageController`** — migrados. `advance()`/`requestNextInstallment()` ficaram sem try/catch (Handler central resolve). `return()` manteve um catch único de `AppException` porque `ReturnProcessModal.vue` depende do contrato `back()->with('error', ...)` (flash de sessão, não `withErrors`) — mudar isso exigiria alterar o Vue, fora do escopo. Bug encontrado e corrigido no Handler: a condição `$request->header('X-Inertia')` não cobria requisições comuns/testes; trocada por `expectsJson() && !X-Inertia` → JSON, senão `back()->withErrors(['message' => ...])`.
- ✅ **`ProjectSupervisorService`, `InstallmentImportService`, `ProjectDocumentService`, `DocumentTypeRegistry`, `NoticeService`** — exceções genéricas trocadas por `BusinessRuleException`.
- ✅ **`GoogleSheetsService`, `MapasClient`** — trocados por `ExternalServiceException` (via factory `unavailable()` quando há `$previous`, ou construtor direto quando a falha vem de um `$response->failed()` sem exception original). Refinamentos pós-review (CodeRabbit, PR #449): `GoogleSheetsService::fetchSheet`/`fetchSheetWithLookup` passaram a capturar também `ConnectionException` (timeout/DNS), não só `RequestException` (falha na resposta HTTP); `MapasClient` deixou de embutir o corpo bruto da resposta upstream na mensagem da exceção e passou a redigir a query string das URLs de download antes de logar/reportar — evita vazar dados sensíveis (PII do agente, tokens em URL) em mensagens exibidas ao usuário ou reportadas ao Sentry.
- ✅ **`InstallmentController::import`** — catch ajustado de `\InvalidArgumentException` para `AppException` (necessário: `InstallmentImportService` não lança mais `InvalidArgumentException`, e sem o ajuste a mensagem específica cairia no catch genérico de `\Throwable`).
- ✅ **`ProjectController` (`assignProjectSupervisor`, `createDocument`)** — try/catch removidos, Handler central assume.
- ⏳ **Restante do passo 4**: `UserController`, `OpeningController` — únicos controllers do inventário (seção 3.1) ainda com `catch` a remover. `MonitoringController`, `NotificationController` e `PaymentController` não têm `catch` hoje e ficam fora de escopo (seção 3.1).
- ⏳ **Passo 5 (frontend)**: `useErrorHandler.js` + adoção nas Tabs.
- ⏳ **Passo 6**: Sentry no frontend.

Testes atualizados para refletir os novos tipos de exceção: `tests/Feature/ProjectStageFlowTest.php`, `tests/Feature/InstallmentImportServiceTest.php`, `tests/Feature/Document/DocumentTest.php`. Suíte completa (432 testes) passando após cada etapa.

**Controllers**: ficam finos — removem os blocos `catch`. Exemplo, `ProjectStageController::advance` passa de ~25 linhas de try/catch para:

```php
public function advance(Request $request, Project $project, ProjectStage $stage)
{
    $stage->load('project');

    if ($stage->slug === ProjectStageSlug::ABERTURA) {
        $this->openingUpdateService->ensureCanAdvance($project);
    }
    if ($stage->slug === ProjectStageSlug::FORMALIZACAO) {
        $this->formalizationService->ensureCanAdvance($project);
    }

    $nextStage = $this->stageService->advance($stage, $request->user());
    $this->notificationService->notifyStageAdvanced($stage, $nextStage, $request->user());

    return back()->with('success', 'Processo tramitado com sucesso!');
}
```

O Handler central resolve automaticamente qualquer `AppException` lançada por `ensureCanAdvance`/`advance`. `ValidationException` continua sendo relançada nativamente (nem precisa de catch, já propaga).

`UserController::assignRole/removeRole` — o `catch (Throwable)` genérico com mensagem fixa fica melhor como `BusinessRuleException` lançada de dentro de um pequeno serviço (ou inline), deixando o controller sem try/catch.

## 4. Frontend — `useErrorHandler` + Sentry

Novo `resources/js/Composables/useErrorHandler.js`:

```js
import { useSnackbar } from '@/Composables/useSnackbar';

export function useErrorHandler() {
    const { showSnackbar } = useSnackbar();

    // Para uso em onError do Inertia useForm/router (objeto de erros por campo)
    function handleInertiaError(errors, fallback = 'Ocorreu um erro. Tente novamente.') {
        const message = errors && Object.keys(errors).length
            ? Object.values(errors).flat().join(', ')
            : fallback;
        showSnackbar(message, 'error');
        return message;
    }

    // Para uso em catch de axios (fetchFiles em useLegalAnalysis, etc.)
    function handleAxiosError(error, fallback = 'Ocorreu um erro. Tente novamente.') {
        const data = error?.response?.data;
        const message = data?.errors
            ? Object.values(data.errors).flat().join(', ')
            : (data?.message ?? fallback);
        showSnackbar(message, 'error');
        if (import.meta.env.PROD && window.Sentry) window.Sentry.captureException(error);
        return message;
    }

    return { handleInertiaError, handleAxiosError };
}
```

Uso nas Tabs, por exemplo em `MonitoringTab.vue`:
```js
onError: (errors) => handleInertiaError(errors, 'Erro ao tramitar monitoramento'),
```
e em `useLegalAnalysis.js`:
```js
} catch (error) {
    handleAxiosError(error, 'Erro ao carregar documentos.');
}
```

Isso não muda o comportamento visual (mesmo texto/snackbar), só centraliza a lógica — chave para migração incremental sem regressão perceptível.

**Sentry no frontend (`@sentry/vue`)**: incluir no escopo. O backend já reporta, mas hoje qualquer erro JS puro (bug de render, erro de rede não tratado) não aparece em lugar nenhum. Instalar `@sentry/vue`, inicializar em `resources/js/app.js`:

```js
Sentry.init({
    app,
    dsn: import.meta.env.VITE_SENTRY_DSN,
    enabled: import.meta.env.PROD,
    integrations: [Sentry.browserTracingIntegration()],
});
```

Trade-off: bundle size adicional e nova env var (`VITE_SENTRY_DSN`); mitigado habilitando só em produção.

## 5. Estratégia de migração incremental

Ordem de rollout proposta (cada etapa é releasável isoladamente):

1. **Fundação, zero risco**: criar `AppException` + subclasses em `app/Exceptions/`, adicionar `reportable`/`render` no `bootstrap/app.php` (aditivo — não altera comportamento de nada existente, pois nenhuma exception nova está sendo lançada ainda). Manter o `render(InvalidArgumentException)` atual em paralelo até o passo 3.
2. **Piloto no fluxo mais crítico**: `ProjectStageController` (`advance`, `return`, `requestNextInstallment`) + `OpeningUpdateService`/`FormalizationService` — trocar `\InvalidArgumentException`/`AuthorizationException` manual por `StageTransitionException`/`BusinessRuleException`, remover os try/catch dos 3 métodos do controller. Validar em staging que Sentry e mensagens Inertia continuam idênticas ao usuário.
3. **Estender os serviços de integração incluídos no escopo**: `GoogleSheetsService` e `MapasClient` passam a lançar `ExternalServiceException`. `SpreadsheetImportService::parseDate` e `PUMLGeneratorService::getModelRelations` permanecem fora de escopo, conforme a seção 3.1. Depois disso, remover o `render(InvalidArgumentException)` legado do `bootstrap/app.php` (confirmar via `grep -rn "InvalidArgumentException" app/` que nada mais depende dele).
4. **Restante dos controllers com `catch`**: `InstallmentController` e `ProjectController` já migrados (seção 3.2); faltam `UserController` e `OpeningController`. `MonitoringController`, `NotificationController` e `PaymentController` estão fora de escopo (sem `catch` a remover, seção 3.1).
5. **Frontend em paralelo, começando pelas telas espelhadas ao passo 2**: criar `useErrorHandler.js`, aplicar primeiro em `MonitoringTab.vue` e `useLegalAnalysis.js` (já tratam o fluxo de tramitação de etapa), depois nas demais Tabs (`OpeningTab`, `PaymentTab`, `BudgetTab`, `FormalizationTab`) uma a uma — cada substituição é local a um `onError`, não quebra as demais.
6. **Sentry frontend**: instalar e configurar por último, já que é aditivo e não depende da hierarquia de exceptions.
7. **Cleanup**: remover `InputError.vue` legado (Breeze/Tailwind) quando confirmado que nenhuma tela ainda o referencia (`grep -rn InputError resources/js`).

Cada passo é reversível e não exige alterar rotas ou contratos de resposta existentes — o `back()->withErrors(['message' => ...])` continua sendo o formato de saída Inertia padrão em todos os passos, só muda quem monta essa string (Handler central em vez de catch disperso). Exceção documentada: `ProjectStageController::return()` mantém um catch próprio de `AppException` que devolve `back()->with('error', ...)` (flash de sessão), não `withErrors`, porque `ReturnProcessModal.vue` já depende desse contrato — ver seção 3.2.

## Arquivos críticos

- `bootstrap/app.php`
- `app/Exceptions/` (novo)
- `app/Http/Controllers/ProjectStageController.php`
- `app/Services/OpeningUpdateService.php`, `app/Services/GoogleSheetsService.php`
- `resources/js/Composables/useErrorHandler.js` (novo)
- `resources/js/Composables/useSnackbar.js`
- `resources/js/app.js` (init Sentry)
- `app/Enums/ProjectStageSlug.php`, `ProjectStageStatus.php` (reaproveitar labels)

## Verificação (quando a implementação for aprovada e iniciada)

- `docker compose exec app php artisan test --filter=ProjectStage` — garantir que fluxo de tramitação continua funcionando após trocar exceções.
- Testar manualmente no navegador (localhost:8080) um cenário de erro de tramitação (etapa bloqueada) e confirmar que a mensagem exibida via `back()->withErrors()` é idêntica à anterior.
- Conferir no painel Sentry que a captura automática (`Integration::handles`) continua registrando os erros técnicos, e que `StageTransitionException` (erro de fluxo esperado, `shouldReport() = false`) não polui o Sentry com ruído.
- `docker compose exec vite npx eslint resources/js --fix` após criar `useErrorHandler.js`.
