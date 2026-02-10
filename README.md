# e-Fomento

Sistema web de gerenciamento de processos de fomento cultural da **Secretaria da Cultura do Ceará (SECULT-CE)**, construído em Laravel 12.

Substitui a planilha compartilhada "Planilão" por um fluxo digital estruturado, seguro e auditável para acompanhar editais culturais pós-seleção no Mapa Cultural do Ceará. Gerencia o ciclo completo de projetos aprovados em editais (ex: Ciclo Cearense Carnavalesco, PNAB), desde a abertura do processo até o pagamento final e monitoramento.

## Stack Tecnológica

| Camada                 | Tecnologia                                      |
|------------------------|--------------------------------------------------|
| Backend                | Laravel 12 (PHP 8.3+)                           |
| Banco de dados         | PostgreSQL (JSONB para campos flexíveis)         |
| Admin interno          | Filament PHP (acesso direto aos models)          |
| Frontend usuário final | Vue.js + Vuetify + Tailwind CSS (via API RESTful)|
| Autenticação           | Laravel Breeze                                   |
| Build                  | Vite 7, Node.js                                  |
| Testes                 | PHPUnit 11 (SQLite in-memory)                    |
| Lint                   | Laravel Pint                                     |
| Infra                  | Docker (PHP-FPM, Nginx, PostgreSQL, Vite)        |

## Funcionalidades

### Importação de Dados
- Importação inicial de projetos/agentes via API do Mapa Cultural do Ceará
- Sincronização sob demanda (refresh) com resiliência — sistema funciona offline se a API estiver indisponível

### Workflow Multi-Etapas
Fluxo sequencial com etapas dedicadas por setor:

| Etapa                  | Setor Responsável | Escopo                                              |
|------------------------|-------------------|------------------------------------------------------|
| **Abertura**           | C. Finalística    | Dados do agente, endereço, documentos, dados bancários, parcelas |
| **Análise Jurídica**   | ASJUR             | Regularidade, certidões, impedimentos                |
| **Orçamento / Parcela**| CODIP             | Definição de parcelas, valores, cronograma           |
| **Pagamento**          | CEGEF             | Comprovantes, liberações                             |
| **Monitoramento**      | —                 | Prestação de contas                                  |

- Cada setor edita apenas sua etapa
- Etapa só avança após aprovação da anterior
- Histórico de alterações e auditoria em todos os campos críticos

### Administração (Filament)
- Cadastro de editais, usuários, permissões e configurações
- Painel de gestão e suporte operacional para equipe técnica

### Interface do Usuário Final (Vue.js)
- Telas de acompanhamento e preenchimento de etapas
- Layout definido pelo time de UX/UI
- Comunicação exclusiva via API RESTful

## Modelo de Dados

```
Project (entidade raiz — projeto cultural)
├── ProjectOpening        (Abertura — C. Finalística)
├── ProjectFormalization   (Análise Jurídica — ASJUR)
├── ProjectParcel          (Orçamento/Parcela — CODIP)
├── ProjectPayment         (Pagamento — CEGEF)
└── ProjectMonitoring      (Monitoramento/Prestação de Contas)
```

### Princípios de modelagem
- **Models dedicadas por etapa** — espelham as abas da planilha original
- **Campos fixos tipados** para dados críticos (CPF, valores, dados bancários)
- **Campos JSONB** para sub-seções flexíveis (parcelas dinâmicas, certidões)
- **Sem padrão EAV** (Entity-Attribute-Value)
- **Soft delete obrigatório** — projetos nunca são deletados, usam status `desclassificado`

### Project (entidade raiz)

| Campo          | Tipo     | Descrição                               |
|----------------|----------|-----------------------------------------|
| edital_id      | FK       | Edital vinculado                        |
| agent_name     | string   | Nome do agente cultural                 |
| agent_cpf_cnpj | string   | CPF ou CNPJ do agente                   |
| nup_mae        | string   | NUP do processo mãe                     |
| nup_dotacao    | string   | NUP de dotação                          |
| nup_credor     | string   | NUP do credor                           |
| status         | enum     | Status global do projeto                |
| last_synced_at | datetime | Última sincronização com Mapa Cultural  |

### ProjectOpening (referência de padrão para as demais)

| Campo                   | Tipo    | Descrição                    |
|-------------------------|---------|------------------------------|
| numero_processo         | string  | Número do processo           |
| data_abertura           | date    | Data de abertura             |
| valor_repasse           | decimal | Valor do repasse             |
| valor_repasse_extenso   | string  | Valor por extenso            |
| banco, tipo_conta       | string  | Dados bancários              |
| agencia, conta          | string  | Dados bancários              |
| fiscal_nome             | string  | Nome do fiscal               |
| fiscal_cpf              | string  | CPF do fiscal                |
| fiscal_matricula        | string  | Matrícula do fiscal          |
| status                  | enum    | pendente/em_andamento/concluido |
| responsible_user_id     | FK      | Responsável pela etapa       |
| parcelas_adicionais     | JSONB   | Array dinâmico de parcelas   |
| certidao_eparcerias     | JSONB   | Dados de certidão            |

> As demais models (Formalization, Parcel, Payment, Monitoring) seguirão o mesmo padrão quando as telas de UX forem definidas.

## Regras de Negócio

- **Acesso por etapa** — cada setor edita apenas sua model/etapa
- **Fluxo sequencial** — avanço condicionado à aprovação da etapa anterior
- **Auditoria obrigatória** — todo campo crítico alterado gera log de histórico
- **Dados sensíveis protegidos** — CPF e dados bancários sempre em colunas tipadas, nunca em JSONB genérico
- **Resiliência** — dados do Mapa Cultural são sincronizados localmente; sistema opera independente da API externa
- **Soft delete** — projetos usam status `desclassificado`, nunca exclusão física

## Como Rodar

### Pré-requisitos
- Docker e Docker Compose

### Subir o projeto

```bash
docker compose up -d
```

Isso inicia automaticamente:

| Container        | Descrição                              | Porta          |
|------------------|----------------------------------------|----------------|
| efomento-postgres| PostgreSQL com health check            | 5432           |
| efomento-app     | PHP-FPM com OPcache + caches Laravel   | 9000 (interno) |
| efomento-vite    | Vite dev server com HMR                | 5173           |
| efomento-nginx   | Proxy reverso                          | 8080           |

Acesse: **http://localhost:8080**

### Comandos úteis

```bash
# Migrations
docker compose exec app php artisan migrate

# Testes
docker compose exec app php artisan test

# Teste específico
docker compose exec app php artisan test --filter=NomeDoTeste

# Lint (code style)
docker compose exec app ./vendor/bin/pint

# Artisan / Composer / npm
docker compose exec app php artisan <comando>
docker compose exec app composer <comando>
docker compose exec app npm <comando>
```

### Acessos locais

| Serviço    | URL / Host             |
|------------|------------------------|
| App        | http://localhost:8080   |
| Vite HMR   | http://localhost:5173   |
| PostgreSQL | localhost:5432          |

## Arquitetura

### Duas camadas de interface
- **Filament PHP** → admin interno (equipe técnica, gestores). Acessa models diretamente.
- **Vue.js + Vuetify + Tailwind** → interface do usuário final. Consome API RESTful.

### Padrões de Projeto
- **MVC** — Resource Controllers + Eloquent Models + Blade (Filament) / Vue (frontend)
- **Service Layer** — lógica de negócio isolada em Services
- **Observers** — cálculos automáticos e side effects
- **Form Requests** — validação centralizada
- **API Resources** — transformação de dados para o frontend Vue

## Licença

Projeto interno da SECULT-CE. Uso restrito.
