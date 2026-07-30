# Critérios de Code Review — e-Fomento

Este arquivo é a referência de critérios para revisão de código neste projeto (Clean Code, SOLID, arquitetura, boas práticas de Laravel e de JavaScript/Vue 3). Antes de revisar qualquer diff — próprio ou de PR — aplique os itens abaixo além do que for específico da mudança em questão.

## Como aplicar

- Revisar apenas o que mudou no diff, não o arquivo inteiro (a menos que a mudança tenha efeito colateral em código não tocado).
- Priorizar achados por impacto real: duplicação que pode divergir silenciosamente > duplicação cosmética > nomenclatura.
- Toda sugestão precisa apontar arquivo + linha + um cenário concreto de como o problema se manifesta (não bastam princípios abstratos soltos, tipo "viola SRP" sem explicar o efeito prático).
- Não sugerir abstrações novas (Policies, Repositories, Strategies) sem uma necessidade concreta já existente no código — este projeto evita over-engineering deliberadamente (ver `Fora de Escopo` das issues como referência de critério).

## Clean Code

- **Sem duplicação de expressões idênticas entre irmãos** — dois métodos/props/computeds calculando exatamente a mesma coisa lado a lado é sinal de que deveriam vir de uma única fonte, mesmo que hoje "seja só coincidência".
- **Sem strings mágicas repetidas dentro do mesmo arquivo/classe** — se um valor (slug, chave, status) aparece mais de uma vez no mesmo escopo, extrair para uma constante local. Sem checagem de tipo, um typo numa das ocorrências quebra silenciosamente.
- **Nomes consistentes para o mesmo conceito entre arquivos irmãos** — ex.: as 6 Tabs de etapa devem nomear a mesma coisa da mesma forma; um outlier aumenta o custo cognitivo de quem lê o conjunto.
- **Funções pequenas e com um nível de abstração por vez** — não misturar orquestração de alto nível com detalhe de implementação no mesmo método.
- Comentários só quando o "porquê" não é óbvio pelo código (constraint escondida, workaround, invariante) — nunca para descrever o "o quê".

## SOLID / Arquitetura

- **SRP**: métodos de serviço devem conter só a lógica de transição/domínio; checagens de autorização repetidas em mais de um método da mesma classe devem virar um método privado (padrão já usado em `ProjectStageService::ensureIsPrincipalSupervisor`).
- **OCP**: preferir que uma regra de negócio exista em um único lugar, de forma que estendê-la não exija editar N pontos de código que hoje "por acaso" fazem a mesma coisa.
- **DIP**: Controllers dependem de Services via injeção de construtor (já é o padrão do projeto) — não instanciar Services diretamente dentro de métodos.
- Não criar Policies dedicadas, Repository pattern ou camadas novas sem necessidade concreta já demonstrada no código atual — Eloquent + Service Layer já é suficiente aqui (ver `CLAUDE.md`).

## Arquitetura do projeto (e-Fomento específico)

- **Controllers finos**: lógica de negócio em `app/Services/`, não no controller.
- **Form Requests** para validação centralizada, não validação inline no controller.
- **API Resources** (`app/Http/Resources/`) para serializar Eloquent antes de mandar para o Vue — não retornar Model bruto em prop Inertia quando já existe um Resource para aquele model.
- **Observers** (`NoticeObserver`, `ProjectObserver`) para side effects automáticos, não lógica de side effect espalhada em controllers/services.
- **Auditoria**: models críticos devem implementar `Auditable` quando alterações precisam ser rastreadas.
- Enums `SCREAMING_SNAKE_CASE` com valor backed idêntico ao nome do case.

## Boas práticas Laravel

- Exceptions de domínio (`AuthorizationException`, `InvalidArgumentException`) devem ser tratadas de forma distinta no controller quando a mensagem para o usuário precisa ser diferente — não colapsar tudo em um `catch (\Throwable $e)` genérico se já existem tipos de exceção mais específicos sendo lançados.
- `report()`/Sentry só para erros inesperados ou que precisam de rastreamento — não logar ruidosamente exceções de domínio esperadas (ex.: usuário sem permissão).
- Migrations: projeto ainda não está em produção — editar a migration original em vez de criar uma nova (ver memória do projeto).
- Testes de feature (`tests/Feature/`) devem cobrir o comportamento observável (resposta HTTP, prop Inertia, mensagem de erro), não detalhes internos de implementação.

## Boas práticas JavaScript / Vue 3

- Lógica repetida em 3+ componentes (mesmo que pequena, tipo 2 linhas) é candidata a composable — ver padrão `useDate`, `useStageAdvance`. Antes de criar um composable novo, checar se já existe um que cobre o caso (evitar duplicar `useDate`, `useAuth` etc.).
- `computed()` só quando o valor precisa ser reativo a mudanças de props/estado; se é derivado uma vez e não muda, usar `const` simples.
- Props que representam a mesma entidade (ex.: slug de etapa) devem ser declaradas uma vez por arquivo, não repetidas como string literal em múltiplos pontos do mesmo componente.
- Seletores Vuetify 3: usar `:deep(.v-data-table__th)`/`:deep(thead tr th)` para cabeçalho, `:deep(.v-data-table__tr:not(:last-child) td)` para linhas — `.v-data-table__thead th` não existe no Vuetify 3.
- Navegação Inertia: usar `document.referrer`, nunca `history.state`, para detectar página anterior.
- Paginação customizada: não usar `v-model:search` em `v-data-table` — filtrar via computed property manual.
