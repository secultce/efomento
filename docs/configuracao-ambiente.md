# Configuração do Ambiente de Desenvolvimento

Guia para configurar sua máquina local após clonar o repositório do e-Fomento.

> Para entender as ferramentas de qualidade de código (ESLint, Pint, Prettier, Husky), leia [`qualidade-de-codigo.md`](./qualidade-de-codigo.md).

---

## Pré-requisitos

| Ferramenta | Versão mínima | Para quê |
|------------|--------------|----------|
| Docker + Docker Compose | v25+ | Toda a stack roda em containers |
| Git | qualquer | Controle de versão |
| Node.js (host) | v22+ | Apenas para rodar o Cypress localmente |
| npm (host) | v10+ | Instalar o binário do Cypress |

> PHP, Composer e Node **não** precisam estar instalados diretamente no host — eles rodam dentro do Docker.

---

## Setup inicial (após clonar)

Execute estes passos **uma única vez** ao clonar o repositório:

### 1. Copiar o `.env`

```bash
cp .env.example .env
```

### 2. Ajustar UID/GID no `.env`

O Docker usa seu usuário do host para evitar problemas de permissão nos volumes montados.
Descubra seu UID e GID:

```bash
id -u   # ex: 1001
id -g   # ex: 1001
```

Abra o `.env` e atualize:

```dotenv
UID=1001
GID=1001
```

> Valores incorretos causam erro `EACCES: permission denied` no container do Vite ao tentar escrever em `node_modules/`.

### 3. Subir os containers

```bash
docker compose up -d --build
```

Aguarde todos os serviços ficarem `Up` (o postgres tem healthcheck — os demais esperam por ele):

```bash
docker compose ps
```

### 4. Instalar o binário do Cypress (host)

O Cypress é instalado como devDependency, mas o **binário** não vem junto com o `npm install` do container. Rode no host:

```bash
npx cypress install
```

---

## Varredura inicial do projeto

Após o setup, rode uma varredura completa para garantir que o código está em conformidade antes de começar a trabalhar.

### PHP (Laravel Pint)

```bash
# Corrige automaticamente todos os arquivos PHP
docker compose exec app ./vendor/bin/pint

# Apenas verifica sem corrigir (mesmo comportamento do CI)
docker compose exec app ./vendor/bin/pint --test
```

### JavaScript / Vue (ESLint + Prettier)

```bash
# Corrige todos os arquivos JS e Vue com ESLint
docker compose exec vite npx eslint resources/js --fix

# Formata todos os arquivos JS e Vue com Prettier
docker compose exec vite npx prettier --write resources/js
```

---

## VSCode

### Extensões obrigatórias

Instale via `Ctrl+Shift+X` ou pela linha de comando:

```bash
code --install-extension Vue.volar
code --install-extension dbaeumer.vscode-eslint
code --install-extension esbenp.prettier-vscode
code --install-extension EditorConfig.EditorConfig
code --install-extension ms-azuretools.vscode-docker
```

| Extensão | ID | Para quê |
|----------|-----|----------|
| Vue - Official (Volar) | `Vue.volar` | Syntax highlighting, IntelliSense e type-check em `.vue` |
| ESLint | `dbaeumer.vscode-eslint` | Sublinha erros ESLint em tempo real no editor |
| Prettier | `esbenp.prettier-vscode` | Formata ao salvar |
| EditorConfig | `EditorConfig.EditorConfig` | Aplica as regras do `.editorconfig` (indentação, LF, etc.) |
| Docker | `ms-azuretools.vscode-docker` | Gerenciar containers sem sair do editor |

### Configurações do workspace

O arquivo `.vscode/settings.json` já está versionado no repositório com as configurações necessárias:

```json
{
    "editor.formatOnSave": true,
    "editor.defaultFormatter": "esbenp.prettier-vscode",
    "[vue]": { "editor.defaultFormatter": "esbenp.prettier-vscode" },
    "[javascript]": { "editor.defaultFormatter": "esbenp.prettier-vscode" }
}
```

Nenhuma ação adicional necessária — o VSCode aplica automaticamente ao abrir o projeto.

---

## PHPStorm / IntelliJ

### 1. Interpretador PHP via Docker

`Settings > PHP > CLI Interpreter > + > From Docker, Vagrant, VM...`

- **Server:** Docker Compose
- **Configuration file:** `./docker-compose.yml`
- **Service:** `app`
- Confirme que o caminho do PHP no container é `/usr/local/bin/php`

### 2. ESLint

`Settings > Languages & Frameworks > JavaScript > Code Quality Tools > ESLint`

- Marque **Automatic ESLint configuration**
- O PHPStorm detecta o `eslint.config.js` e o `node_modules/.bin/eslint` do projeto automaticamente
- Marque **Run eslint --fix on save**

### 3. Prettier

`Settings > Languages & Frameworks > JavaScript > Prettier`

- **Prettier package:** `<caminho-do-projeto>/node_modules/prettier`
- Marque **On save**
- **Run for files:** `{**/{*.js,*.vue,*.ts,*.json,*.css,*.scss},*.js,*.vue}`

### 4. EditorConfig

`Settings > Editor > Code Style`

- Marque **Enable EditorConfig support**

O PHPStorm aplica automaticamente as regras do `.editorconfig` (indentação 4 espaços, LF, charset UTF-8).

### 5. Laravel Pint (PHP CS Fixer)

`Settings > PHP > Quality Tools > PHP CS Fixer`

- **PHP CS Fixer path:** deixe em branco (usaremos via terminal, não via watcher)
- Para formatar PHP manualmente: abra o terminal integrado e rode:

```bash
docker compose exec app ./vendor/bin/pint
```

> O Pint roda obrigatoriamente dentro do container. Não configure file watchers apontando para um binário do host.

---

## Fluxo de trabalho diário

```bash
# Subir o ambiente
docker compose up -d

# Ver logs em tempo real (ex: container app)
docker compose logs -f app

# Parar tudo
docker compose down
```

O hook de pré-commit (`husky`) roda **automaticamente** ao fazer `git commit`, então não é necessário rodar ESLint/Pint manualmente antes de commitar — eles já serão executados nos arquivos staged.

Se o Docker **não estiver rodando** no momento do commit, o Pint é ignorado e o CI do GitHub Actions fará a verificação no PR.

---

## Portas expostas

| Serviço | Porta local | Acesso |
|---------|-------------|--------|
| Nginx (app) | `8080` | `http://localhost:8080` |
| Vite HMR | `5173` | Automático pelo Laravel |
| PostgreSQL | `5433` | `localhost:5433` (usuário/senha: `efomento`) |