---
name: nova-tab
description: Cria uma nova Tab de etapa do projeto (ex. PaymentTab) com seus Schemas (viewSections/formSections) seguindo o padrão SplitScreenTab do efomento. Use quando o usuário pedir para "criar uma nova tab", "nova etapa do projeto", "adicionar aba de <etapa>", "criar schemas de seções" ou criar a tela de uma etapa do fluxo do Project.
---

# Nova Tab de etapa do projeto

Cria a interface completa de uma etapa do fluxo do `Project` (Tab + Schemas), no padrão das tabs existentes em `resources/js/Pages/ProjectDetails/Partials/Tabs/`.

## Passo 1 — Definir nomes e conferir o backend

Pergunte ao usuário (ou infira do pedido):

- **Nome da etapa em inglês, PascalCase** — ex. `Payment`, `Inspection`. Define os nomes de arquivos (`<Etapa>Tab.vue`, `Schemas/<Etapa>/`).
- **Nome da relação no model `Project`** — ex. `payment`. Define `props.project.<relacao>` e os nomes de rotas (`projects.<relacao>.store|update`).

Depois confira se o backend já existe:

- A relação está em `app/Models/Project.php`?
- As rotas `projects.<relacao>.*` estão em `routes/web.php`?
- O controller faz eager load da relação em `app/Http/Controllers/ProjectController.php` (método que renderiza ProjectDetails)?

**Se o backend não existir**: avise o usuário e ofereça criá-lo como etapa separada seguindo `checklist-backend.md` (neste mesmo diretório). NUNCA gere model/migration/rotas silenciosamente — espere a confirmação.

## Passo 2 — Ler as referências vivas (OBRIGATÓRIO antes de gerar qualquer código)

Estes arquivos do repo são a fonte de verdade do padrão. Leia todos:

| Arquivo | O que observar |
|---|---|
| `resources/js/Pages/ProjectDetails/Partials/Tabs/FormalizationTab.vue` | Tab completa: useForm, init no onMounted, validação, upload, submit, tramitação |
| `resources/js/Pages/ProjectDetails/Partials/Tabs/MonitoringTab.vue` | Tab que consome viewSections via SectionContent |
| `resources/js/Schemas/Monitoring/viewSections.js` | Estrutura de campos `{label, key|compute, format}` e uso do useDate |
| `resources/js/Schemas/Monitoring/formSections.js` e `index.js` | formSections `{title, key}` e barrel export |
| `resources/js/Components/SplitScreenTab.vue`, `SectionContent.vue`, `SectionForm.vue`, `SectionChips.vue` | Contrato de props e slots |

## Passo 3 — Criar os Schemas

Criar `resources/js/Schemas/<Etapa>/`:

1. **`viewSections.js`** — array de seções `{ title, fields: [...] }`. Cada field:
   - `{ label: 'Texto em português', key: 'relacao.campo' }` — acesso por dot notation
   - `{ label, compute: fn(project) }` — para valores calculados; use o `useDate`:
     - `getDate('relacao.campo')` — formata data
     - `addDaysTo('campo', dias)` — data + N dias (combine com `format: 'datetime'`)
     - `daysBetween('relacao.inicio', 'relacao.fim')` — dias entre duas datas
   - Atenção: a relação de formalização chama-se **`formalizations`** (plural) no JSON.
2. **`formSections.js`** — `export const formSections = [{ title: '...', key: 'slug-da-secao' }]`
3. **`index.js`** — barrel export: `export { viewSections } from './viewSections'; export { formSections } from './formSections';`

## Passo 4 — Criar a Tab

Criar `resources/js/Pages/ProjectDetails/Partials/Tabs/<Etapa>Tab.vue` seguindo o exemplar lido no Passo 2:

- Layout: `SplitScreenTab` — slot `#left-content` (visualização: `SectionChips` + `SectionContent` por seção) e `#right-content` (edição: `SectionChips` + `SectionForm` com slot por `section.key`).
- Form: `useForm` do Inertia com todos os campos `null`; inicializar no `onMounted` a partir de `props.project.<relacao> || {}`, usando `normalizeDate()` (do `useDate`) em todo campo de data.
- Submit: `form.post(...)` se não existe registro, `form.patch(...)` se existe — rotas `route('projects.<relacao>.store', { project: props.project.id })` / `route('projects.<relacao>.update', { project, <relacao>: id })`. Sempre com:
  - `preserveScroll: true`
  - `onSuccess: () => showSnackbar('<mensagem em português>', 'success')`
  - `onError: (errors) => showSnackbar(Object.values(errors).flat().join(', ') || '<fallback>', 'error')`
- Upload de arquivo (se a etapa tiver): `forceFormData: true`, validar tamanho máximo de 10MB com `form.setError`, limpar o campo e o input nativo no `onSuccess`.
- Permissões: usar `useAuth` (`hasRole`/`can`) em computeds para habilitar edição/tramitação.

## Passo 5 — Registrar a Tab

Adicionar a nova tab em `resources/js/Pages/ProjectDetails/Partials/ProcessTabs.vue`, seguindo exatamente o padrão das tabs já registradas lá (import + entrada na lista/template).

## Passo 6 — Checklist de conformidade (verifique item a item)

- [ ] `<script setup>` com ordem: imports → composables → `defineProps` → `defineEmits` → state (`ref`/`useForm`) → computed → lifecycle → methods
- [ ] `defineProps` com `type` e `default` (`default: () => ({})` ou `() => []` para Object/Array)
- [ ] Nomes de arquivos e componentes em **PascalCase**
- [ ] Código (variáveis, métodos, keys) em **inglês**; textos visíveis (labels, mensagens, títulos) em **português**
- [ ] Vuetify para widgets: `v-btn variant="outlined" color="primary"`, `v-select variant="outlined"`; tema primary `#008344`, secondary `#ffcc05`
- [ ] Tailwind para layout: `space-y-6`, `grid grid-cols-2 gap-4`, `flex items-center justify-between`
- [ ] **Nunca** `v-data-table` — para listas usar `ListDataTable` (`resources/js/Components/ListDataTable.vue`)
- [ ] Imports com alias `@/` (ex. `@/Components/...`, `@/Composables/...`, `@/Schemas/...`)
- [ ] Sem erro no Vite (verificar o container `efomento-vite` ou rodar build)
