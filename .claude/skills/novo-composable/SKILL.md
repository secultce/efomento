---
name: novo-composable
description: Cria composable em resources/js/Composables/ no padrão export function useXxx() do efomento. Use quando o usuário pedir para "criar um composable", "extrair lógica reutilizável", criar um use<Algo> ou compartilhar lógica/estado entre componentes Vue.
---

# Novo composable

## Passo 1 — Verificar se já existe

Composables atuais em `resources/js/Composables/` — confira se o pedido não cabe em um deles (nesse caso, **adicione a função ao existente**, como foi feito com `daysBetween` no `useDate`):

| Composable | Responsabilidade |
|---|---|
| `useAuth` | usuário logado, `hasRole()`, `can()`, `canPerform()` |
| `useDate` | datas: `formatDate`, `normalizeDate`, `formatRelativeDate`, `addDaysTo`, `getDate`, `daysBetween` |
| `useSnackbar` | notificações: `showSnackbar(message, type, timeout)` |
| `useAlert` | diálogos de confirmação |
| `useFormHelper` | leitura de valores em forms/objetos |
| `useObjectPath` | resolução de paths `a.b.c[0].d` |
| `useEnums` | label de enums |
| `useMask` | máscaras de input |
| `useAuditFormatter` | formatação de logs de auditoria |
| `useDocumentImages` | imagens de documentos |
| `useExternalLink` | abertura de links externos |
| `useLegalAnalysis` | avaliação de documentos jurídicos (axios) |

## Passo 2 — Ler as referências vivas (OBRIGATÓRIO antes de gerar)

- `resources/js/Composables/useDate.js` — padrão base: `export function useXxx()` com funções puras e `return { ... }` explícito
- `resources/js/Composables/useSnackbar.js` — **somente se** precisar de estado compartilhado entre componentes (refs em escopo de módulo, fora da função)

## Passo 3 — Criar o composable

Criar `resources/js/Composables/use<Nome>.js`:

```js
export function use<Nome>() {
    const doSomething = (value) => {
        // ...
    };

    return {
        doSomething,
    };
}
```

- Nomes de funções e variáveis em **inglês**; mensagens voltadas ao usuário em **português**
- Estado compartilhado entre componentes → `ref` em escopo de módulo (padrão `useSnackbar`); estado por instância → `ref` dentro da função
- Funções que recebem `key` de objeto aninhado devem aceitar dot notation (reuse `useObjectPath` se precisar de algo além do simples `split('.').reduce(...)`)

## Passo 4 — Checklist de conformidade

- [ ] Arquivo `use<Nome>.js` em `resources/js/Composables/` e função `export function use<Nome>()`
- [ ] Nenhum efeito colateral no import do módulo (exceto refs de estado compartilhado, padrão `useSnackbar`)
- [ ] `return { ... }` explícito com tudo que é público
- [ ] Funções pequenas e puras quando possível; sem acesso direto a DOM salvo necessidade real
- [ ] Datas: não reimplementar — reusar/estender `useDate`
- [ ] Consumido nos componentes via `const { x } = use<Nome>()` com import `@/Composables/use<Nome>`
