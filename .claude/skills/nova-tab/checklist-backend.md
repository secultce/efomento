# Checklist de backend para uma nova etapa do Project

Use este checklist apenas quando o usuário confirmar que quer criar o backend da etapa. Antes de gerar cada item, leia o arquivo de referência indicado — ele é a fonte de verdade do padrão.

1. **Migration** — tabela `<etapas>` (plural snake_case) com `project_id` FK, campos de data como `date`, `created_by`, `softDeletes`, timestamps. Referência: migration da tabela `formalizations` em `database/migrations/`.

2. **Model** — `app/Models/<Etapa>.php`. Referência: `app/Models/Formalization.php`:
   - `implements Auditable` + traits `AuditableTrait, HasCreatedBy, HasFactory, HasFiles, SoftDeletes`
   - `$fillable` completo, `$casts` com `'campo' => 'date'` para datas e enums com classe
   - relação `project()` (`belongsTo`)

3. **Relação no Project** — adicionar `HasOne` em `app/Models/Project.php`. Atenção ao nome: é ele que o frontend usa em `props.project.<relacao>` (ex.: `formalizations()` é plural — siga o nome decidido com o usuário).

4. **Eager load** — incluir a relação (e `.files` se tiver upload) no `with()` do método do `ProjectController` que renderiza a página de detalhes (`app/Http/Controllers/ProjectController.php`).

5. **Controller da etapa** — `app/Http/Controllers/<Etapa>Controller.php` com `store`/`update` finos delegando para Service se houver lógica. Referência: `app/Http/Controllers/FormalizationController.php`.

6. **Form Request** — validação em `app/Http/Requests/<Etapa>/`. Referência: requests de Formalization no mesmo diretório.

7. **Rotas** — em `routes/web.php`: **URL em português** (ex. `/projetos/{project}/formalizacao`), **nome em inglês** no padrão `projects.<relacao>.store|update`. Referência: rotas de formalizations já existentes no arquivo.

8. **Enums** (se a etapa tiver campos de status) — `app/Enums/`, cases em `SCREAMING_SNAKE_CASE` com backed string idêntica ao nome do case.

9. **Rodar** `docker compose exec app php artisan migrate` e os testes (`docker compose exec app php artisan test`).
