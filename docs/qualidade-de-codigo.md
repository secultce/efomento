# Qualidade de Código

Este documento descreve as ferramentas e configurações de qualidade de código adotadas no projeto e-Fomento.

---

## Visão geral

O projeto usa duas camadas de verificação:

| Camada | Quando executa | Ferramentas |
|--------|---------------|-------------|
| **Pré-commit local** | Ao fazer `git commit` | Husky + lint-staged → Pint (PHP) + ESLint + Prettier (JS/Vue) |
| **CI no GitHub Actions** | Em Pull Requests para `main` ou `develop` | Pint (PHP) + ESLint (JS/Vue) |

---

## Pré-commit local

### `.husky/pre-commit`

Executado automaticamente pelo Git antes de cada commit. 
Chama o `lint-staged`, que analisa **apenas os arquivos staged** (não o projeto inteiro), 
tornando o processo rápido.

```
lint-staged
```

### `lint-staged` (`package.json`)

Define o que executar por tipo de arquivo:

```json
"lint-staged": {
  "*.php": "bash scripts/pint-lint-staged.sh",
  "*.{js,vue}": ["eslint --fix", "prettier --write"]
}
```

- **`*.php`** → executa o script `pint-lint-staged.sh` (ver abaixo)
- **`*.js` e `*.vue`** → ESLint corrige automaticamente, depois Prettier formata

### `scripts/pint-lint-staged.sh`

Script intermediário que roda o Pint **dentro do container Docker**, traduzindo os caminhos do host para o container.

Comportamento:
- Se o Docker **não estiver rodando**, exibe um aviso e deixa o commit passar — o CI do GitHub Actions fará a verificação.
- Se o Docker **estiver rodando**, executa `./vendor/bin/pint` no container `app` passando apenas os arquivos staged.

> Necessário porque o PHP roda exclusivamente no container — nunca no host.

---

## PHP — Laravel Pint

### `pint.json`

Configuração do [Laravel Pint](https://laravel.com/docs/pint), o linter/formatter de PHP do projeto.

```json
{
    "preset": "laravel",
    "rules": {
        "ordered_imports": { "sort_algorithm": "alpha" },
        "not_operator_with_successor_space": true
    }
}
```

| Opção | Descrição |
|-------|-----------|
| `preset: laravel` | Usa o conjunto de regras padrão do Laravel (baseado no PHP-CS-Fixer) |
| `ordered_imports` | Ordena imports em ordem alfabética |
| `not_operator_with_successor_space` | Exige espaço após o operador `!` (ex: `! $value`) |

**Comandos:**

```bash
# Formatar (corrige automaticamente)
docker compose exec app ./vendor/bin/pint

# Verificar sem corrigir (usado no CI)
docker compose exec app ./vendor/bin/pint --test
```

---

## JavaScript / Vue — ESLint + Prettier

### `eslint.config.js`

Configuração do ESLint usando o formato flat config (ESLint v9+).

**Plugins ativos:**
- `@eslint/js` — regras base do JavaScript
- `eslint-plugin-vue` — regras específicas para componentes Vue 3
- `eslint-config-prettier` — desativa regras do ESLint que conflitam com o Prettier

**Globals definidos:**

| Global | Motivo |
|--------|--------|
| `tinymce` | Editor de texto rico usado no projeto |
| `route` | Helper do Ziggy (rotas Laravel no frontend) |

**Regras customizadas:**

| Regra | Configuração | Motivo |
|-------|-------------|--------|
| `vue/multi-word-component-names` | `off` | Componentes como `Modal`, `Dashboard` são nomes de uma palavra |
| `vue/valid-v-slot` | `error` com `allowModifiers: true` | Vuetify usa modificadores em `v-slot` (ex: `#item.name`) |
| `no-console` | `warn` (exceto `console.error`) | Evita logs esquecidos em produção |
| `no-unused-vars` | `warn` (ignora vars com prefixo `_`) | Variáveis descartadas intencionalmente usam `_` como prefixo |

**Ignorados:** `public/`, `vendor/`, `node_modules/`, `storage/`, `bootstrap/ssr/`

### `.prettierrc`

Configuração do [Prettier](https://prettier.io/) para formatação automática de JS/Vue.

```json
{
    "semi": true,
    "singleQuote": true,
    "tabWidth": 4,
    "trailingComma": "es5",
    "printWidth": 120,
    "vueIndentScriptAndStyle": false
}
```

| Opção | Valor | Efeito |
|-------|-------|--------|
| `semi` | `true` | Ponto e vírgula obrigatório |
| `singleQuote` | `true` | Aspas simples em strings JS |
| `tabWidth` | `4` | Indentação com 4 espaços |
| `trailingComma` | `es5` | Vírgula após o último item em objetos e arrays multi-linha |
| `printWidth` | `120` | Quebra de linha a partir de 120 caracteres |
| `vueIndentScriptAndStyle` | `false` | `<script>` e `<style>` em SFCs Vue não recebem indentação extra |

---

## CI — GitHub Actions

### `.github/workflows/lint.yml`

Executado em Pull Requests direcionados às branches `main` ou `develop`.

**Job `lint-php`** (PHP 8.3):
1. Faz checkout do código
2. Instala dependências via `composer install`
3. Executa `./vendor/bin/pint --test` — falha se houver arquivos fora do padrão

**Job `lint-js`** (Node 20):
1. Faz checkout do código
2. Instala dependências via `npm ci`
3. Executa `npm run lint` — falha se houver erros de ESLint

Os dois jobs rodam em paralelo e são independentes.

> O CI não corrige automaticamente — apenas detecta problemas. A correção deve ser feita localmente antes de abrir o PR.
