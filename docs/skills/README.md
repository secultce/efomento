# Skills do Claude Code — padronização do frontend

Este documento explica o que são as skills locais do projeto, como usá-las e por que elas existem.

## Por que essas skills existem

O frontend do e-Fomento tem padrões bem definidos — `<script setup>` com ordem fixa de seções, Tabs construídas sobre `SplitScreenTab` + Schemas (`viewSections`/`formSections`), composables no padrão `useXxx()`, forms do Inertia com snackbar, Vuetify para widgets e Tailwind para layout. O problema é que esses padrões vivem apenas nos arquivos existentes: cada código novo depende de o autor (humano ou IA) conhecer os exemplares certos e copiá-los na íntegra.

As skills resolvem isso codificando o passo a passo de cada tipo de artefato. Quando uma skill é invocada, o Claude Code é **obrigado a ler os arquivos exemplares reais do repositório antes de gerar qualquer código** e a validar o resultado contra um checklist de conformidade. Os benefícios práticos:

- **Consistência** — todo Tab, componente ou composable novo nasce no padrão da casa, sem depender de revisão para apontar desvios básicos.
- **Sem documentação desatualizada** — as skills não carregam templates copiados; elas apontam para arquivos reais (ex.: `FormalizationTab.vue`, `useDate.js`). Quando o padrão evolui, a referência evolui junto.
- **Menos retrabalho** — evita duplicação: as skills mandam verificar componentes e composables existentes antes de criar algo novo.
- **Segurança no fluxo** — a skill de nova tab nunca gera backend (migration, model, rotas) silenciosamente; ela detecta o que falta e pede confirmação.

## Skills disponíveis

As skills ficam em `.claude/skills/` na raiz do projeto:

| Skill | Quando usar | O que faz |
|---|---|---|
| `/nova-tab` | Criar a tela de uma nova etapa do fluxo do Project (ex.: PaymentTab) | Cria `Schemas/<Etapa>/` (viewSections, formSections, index), a `<Etapa>Tab.vue` no padrão SplitScreenTab e registra no `ProcessTabs.vue`. Se o backend da etapa não existir, oferece o fluxo guiado do `checklist-backend.md` |
| `/novo-componente` | Criar componente Vue reutilizável em `resources/js/Components/` | Verifica se já existe componente similar (prefere estender/compor), usa `TextField.vue`/`FormField.vue` como referência e aplica o checklist (props tipadas, v-model, Vuetify outlined + Tailwind) |
| `/novo-composable` | Extrair lógica reutilizável para `resources/js/Composables/` | Confere os 12 composables existentes para não duplicar (ex.: nova função de data vai no `useDate`, não num composable novo), segue o padrão `export function useXxx()` |
| `/tratamento-erros` | Lançar/capturar erros, criar Service/Controller novo, tratar erro de API no Vue | Aplica a hierarquia `AppException` (backend) e o padrão `useErrorHandler` (frontend) descritos em `docs/error_handling.md` |

## Como usar

### Invocação explícita

Numa sessão do Claude Code, digite o comando:

```
/nova-tab
/novo-componente
/novo-composable
/tratamento-erros
```

A skill vai perguntar o que precisa (nome da etapa, da relação no `Project` etc.) e seguir os passos numerados do seu `SKILL.md`.

### Invocação automática

Não é preciso decorar os comandos. Pedidos em linguagem natural que casem com a descrição da skill a disparam sozinha:

> "crie a aba de pagamento do projeto" → `/nova-tab`
> "preciso de um campo de CNPJ reutilizável" → `/novo-componente`
> "crie um composable para formatar moeda" → `/novo-composable`
> "lance uma exception para essa regra de negócio" → `/tratamento-erros`

### O que esperar durante a execução

1. A skill **lê os arquivos de referência** do repositório (não pule esta etapa nem peça para pular — é ela que garante o padrão atual).
2. Gera os arquivos nos diretórios corretos.
3. Percorre o **checklist de conformidade** item a item.
4. No caso da `/nova-tab`, se o backend não existir, ela **para e pergunta** antes de criar model/migration/rotas.

## A importância de seguir a documentação das skills

- **Não edite o código gerado para "fugir" do padrão** sem antes discutir a mudança. Se o padrão precisa evoluir, atualize primeiro os arquivos exemplares (eles são a fonte de verdade) e, se necessário, o texto da skill.
- **Se a skill gerar algo fora do padrão**, o problema é da skill: corrija o `SKILL.md` correspondente em vez de corrigir só o arquivo gerado — senão o erro se repete na próxima invocação.
- **Antes de criar manualmente** um Tab, componente ou composable, prefira invocar a skill. Ela existe justamente para que ninguém precise lembrar de todos os detalhes (ordem do script setup, `normalizeDate` no `onMounted`, `preserveScroll`, limite de 10MB no upload...).

## Observações de manutenção

- As skills **são versionadas** — `.claude/skills/` é commitado no repo, então todo desenvolvedor que clonar o projeto já tem as skills disponíveis, sem setup extra.
- Skills são carregadas **no início da sessão** do Claude Code. Depois de criar ou editar uma skill, abra uma sessão nova para que a mudança valha.
- Estrutura de uma skill: diretório `.claude/skills/<nome>/` com um `SKILL.md` (frontmatter `name` + `description` com os gatilhos de invocação, seguido das instruções) e arquivos auxiliares opcionais (ex.: `nova-tab/checklist-backend.md`).
