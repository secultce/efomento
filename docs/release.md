# Versionamento e Release

Este documento descreve como o e-Fomento versiona releases automaticamente e como isso se conecta ao pipeline de build/deploy.

## Resumo

A partir da versão `1.0.0`, o projeto usa **versionamento semântico (semver)** — `MAJOR.MINOR.PATCH` — calculado e publicado automaticamente pelo [release-please](https://github.com/googleapis/release-please) via GitHub Actions. Não há mais necessidade de decidir/editar a versão manualmente em nenhum arquivo: ela é derivada do tipo de mudança que entra em `main`.

## Como funciona no dia a dia

- **PRs para `develop`**: fluxo normal, sem exigência de formato de título. Nada muda aqui.
- **PRs de `develop` para `main`** (ou hotfix direto para `main`): o **título do PR** precisa seguir [Conventional Commits](https://www.conventionalcommits.org/), por exemplo:
  - `feat: adicionar exportação de relatório de pagamentos` → gera bump **minor** (1.2.0 → 1.3.0)
  - `fix: corrigir cálculo de parcela no ciclo de monitoramento` → gera bump **patch** (1.2.0 → 1.2.1)
  - `feat!: remover endpoint legado de importação` (ou rodapé `BREAKING CHANGE:`) → gera bump **major** (1.2.0 → 2.0.0)
  - Outros prefixos aceitos sem impacto de versão: `chore:`, `docs:`, `style:`, `refactor:`, `perf:`, `test:`, `build:`, `ci:`, `revert:`
- Um workflow de CI (`pr-title-lint.yml`) valida esse título automaticamente e falha o check do PR se o formato estiver incorreto.
- O merge de PRs para `main` é feito por **squash-merge** — todos os commits da branch viram um único commit em `main`, com o título do PR como mensagem. É esse commit único que o release-please analisa.

### Por que não exigir esse padrão em todo commit?

Historicamente os commits do projeto são em português livre, sem convenção fixa. Em vez de forçar reescrita de hábito em cada commit local (o que gera fricção no dia a dia), a exigência foi concentrada só no título do PR que efetivamente vai para produção — é o único ponto que precisa ser "machine-readable" para automação funcionar.

## O que acontece automaticamente após o merge

1. Assim que um PR chega em `main`, o workflow `release-please.yml` roda e abre (ou atualiza) um **Release PR** — um PR criado pelo próprio bot, contendo:
   - `CHANGELOG.md` atualizado com as mudanças desde a última release, agrupadas por tipo (Features, Bug Fixes, etc.);
   - o arquivo `VERSION` (raiz do projeto) com o novo número calculado;
   - `package.json` com o campo `version` sincronizado.
2. Esse Release PR fica aberto e é **cumulativo** — se mais PRs forem mergeados em `main` antes dele ser aprovado, o release-please só atualiza o mesmo PR com as mudanças novas.
3. Quando alguém revisa e mergeia o Release PR, o release-please cria automaticamente:
   - a tag git `vX.Y.Z`;
   - uma GitHub Release com as notas de versão.
4. A partir daí, a aplicação já reflete a nova versão: o backend lê o arquivo `VERSION` (`config('app.version')`), expõe via Inertia (`appVersion` em `HandleInertiaRequests`), e o rodapé (`AppFooter.vue`) exibe "Versão: X.Y.Z" automaticamente — nenhuma edição manual necessária.

## Pipeline de imagem/deploy (DevOps)

**Importante para quem for configurar o pipeline de build de imagem/deploy**: o release-please e o build de imagem são **dois workflows separados e encadeados**, não a mesma coisa.

- O release-please só cuida de versão, changelog e tag em `main` — ele **não builda imagem nenhuma**.
- O pipeline de build/deploy deve disparar **somente no evento `release: types: [published]`** (ou `push: tags: 'v*'`), **nunca em todo push de `main`**. O push que apenas abre/atualiza o Release PR não representa uma versão oficial ainda — só o merge desse PR (que gera o evento de release) é o gatilho correto.
- A imagem deve ser buildada a partir do commit exato daquela tag. Como o arquivo `VERSION` já está atualizado nesse commit (o próprio release-please o modifica dentro do Release PR), **a imagem já nasce com a versão certa** sem nenhum passo extra de "injetar versão no build" — o backend simplesmente lê o arquivo em runtime, como sempre.
- Convenção sugerida: usar a própria tag git (`vX.Y.Z`) como tag da imagem no registry, evitando qualquer numeração paralela do lado do CD.

```
PR feat/fix → main (squash)
        │
        ▼
release-please.yml (push em main)
        │
        ▼
  Release PR (changelog + VERSION + package.json)
        │  (merge humano)
        ▼
  tag vX.Y.Z + GitHub Release publicada
        │
        ▼
 CD do DevOps (trigger: release published)
        │
        ▼
  build da imagem a partir da tag → deploy
```

## Referência rápida dos arquivos

| Arquivo | Função |
|---|---|
| `VERSION` | Fonte de verdade da versão atual (texto puro, ex. `1.0.0`). Lido por `config/app.php`. |
| `release-please-config.json` | Configuração do release-please: quais arquivos atualizar, formato do changelog. |
| `.release-please-manifest.json` | Estado atual da versão gerenciada pelo release-please (não editar manualmente). |
| `.github/workflows/release-please.yml` | Roda em push para `main`; abre/atualiza o Release PR e publica tag/release. |
| `.github/workflows/pr-title-lint.yml` | Valida título de Conventional Commits em PRs com destino `main`. |
| `CHANGELOG.md` | Gerado e mantido automaticamente pelo release-please. Não editar manualmente. |
