# CLAUDE.md

Este arquivo fornece orientações ao Claude Code (claude.ai/code) ao trabalhar neste repositório.

## Ambiente

Todos os comandos rodam dentro do Docker — nunca execute `php`, `artisan`, `composer` ou `npm` diretamente no host.

| Container              | Função                                        | Porta          |
|------------------------|-----------------------------------------------|----------------|
| `efomento-app`         | PHP-FPM (Laravel)                             | 9000 (interno) |
| `efomento-vite`        | Servidor Vite + HMR                           | 5173           |
| `efomento-nginx`       | Proxy reverso                                 | 8080           |
| `efomento-postgres`    | PostgreSQL 16                                 | 5433 (host)    |
| `efomento-queue`       | Worker de filas (high/medium/details/default) | —              |
| `efomento-queue-files` | Worker de filas (files)                       | —              |
| `efomento-scheduler`   | Laravel scheduler                             | —              |
| `efomento-greenmail`   | Servidor SMTP/IMAP local (e-mail de teste)    | —              |
| `efomento-roundcube`   | Webmail para visualizar e-mails de teste      | 8025           |

Acesso: http://localhost:8080

## Comandos comuns

```bash
# Subir todos os containers
docker compose up -d

# Migrations
docker compose exec app php artisan migrate

# Recriar banco com seeders
docker compose exec app php artisan migrate:fresh --seed

# Rodar todos os testes (SQLite in-memory)
docker compose exec app php artisan test

# Rodar teste específico por classe ou filtro
docker compose exec app php artisan test --filter=OpeningTest
docker compose exec app php artisan test tests/Feature/OpeningTest.php

# PHP lint/formatter (corrige automaticamente)
docker compose exec app ./vendor/bin/pint

# PHP lint somente verificação (modo CI)
docker compose exec app ./vendor/bin/pint --test

# JS/Vue lint (corrige automaticamente)
docker compose exec vite npx eslint resources/js --fix

# JS/Vue formatação
docker compose exec vite npx prettier --write resources/js

# Qualquer comando Artisan
docker compose exec app php artisan <comando>
```

Os hooks de pré-commit (Husky + lint-staged) executam Pint e ESLint/Prettier automaticamente sobre os arquivos staged a cada `git commit`. Se o Docker não estiver rodando, o Pint é ignorado e o CI do GitHub Actions fará a verificação.

## Arquitetura

### Duas camadas de interface

- **Filament PHP** → painel administrativo interno (`/admin`). Acessa os models Eloquent diretamente.
- **Vue 3 + Vuetify 3 + Tailwind** → interface do usuário final via Inertia.js (`@inertiajs/vue3`). Páginas ficam em `resources/js/Pages/`.

### Modelo de fluxo

A entidade central é `Project` (uma inscrição em um edital de fomento cultural). Cada projeto percorre etapas sequenciais:

```
Notice (edital)
└── Project (inscrição)
    ├── Opening        — Coord. Finalística  (dados do agente, documentos, dados bancários)
    ├── LegalAnalysis  — ASJUR               (certidões, impedimentos, pareceres)
    ├── Formalization  — —                   (número do contrato)
    ├── Budget         — CODIP               (parcelas, valores, cronograma)
    ├── Payment        — CEGEF               (comprovantes, liberações)
    └── Monitoring     — —                   (prestação de contas)
```

A progressão de etapas é controlada: cada etapa só é desbloqueada após aprovação da anterior. `ProjectStage` rastreia o status atual via enums `ProjectStageSlug` e `ProjectStageStatus`.

### Padrões adotados

- **Service Layer** — lógica de negócio fica em `app/Services/`. Controllers são finos e delegam para os serviços.
- **Observers** — `NoticeObserver` e `ProjectObserver` tratam side effects e cálculos automáticos.
- **Form Requests** — validação centralizada, não nos controllers.
- **API Resources** — dados Eloquent são transformados em `app/Http/Resources/` antes de chegar ao Vue.
- **Auditoria** — `OwenIt\Auditing` rastreia alterações em models críticos (implementam `Auditable`).
- **Morph map** — registrado no `AppServiceProvider`: `agent`, `notice`, `project`, `opening`.

### Integrações externas

- **API do Mapa Cultural do Ceará** (`MapasClient`) — importa editais e dados de agentes; usa cache com resiliência caso a API esteja indisponível.
- **Google Sheets** (`GoogleSheetsService`) — sincroniza dados da planilha para os models via comando Artisan ou endpoint HTTP. Mapeamento colunas → fields fica em `config/spreadsheet_mappings.php`.

### Estrutura de filas

Dois workers separados por responsabilidade:
- `high,medium,details,default` — jobs gerais (sincronização, notificações)
- `files` — jobs de download de arquivos (`DownloadMapasProjectFileJob`, `SyncProjectFilesJob`)

### Tabs de etapa e Schemas

As telas de cada etapa do Project são Tabs em `resources/js/Pages/ProjectDetails/Partials/Tabs/` (registradas em `ProcessTabs.vue`), construídas sobre `SplitScreenTab` + `SectionChips` + `SectionContent` (visualização) + `SectionForm` (edição). Os campos exibidos/editados são declarados em Schemas (`resources/js/Schemas/<Etapa>/`):

- `viewSections.js` — seções `{ title, fields }`; cada field usa `{ label, key }` (dot notation) ou `{ label, compute }` para valores calculados.
- `formSections.js` — `{ title, key }`; o `SectionForm` renderiza um slot por `section.key`.
- `index.js` — barrel export.

Para datas nos Schemas, usar o composable `useDate`: `getDate(key)` formata, `addDaysTo(key, dias)` soma dias, `daysBetween(startKey, endKey)` retorna o intervalo em dias.

**Atenção:** o relacionamento de formalização no `Project` chama-se `formalizations` (plural, mesmo sendo `HasOne`) — no frontend o acesso é `project.formalizations.<campo>`.

### Convenções de frontend

- Roteamento via **Ziggy** — o helper `route()` está disponível globalmente no Vue.
- Navegação com Inertia: usar `document.referrer` (não `history.state`) para detectar a página anterior.
- Seletores deep do Vuetify 3 que funcionam: `:deep(.v-data-table__th)` para cabeçalhos, `:deep(.v-data-table__tr:not(:last-child) td)` para linhas. O seletor `.v-data-table__thead th` **não existe** no Vuetify 3.
- Paginação customizada: não usar `v-model:search` na `v-data-table` — filtrar manualmente com uma computed property.
- TinyMCE é usado para texto rico; seus assets são copiados para `public/tinymce/` via `vite-plugin-static-copy`.

## Skills do Claude Code

Existem skills locais em `.claude/skills/` que codificam os padrões do frontend — **prefira invocá-las a criar esses artefatos manualmente**:

- `/nova-tab` — cria Tab de etapa + Schemas (viewSections/formSections) e registra no `ProcessTabs.vue`; se o backend da etapa não existir, segue o `checklist-backend.md` somente com confirmação do usuário.
- `/novo-componente` — cria componente reutilizável em `resources/js/Components/`, verificando antes se já existe algo similar.
- `/novo-composable` — cria composable em `resources/js/Composables/`, conferindo os existentes para não duplicar (funções de data vão no `useDate`, não em composable novo).

As skills mandam ler arquivos exemplares reais do repo antes de gerar código — não pule essa etapa. Documentação completa em `docs/skills/README.md`. O diretório `.claude/skills/` **não é versionado** (está no `.gitignore`); se uma skill gerar código fora do padrão, corrija o `SKILL.md` dela, não apenas o arquivo gerado.

## Convenções de nomenclatura

- Métodos PHP, colunas do banco, variáveis JS: **inglês**
- Textos visíveis ao usuário (labels, placeholders, títulos): **português**
- Enums: `SCREAMING_SNAKE_CASE` com valor backed string idêntico ao nome do case

## Testes

- PHPUnit 11, SQLite in-memory (`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:` em `phpunit.xml`)
- Testes de feature: `tests/Feature/`
- Testes unitários: `tests/Unit/`
- E2E: Cypress (roda no navegador do host contra `http://localhost:8080`). O binário do Cypress requer instalação separada: `npx cypress install` no host (não no Docker).