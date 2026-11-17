# Rollback da busca por agente

Execute na raiz do repositório:

```bash
git apply --check rollback/agent-search/rollback.patch
git apply rollback/agent-search/rollback.patch
```

O patch reverte apenas esta implementação e preserva as alterações que já existiam antes dela. Se a verificação falhar após novas edições, não force a aplicação: compare com os arquivos `.before`, que contêm o estado anterior dos dois arquivos.

Para testar pela interface:

```bash
npx cypress open --e2e
```

Selecione `cypress/e2e/efomento/projects/projects.cy.js` e o cenário `should search for a project by agent name`. A aplicação e os dados Cypress devem estar disponíveis no ambiente configurado.
