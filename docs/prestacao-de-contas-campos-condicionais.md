# Aba "Prestação de Contas" — campos condicionais (issue #319)

## Contexto

A [issue #319](https://github.com/secultce/efomento/issues/319) pede a implementação da aba de
projeto **Prestação de Contas**, usada pelo fiscal e pela autoridade julgadora para conferir a
execução do objeto proposto no plano de trabalho: visita presencial, Relatório de Execução do
Objeto Cultural (REOC), Parecer Técnico, Parecer Conclusivo, emissão do Termo de Aceitação do
Objeto (TADO), diligências e, quando aplicável, Medidas Cabíveis.

O critério de aceitação da issue descreve **~34 campos** cuja exibição depende da resposta de
campos anteriores, em cascata de até 3-4 níveis, com **convergência**: caminhos diferentes do
formulário terminam habilitando o mesmo par de campos (ex.: "Qual a data de envio para
Autoridade Julgadora?" + "Necessita de Relatório Financeiro?" é reaberto por pelo menos 4 ramos
distintos da árvore de decisão).

O ponto levantado pelo usuário é que essas regras de negócio (quais campos existem, quais opções
cada um tem, quando aparecem) **tendem a mudar** — é uma área normativa sujeita a revisão. Por
isso o objetivo não é só implementar a tela descrita hoje, mas fazer isso de um jeito onde alterar
uma regra amanhã seja editar dados, não reescrever template.

### Estado atual do código (achados da exploração)

- `PRESTACAO_DE_CONTAS` já existe em `app/Enums/ProjectStageSlug.php` (7ª etapa, depois de
  `MONITORAMENTO`) e já é semeada como `ProjectStage` pelo `ProjectObserver` — mas **não tem**
  model de dados, migration, controller, rotas nem tab/schema no frontend. É stage nova, do zero.
- As 6 tabs existentes (`Opening`, `LegalAnalysis`, `Formalization`, `Budget`, `Payment`,
  `Monitoring`) seguem o padrão `SplitScreenTab` + `Schemas/<Etapa>/{viewSections,formSections}.js`
  + `SectionForm.vue`. Hoje `formSections.js` só declara **títulos de seção**; os campos em si e
  qualquer `v-if` são escritos à mão dentro do `<template>` da Tab. **Não existe, em nenhuma tab
  atual, um campo condicionado ao valor de outro campo do formulário** — só existem `v-if`
  condicionados a dados já persistidos do projeto (ex. `BudgetTab.vue`).
- Não existe no projeto nenhum motor de regras, wizard/stepper, nem composable de visibilidade de
  campo.
- Persistência hoje é sempre coluna fixa por tabela de etapa (nunca EAV/JSON genérico para
  respostas de usuário — a única coluna jsonb existente, `monitorings.data_registration`, guarda o
  payload bruto importado do Mapa Cultural, não resposta de formulário).
- `resources/js/Components/DiligenceChat.vue` já mapeia o label do stage `prestacao_de_contas`, e
  `resources/js/Pages/Projects/Index.vue` já roteia esse slug para a chave de tab `monitoring` —
  sinais de que outras partes do sistema já anteciparam essa etapa, mas a tab em si não existe.
- `resources/js/Pages/ProjectDetails/Partials/ProcessTabs.vue` registra hoje só 6 tabs; será
  preciso adicionar a 7ª.

## Abstração proposta

### Frontend — schema declarativo + composable de visibilidade, não v-if em cascata

Evoluir o padrão de schema **de forma aditiva e retrocompatível**: `formSections.js` passa a
aceitar opcionalmente uma lista `fields` por seção (hoje só tem `{title, key}`). Quando uma seção
declara `fields`, `SectionForm.vue`/uma Tab nova renderiza os campos automaticamente via um
componente `DynamicField.vue`; quando não declara (como hoje em Budget/Payment/etc.), o
comportamento atual (slot manual) permanece — **nenhuma das 6 tabs existentes muda**.

Cada campo é um objeto plano num array (não uma árvore aninhada — isso resolve a convergência
sem duplicar campos). "DSL" aqui significa *Domain-Specific Language*: não é uma linguagem de
programação nova, é só um formato de dado pequeno e fechado — um objeto JSON — desenhado só para
expressar uma coisa ("quando este campo aparece"), em vez de escrever isso como código
(`if campo_x === 'sim' && campo_y === 'nao'`). Por ser dado, tanto o front (JS) quanto o back
(PHP) conseguem ler e interpretar o mesmo arquivo com uma função `evaluate()` própria de cada
linguagem, sem duplicar a regra em si.

`visibleWhen` é literalmente essa condição, escrita como dado. Pensa em cada campo como tendo um
"porteiro" que pergunta: *"os campos dos quais eu dependo já têm a resposta que me libera?"* Se
sim, o campo aparece; se a resposta de um campo do qual ele depende mudar e deixar de bater, o
campo some de novo (e seu valor é limpo, para não sobrar resposta de um caminho abandonado).

A implementação de referência (só frontend, sem persistência) está em
`resources/schemas/accountability-fields-demo.json`, com 13 campos cobrindo quatro seções da
cascata (`visit`, `analysis`, `authority`, `conclusion`). Trecho ilustrativo, mostrando o campo
[1] "Foi realizada visita presencial?" abrindo dois ramos (visita = Sim → Parecer Técnico da
Visita; visita = Não → Parecer Técnico + Análise) e o ponto de convergência em
`authority_submission_date`/`needs_financial_report` (campos [8]/[9] da issue), que na versão
completa do arquivo já é alimentado por **4 ramos diferentes** (`analysis_result = full`,
`visit_result = compliance_verified`, `analysis_forwarding = no_diligence_needed` ou
`= suggest_financial_report`):

```json
[
  {
    "key": "in_person_visit",
    "label": "Foi realizada visita presencial?",
    "section": "visit",
    "type": "radio",
    "options": [{ "label": "Sim", "value": "yes" }, { "label": "Não", "value": "no" }],
    "required": true
  },
  {
    "key": "visit_result",
    "label": "Qual o resultado da visita presencial?",
    "section": "visit",
    "type": "select",
    "options": [
      { "label": "Necessidade de apresentação de documentação complementar", "value": "needs_documentation" },
      { "label": "Cumprimento integral ou parcial verificado", "value": "compliance_verified" }
    ],
    "required": true,
    "visibleWhen": { "field": "in_person_visit", "equals": "yes" }
  },
  {
    "key": "analysis_result",
    "label": "Qual o resultado da Análise do Relatório de Execução do Objeto?",
    "section": "analysis",
    "type": "select",
    "options": [
      { "label": "Cumprimento Parcial do Objeto", "value": "partial" },
      { "label": "Cumprimento Integral do Objeto", "value": "full" }
    ],
    "required": true,
    "visibleWhen": { "any": [
      { "field": "in_person_visit", "equals": "no" },
      { "field": "visit_result", "equals": "needs_documentation" }
    ] }
  },
  {
    "key": "analysis_forwarding",
    "label": "Encaminhamento da Análise",
    "section": "analysis",
    "type": "select",
    "options": [
      { "label": "Necessita Solicitar Documentação", "value": "needs_documentation" },
      { "label": "É sugerido solicitar Relatório de Execução Financeira", "value": "suggest_financial_report" },
      { "label": "Não necessita de diligência", "value": "no_diligence_needed" }
    ],
    "required": true,
    "visibleWhen": { "field": "analysis_result", "equals": "partial" }
  },
  {
    "key": "authority_submission_date",
    "label": "Qual a data de envio para Autoridade Julgadora?",
    "section": "authority",
    "type": "date",
    "required": true,
    "visibleWhen": { "any": [
      { "field": "analysis_result", "equals": "full" },
      { "field": "visit_result", "equals": "compliance_verified" },
      { "field": "analysis_forwarding", "equals": "no_diligence_needed" },
      { "field": "analysis_forwarding", "equals": "suggest_financial_report" }
    ] }
  },
  {
    "key": "needs_financial_report",
    "label": "Necessita de Relatório Financeiro?",
    "section": "authority",
    "type": "radio",
    "options": [{ "label": "Sim", "value": "yes" }, { "label": "Não", "value": "no" }],
    "required": true,
    "visibleWhen": { "any": [
      { "field": "analysis_result", "equals": "full" },
      { "field": "visit_result", "equals": "compliance_verified" },
      { "field": "analysis_forwarding", "equals": "no_diligence_needed" },
      { "field": "analysis_forwarding", "equals": "suggest_financial_report" }
    ] }
  }
]
```

(Arquivo completo, com as seções `visit`/`analysis`/`authority`/`conclusion` e os campos de
Parecer Conclusivo/Sanção/Medidas Cabíveis, em `resources/schemas/accountability-fields-demo.json`.)

Repare que `authority_submission_date` e `needs_financial_report` (campos [8] e [9] da issue) têm
exatamente a mesma `visibleWhen` — eles são o ponto de convergência de **4 ramos diferentes**:
"sem visita presencial ou visita com pendência" seguido de cumprimento integral
(`analysis_result = full`), "visita presencial com cumprimento verificado"
(`visit_result = compliance_verified`), e os dois desfechos de "cumprimento parcial sem
necessidade de diligência" (`analysis_forwarding = no_diligence_needed` ou
`= suggest_financial_report`). É esse tipo de reaparecimento do mesmo campo em ramos diferentes
que motiva o campo ser uma entrada única num array plano (com uma condição `any`), em vez de
modelar isso como uma árvore onde o campo teria que ser duplicado em cada ramo.

DSL de visibilidade suportado: `{ field, equals }`, `{ field, in: [...] }`, `{ all: [...] }`,
`{ any: [...] }`, aninháveis — cobre 100% das regras da issue (são todas comparações de igualdade
combinadas por E/OU) sem precisar de um parser de expressões genérico ou dependência nova.

Um composable novo, **não específico desta etapa** — `Composables/useConditionalFields.js` —
recebe `fields` + os valores atuais do formulário (reativos) e devolve `visibleFields` e
`requiredFields` como `computed`. A reatividade do Vue resolve a cascata sozinha (campo B depende
de A que depende de "visível"; quando A muda, B reavalia). O composable também limpa (`watch`) o
valor de campos que saem de visibilidade, evitando "dado fantasma" de um ramo abandonado.

Os poucos campos que não são select/date/radio simples — modal do relatório do Mapa Cultural,
editor de texto do TADO, confirmação de arquivamento — usam `type: 'action'`/`'modal-trigger'` no
schema e continuam com slot nomeado por `key` na Tab, como hoje `SectionForm` expõe slot por
`section.key`. É a válvula de escape deliberada: abstrai o que é repetitivo, não força abstração
onde não compensa (3 casos únicos).

Prazos calculados (+120 dias, +15 dias) seguem o padrão já existente: `viewSections.js` +
`Composables/useDate.js` (`addDaysTo`, `daysBetween`), ficam do lado somente-leitura ("Dados
disponível para consulta"), fora do schema editável.

### Backend — mesmo grafo, uma única fonte de verdade

O grafo de condições existe nos dois lados: o frontend decide o que mostrar, o backend precisa
validar o que é obrigatório dado o que foi enviado. Manter duas representações (schema JS + regras
PHP) sincronizadas manualmente é justamente o risco que se quer evitar numa área de regra
instável. Gerar código de um lado para o outro traria uma etapa de build nova sem precedente no
projeto, desproporcional para uma única aba.

Solução recomendada: **um arquivo JSON único**, importado nativamente pelos dois lados (Vite
importa JSON direto; PHP faz `json_decode`), sem build step novo:

- `resources/schemas/accountability-fields.json` — a fonte única do grafo: `key`, `label`, `type`,
  `options`, `section`, `required`, `visibleWhen` (mesmo DSL acima). É o arquivo que muda quando a
  regra de negócio muda.
- `resources/js/Schemas/Accountability/fields.js` importa o JSON e só acrescenta metadado de
  apresentação (ícone, componente Vue quando `type` não mapeia 1:1).
- `app/Support/Accountability/FieldGraph.php` carrega o mesmo JSON e expõe `visibleFields(array
  $data)` / `requiredFields(array $data)`, com uma implementação PHP equivalente (~20 linhas) do
  `evaluate()` usado no JS.
- `AccountabilityStoreRequest`/`UpdateRequest` montam `rules()` dinamicamente a partir de
  `FieldGraph::requiredFields($this->all())`. Campos cuja `visibleWhen` avalia falso para os dados
  enviados são anulados antes de persistir (higiene contra valor de ramo abandonado).
- Tabela nova `accountabilities` (migration + `app/Models/Accountability.php`, `belongsTo(Project)`,
  `HasCreatedBy`, `Auditable`, `SoftDeletes`) com colunas fixas nullable — mesmo padrão de
  `budgets`/`payments`. **Atenção**: `app/Models/Monitoring.php` tem hoje `$fillable` com campos
  que não existem na migration real (`effective_date_of_the_instrument` etc.) — não repetir esse
  bug; `$fillable` deve espelhar exatamente as colunas.
- `tests/Unit/Support/AccountabilityFieldGraphTest.php`: um teste por regra de visibilidade da
  issue, com atenção especial aos ≥4 pontos de convergência — é o teste que protege contra
  regressão quando a regra mudar.

### Como o `FieldGraph.php` valida o `required` condicional

O backend não pode confiar que o front só mandou o que devia (alguém pode chamar a rota
diretamente, sem passar pela tela). Então o `FieldGraph` refaz, em PHP, o mesmo cálculo que o
`useConditionalFields.js` fez no browser: dado o payload que chegou, quais campos *deveriam*
estar visíveis — e, entre esses, quais são `required` no schema. Como os dois lados leem o
**mesmo** `resources/schemas/accountability-fields-demo.json`, a lógica de `evaluate()` é a mesma
DSL (`equals`/`in`/`all`/`any`) reescrita em PHP:

```php
final class FieldGraph
{
    private array $fields;

    public function __construct()
    {
        $this->fields = json_decode(
            file_get_contents(base_path('resources/schemas/accountability-fields-demo.json')),
            true
        );
    }

    private function evaluateCondition(?array $condition, array $data): bool
    {
        if ($condition === null) {
            return true; // sem visibleWhen = sempre visível (ex.: campo [1])
        }

        if (isset($condition['all'])) {
            return collect($condition['all'])->every(fn ($c) => $this->evaluateCondition($c, $data));
        }

        if (isset($condition['any'])) {
            return collect($condition['any'])->contains(fn ($c) => $this->evaluateCondition($c, $data));
        }

        $value = $data[$condition['field']] ?? null;

        if (array_key_exists('equals', $condition)) {
            return $value === $condition['equals'];
        }

        if (array_key_exists('in', $condition)) {
            return in_array($value, $condition['in'], true);
        }

        return true;
    }

    public function visibleFields(array $data): array
    {
        return array_filter($this->fields, fn ($f) => $this->evaluateCondition($f['visibleWhen'] ?? null, $data));
    }

    public function requiredFields(array $data): array
    {
        return array_filter($this->visibleFields($data), fn ($f) => $f['required'] ?? false);
    }
}
```

Passando por esse método um payload como o que a demo do frontend gera hoje —

```json
{
  "in_person_visit": "yes",
  "visit_technical_opinion_number": "234234",
  "visit_technical_opinion_date": "2020-02-01",
  "visit_result": "compliance_verified",
  "authority_submission_date": "2026-07-14",
  "needs_financial_report": "no",
  "conclusive_opinion_result": "approved"
}
```

— `visibleFields()` libera exatamente essas 7 chaves em cascata: `in_person_visit = yes` libera
os 3 campos de visita; `visit_result = compliance_verified` libera `authority_submission_date` +
`needs_financial_report` (via o `any` de convergência); `needs_financial_report = no` libera
`conclusive_opinion_result`. Todas têm `required: true` no schema, então `requiredFields()`
retorna essas mesmas 7 chaves — batendo exatamente com o que foi enviado.

A obrigatoriedade condicional entra no Form Request **sem** reescrever a árvore de decisão como
`required_if` encadeado (o que duplicaria a lógica em um segundo lugar): as regras são derivadas
dinamicamente do grafo, a cada request, em cima dos dados que acabaram de chegar.

```php
final class AccountabilityStoreRequest extends FormRequest
{
    public function rules(): array
    {
        $graph = app(FieldGraph::class);
        $requiredKeys = array_keys($graph->requiredFields($this->all()));

        return collect($graph->allFieldKeys())
            ->mapWithKeys(fn ($key) => [$key => in_array($key, $requiredKeys) ? ['required'] : ['nullable']])
            ->all();
    }
}
```

Se amanhã uma regra da issue mudar (ex.: o campo [9] passa a depender de mais uma condição),
edita-se só o JSON — nem o Form Request nem o schema JS precisam mudar.

Um cuidado que fica registrado para a implementação real: se o front, por algum motivo, enviar um
campo que o `FieldGraph` calcula como **não visível** para aquele conjunto de respostas (payload
manipulado, campo de um ramo abandonado, etc.), o controller deve anular esse valor antes de
persistir — evitando gravar resposta de um caminho que a regra de negócio diz que não deveria
existir para aquele conjunto de respostas. Isso pode virar um `FieldGraph::sanitize(array $data)`
que aplica `visibleFields()` e descarta o resto.

### Permissão

A issue pede grupo "fiscal + autoridade julgadora" para edição (visualização é geral), o que não
bate com o padrão `role + coord_role` das etapas atuais. Precisa confirmar com o time se esses
papéis já existem sob outro nome antes de criar cases novos em `app/Enums/Role.php` — não
constatado na exploração atual do código.

## Plano de ação (sequenciamento de entrega)

**Fase 1 — MVP (foco desta issue: estrutura + campos + condicionais)**
1. Migration `create_accountabilities_table.php` + `app/Models/Accountability.php`.
2. `resources/schemas/accountability-fields.json` com os 34 campos e suas `visibleWhen` (transcrever
   da issue, seção por seção).
3. `app/Support/Accountability/FieldGraph.php` + testes unitários (inclui os 4 pontos de
   convergência).
4. `app/Http/Requests/Accountability/{Store,Update}Request.php` usando `FieldGraph`.
5. `app/Http/Controllers/AccountabilityController.php` + rotas `projects.accountabilities.*`
   (espelhando `MonitoringController`).
6. Papéis/permissão: confirmar com o time, então `Role::accountabilityRoles()` +
   `usePermissions.canManageAccountability`.
7. Frontend: `Composables/useConditionalFields.js`, `Components/DynamicField.vue`,
   `resources/js/Schemas/Accountability/{fields,viewSections,index}.js`,
   `Pages/ProjectDetails/Partials/Tabs/AccountabilityTab.vue`, registrar em `ProcessTabs.vue`
   (7ª tab). Os 3 campos de modal complexo entram como placeholder (botão desabilitado) nesta fase.
8. `DiligenceChat` já suporta o stage `prestacao_de_contas` — só conectar na tab nova.

**Fase 2** — modal "Visualize o Relatório de Execução do Objeto" (REOC) importado do Mapa Cultural
com exportação em PDF. Reaproveitar o padrão já existente de `SyncMonitoringRegistrationJob` +
coluna `data_registration` (jsonb).

**Fase 3** — emissão do TADO: editor de texto + salvar versão + baixar PDF. Reaproveitar
`barryvdh/laravel-dompdf` (já é dependência) + `HasFiles`. Avaliar/instalar um editor rich-text
para o Vue (não há um hoje no `package.json`).

**Fase 4** — botão "Arquivar processo" com modal de confirmação e bloqueio de campos, reaproveitando
o padrão de `TramitButton`/ações de retorno já usado em Budget/Payment/Monitoring.

**Fora de escopo** (conforme a própria issue): aba da lista de projetos para Prestação de Contas;
seção de Medidas Cabíveis.

## Por que essa abstração e não outra

- **Não migrar as 6 tabs existentes** para o schema declarativo agora: elas não têm campos
  condicionais entre si, forçar a migração aumentaria o raio de risco da entrega sem ganho.
- **Sem motor de expressões genérico** (tipo json-logic): as regras da issue são só igualdade
  combinada por E/OU: um DSL de objeto pequeno resolve com menos código e menos dependência.
- **JSON compartilhado em vez de codegen**: dado que é a primeira vez que o projeto precisa de um
  grafo de condições nos dois lados, introduzir uma etapa de build só para isso é desproporcional;
  o JSON puro já elimina a duplicação sem essa infraestrutura.