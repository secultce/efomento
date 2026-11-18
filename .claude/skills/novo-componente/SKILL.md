---
name: novo-componente
description: Cria componente Vue 3 reutilizável em resources/js/Components/ seguindo os padrões do efomento (script setup, Vuetify outlined, Tailwind para layout). Use quando o usuário pedir para "criar um componente", "novo campo de formulário", "componente reutilizável" ou extrair UI repetida de páginas/tabs.
---

# Novo componente reutilizável

## Passo 1 — Verificar se já existe algo parecido

Liste `resources/js/Components/` e confira se o que foi pedido não é um destes (prefira **estender ou compor** um existente a criar do zero):

- **Campos de formulário**: `FormField` (wrapper label + erro + required), `TextField` (input com mask/money/capitalize), `SelectField`, `UserAutocompleteField`, `EditableField`
- **Botões**: `PrimaryButton` (amarelo `#ffcc05`), `SecondaryButton` (outlined), `DangerButton`
- **Modais/avisos**: `Modal` (dialog HTML nativo com transitions), `AppAlert` (v-dialog), `AppSnackbar`, `ReturnProcessModal`
- **Seções/layout**: `SplitScreenTab` (2 colunas), `SectionChips`, `SectionContent`, `SectionForm`, `AppContainer`
- **Listas**: `ListDataTable` (lista customizada — o projeto **não usa** `v-data-table`), `DocumentEvaluationList`
- **Outros**: `AppTextEditor` (TinyMCE), `DocumentImageUpload`, `AuxLinks`

Se já existir, pare e proponha reutilizar/estender.

## Passo 2 — Ler as referências vivas (OBRIGATÓRIO antes de gerar)

- `resources/js/Components/TextField.vue` — componente completo: props tipadas, `v-bind="$attrs"`, computed para formatação, emits de `update:modelValue`
- `resources/js/Components/FormField.vue` — wrapper simples com slot

## Passo 3 — Criar o componente

Criar `resources/js/Components/<Nome>.vue` (PascalCase) com `<script setup>` na ordem: imports → composables → `defineProps` → `defineEmits` → state → computed → methods.

- `defineProps` sempre com `type` e `default` (`default: () => ({})` / `() => []` para Object/Array); `required: true` só para dados críticos
- `defineEmits` explícito; para v-model, emitir `update:modelValue`
- Repassar atributos extras com `v-bind="$attrs"` quando o componente envolve um widget Vuetify
- Vuetify para o widget (`variant="outlined"`, cores do tema: primary `#008344`, secondary `#ffcc05`); Tailwind para layout/espacejamento
- Textos visíveis (labels, placeholders) em português; props, variáveis e eventos em inglês

## Passo 4 — Checklist de conformidade

- [ ] Nome do arquivo em PascalCase e nome descritivo do papel (não da página que o usa)
- [ ] Sem lógica de negócio dentro do componente — lógica vai para composable ou para a página
- [ ] Sem chamadas a backend dentro do componente reutilizável (receber dados via props, emitir eventos)
- [ ] Funciona com `v-model` se for campo de entrada
- [ ] Usado com import via alias `@/Components/<Nome>.vue`
- [ ] Sem erro no Vite após o uso
