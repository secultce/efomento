# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Idioma

O desenvolvedor se comunica em **português brasileiro**. Responda sempre em pt-BR.

## Visão Geral do Projeto

**e-Fomento** — sistema web de gerenciamento de processos de fomento cultural da Secretaria da Cultura do Ceará (SECULT-CE), construído em Laravel 12. Substitui a planilha compartilhada "Planilão" por um fluxo digital estruturado, seguro e auditável.

Gerencia o ciclo completo de projetos aprovados em editais culturais (ex: Ciclo Cearense Carnavalesco, PNAB), desde a abertura do processo até o pagamento final e monitoramento, envolvendo múltiplos setores internos (C. Finalística, ASJUR, CODIP, CEGEF).

## Stack Tecnológica (decisões já tomadas — não questionar)

- **Backend:** Laravel 12 (PHP 8.3+)
- **Banco de dados:** PostgreSQL (preferir JSONB para campos flexíveis)
- **Admin interno:** Filament PHP (acesso direto aos models, sem API)
- **Frontend usuário final:** Vue.js + Vuetify + Tailwind CSS (via API RESTful)
- **Autenticação:** Laravel Breeze
- **Testes:** PHPUnit 11 (SQLite in-memory)
- **Lint:** Laravel Pint

## Comandos de Desenvolvimento

Tudo roda via Docker. Nunca executar artisan/composer/npm diretamente no host.

```bash
# Subir/parar containers
docker compose up -d
docker compose down

# Executar comandos artisan/composer/npm
docker compose exec app php artisan <comando>
docker compose exec app composer <comando>
docker compose exec app npm <comando>

# Testes
docker compose exec app php artisan test
docker compose exec app php artisan test --filter=NomeDoTeste

# Lint (code style)
docker compose exec app ./vendor/bin/pint

# Migrations
docker compose exec app php artisan migrate

# Limpar caches (necessário após alterar config/rotas — entrypoint cacheia automaticamente)
docker compose exec app php artisan config:clear
docker compose exec app php artisan route:clear
docker compose exec app php artisan view:clear
```

### URLs locais
- **App:** http://localhost:8080 (Nginx → PHP-FPM)
- **Vite HMR:** http://localhost:5173 (container dedicado)
- **PostgreSQL:** localhost:5432

## Arquitetura Docker

```
docker compose up -d
├── efomento-postgres   (5432)  — PostgreSQL com health check
├── efomento-app        (9000)  — PHP-FPM + OPcache, entrypoint cacheia config/route/view
├── efomento-vite       (5173)  — Vite dev server com HMR
└── efomento-nginx      (8080)  — Proxy reverso
```

O `entrypoint.sh` executa `config:cache`, `route:cache` e `view:cache` ao iniciar. **Após alterar config, rotas ou views, limpe os caches** ou reinicie o container.

## Arquitetura de Aplicação

### Duas camadas de interface
- **Filament PHP** → admin interno (equipe técnica, gestores). Acessa models diretamente, sem API.
- **Vue.js + Vuetify** → interface do usuário final (agentes culturais, analistas). Consome API RESTful.

### Padrões de Projeto
- **MVC:** Resource Controllers + Eloquent Models + Blade (Filament) / Vue (frontend)
- **Service Layer:** lógica de negócio isolada em Services
- **Observers:** cálculos automáticos e side effects
- **Form Requests:** validação centralizada
- **API Resources:** transformação de dados para o frontend Vue

## Modelagem de Dados (Abordagem 01 — já validada)

Models dedicadas por etapa do fluxo, espelhando as abas da planilha original. Cada etapa tem **campos fixos tipados** para dados críticos e **campos JSONB** para sub-seções flexíveis. **Não usar padrão EAV.**

### Entidade raiz

**Project** — projeto cultural:
- `edital_id`, `agent_name`, `agent_cpf_cnpj`, `status` (enum global), `last_synced_at`
- NUPs: `nup_mae`, `nup_dotacao`, `nup_credor`
- Dados sincronizados via API do Mapa Cultural (somente leitura após importação)

### Models por etapa

| Model | Etapa | Setor responsável | Status |
|-------|-------|--------------------|--------|
| `ProjectOpening` | Abertura | C. Finalística | Campos mapeados |
| `ProjectFormalization` | Análise Jurídica | ASJUR | Aguardando UX |
| `ProjectParcel` | Orçamento/Parcela | CODIP | Aguardando UX |
| `ProjectPayment` | Pagamento | CEGEF | Aguardando UX |
| `ProjectMonitoring` | Monitoramento | — | Aguardando UX |

### ProjectOpening (referência de padrão para as demais)

**Campos tipados:** `numero_processo`, `data_abertura`, `status_agente_cultural`, `responsavel_abertura`, `valor_repasse` (decimal), `valor_repasse_extenso`, `banco`, `tipo_conta`, `agencia`, `conta`, `fiscal_nome`, `fiscal_cpf`, `fiscal_matricula`

**Campos JSONB:** `parcelas_adicionais` (array dinâmico), `certidao_eparcerias`

**Controle:** `status` (enum: pendente/em_andamento/concluido), `responsible_user_id`, `concluded_at`

Quando as telas de UX forem fornecidas para as demais models, seguir este mesmo padrão.

### ProjectStage (tabela de configuração, opcional)
- `slug`, `order`, `responsible_sector` — controle de fluxo e validação de avanço entre etapas

### Relacionamentos
- 1 Project → 1 ProjectOpening → 1 ProjectFormalization → 1 ProjectParcel → 1 ProjectPayment → 1 ProjectMonitoring
- 1 Project → N ProjectStages (configuração de fluxo)
- User → responsável por etapa via `responsible_user_id`

## Regras de Negócio Fundamentais

- **Acesso por etapa:** cada setor edita apenas sua etapa
- **Fluxo sequencial:** etapa só avança após aprovação da anterior
- **Soft delete obrigatório:** projetos nunca são deletados fisicamente — usar status `desclassificado`
- **Auditoria:** todo campo crítico alterado gera log (histórico de alterações)
- **Dados sensíveis:** CPF e dados bancários sempre em colunas tipadas próprias, nunca em campos genéricos
- **Resiliência:** sistema funciona mesmo se a API do Mapa Cultural estiver indisponível (dados locais sincronizados)
- **Sincronização Mapa Cultural:** importação inicial via API + refresh sob demanda

## Tom de Trabalho

Projeto de uso governamental com requisitos de transparência e auditoria. Priorizar sempre: **integridade dos dados, rastreabilidade de alterações, clareza do código** e aderência às decisões arquiteturais já tomadas. Quando houver dúvida sobre modelagem, **perguntar antes de implementar**.
