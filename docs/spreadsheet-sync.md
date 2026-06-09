# Sincronização de Planilha Google Sheets → Banco de Dados

> Arquivo local — não commitar.

---

## O que foi construído

Um mecanismo unificado para importar dados de múltiplas abas de uma planilha pública do Google Sheets
diretamente para os models Eloquent, via comando Artisan ou endpoint HTTP.

### Arquivos envolvidos

| Arquivo | Papel |
|---|---|
| `app/Services/GoogleSheetsService.php` | Busca e parseia a planilha; contém a lógica de sync por aba (`importSheet`, `syncFormalization`, `syncBudget`) |
| `app/Services/SpreadsheetImportService.php` | Processa linhas da aba Abertura: cria Agent, Category, Project e Opening |
| `app/Console/Commands/ImportGoogleSheetsCommand.php` | Comando Artisan unificado — aceita `--aba` para escolher quais abas importar |
| `app/Http/Controllers/SpreadsheetSyncController.php` | Controller HTTP fino — delega para `GoogleSheetsService` |
| `config/spreadsheet_mappings.php` | Mapeamento `coluna da planilha → campo do model` por aba |
| `routes/web.php` | `POST /sync/formalizacao`, `POST /sync/orcamento` |

---

## Como funciona

**Aba Abertura** (cria entidades novas):
```
Planilha → fetchSheet() → rows[] por label
        → SpreadsheetImportService::processRow()
        → cria/atualiza Agent (CPF ou CNPJ), Category, Project, Opening
```

**Abas Formalização e Orçamento** (enriquece projetos existentes):
```
Planilha → fetchSheet() → rows[] por label
        → GoogleSheetsService::syncFormalization() / syncBudget()
        → lê config/spreadsheet_mappings.php
        → Project::where('number', row['CÓDIGO INSCRIÇÃO MAPAS'])
        → Formalization/Budget::updateOrCreate(['project_id' => $id], $record)
```

> Datas no formato gviz `Date(Y,M,D)` são convertidas automaticamente para `Y-m-d` antes de chegar ao Eloquent.

### Campos derivados (não vêm da planilha)
- `project_id` → resolvido via `Project::where('number', row['CÓDIGO INSCRIÇÃO MAPAS'])`
- `process_supervisor_id` → `--user-id` do comando / `Auth::id()` via HTTP (somente Formalização)
- `created_by` → `--user-id` do comando / `Auth::id()` via HTTP

### Regras de negócio
- Formalização: linhas com `STATUS = 'Desclassificado'` ou `STATUS = 'Desistente'` são ignoradas
- Abertura: aceita CPF (11 dígitos) e CNPJ (14 dígitos) no campo `CPF / CNPJ DO PROPONENTE`

---

## Mapeamento atual — Formalização

| Coluna na planilha | Campo no banco (`formalizations`) |
|---|---|
| CÓDIGO INSCRIÇÃO MAPAS | *(lookup do `project_id`)* |
| DATA TRAMITAÇÃO FINALÍSTICA > ASJUR | `asjur_finalistic_processing_date` |
| NÚMERO DO TERMO | `term_number` |
| TERMO DE FOMENTO ENVIADO PARA ASSINATURA DO PROPONENTE (DATA) | `term_signature_sent_at` |
| TERMO DE FOMENTO ASSINADO PELO PROPONENTE (DATA) | `term_signed_at` |
| DATA TRAMITAÇÃO ASJUR > GAB | `sent_to_office_at` |
| DATA DE ASSINATURA DO TERMO PELA SECRETÁRIA | `data_sign_gabinete` |
| N° SACC | `sacc_number` |
| CHAMADO CGE ATENDE | `cge_atende_ticket` |
| DELIBERAÇÃO | `deliberation` |
| DATA DE ENVIO PARA CASA CIVIL | `sent_to_chief_of_staff_at` |
| DATA DE PUBLICAÇÃO NO DOE | `official_gazette_published_at` |
| DATA DE INÍCIO DE VIGÊNCIA DO INSTRUMENTO | `validity_start_at` |
| DATA DE TÉRMINO INICIAL DA VIGÊNCIA DO INSTRUMENTO | `validity_end_at` |

---

## Mapeamento atual — Orçamento

| Coluna na planilha | Campo no banco (`budgets`) |
|---|---|
| CÓDIGO INSCRIÇÃO MAPAS | *(lookup do `project_id`)* |
| DATA TRAMITAÇÃO CODIP > COAFI | `processing_date_for_coafi` |
| DATA RECEBIMENTO CODIP | `processing_date_for_codip` |

---

## Como executar

### 1. Rodar a migration (apenas uma vez)

```bash
docker exec <container_php> php artisan migrate
```

### 2. Via comando Artisan (recomendado)

```bash
docker exec <container_php> php artisan app:import-google-sheets \
  ID_DA_PLANILHA \
  --aba="Abertura" --aba="Formalização" --aba="Orçamento" \
  --user-id=<id_do_usuario> \
  --notice-id=<id_do_edital>   # fallback quando o edital não é encontrado pelo external_id
```

Opções disponíveis:

| Opção | Obrigatório | Descrição |
|---|---|---|
| `--aba` | não (padrão: todas) | Abas a importar. Pode repetir para múltiplas. |
| `--user-id` | **sim** | ID do usuário para `created_by` / `user_id` |
| `--notice-id` | não | ID do edital fallback (necessário para Abertura quando `external_id` não bate) |
| `--with-files` | não | Baixar arquivos do MAPAS ao importar projetos (apenas Abertura) |

### 3. Via endpoint HTTP

```bash
curl -X POST https://<seu-dominio>/sync/formalizacao \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: <token>" \
  -d '{"spreadsheet_id": "ID_DA_PLANILHA_AQUI"}'
```

Rotas disponíveis: `POST /sync/formalizacao`, `POST /sync/orcamento`

### 4. Inspecionar colunas via Tinker (diagnóstico)

```bash
docker exec -it <container_php> php artisan tinker
```
```php
$s = app(App\Services\GoogleSheetsService::class);
$data = $s->fetchSheet('ID_DA_PLANILHA', 'Abertura');
// Ver colunas reais:
foreach ($data['columns'] as $i => $col) { echo "[$i] $col\n"; }
// Ver primeira linha:
print_r($data['rows'][0]);
```

> **Como encontrar o ID da planilha:**
> Na URL `https://docs.google.com/spreadsheets/d/**ID_AQUI**/edit`, o ID é a parte entre `/d/` e `/edit`.

> **Requisito:** A planilha precisa estar com acesso "Qualquer pessoa com o link pode visualizar".

---

## Como adicionar uma nova aba

1. **Adicionar o mapeamento** em `config/spreadsheet_mappings.php`:
```php
'pagamento' => [
    'column_for_project_lookup' => 'CÓDIGO INSCRIÇÃO MAPAS',
    'column_map' => [
        'COLUNA DA PLANILHA' => 'campo_do_model',
    ],
],
```

2. **Adicionar método** em `GoogleSheetsService`:
```php
public function syncPagamento(string $spreadsheetId, string $sheetName, int $userId): int
{
    $config = config('spreadsheet_mappings.pagamento');
    // ... mesmo padrão de syncFormalization / syncBudget
}
```

3. **Registrar no comando** em `ImportGoogleSheetsCommand` (bloco `match`):
```php
'Pagamento' => $service->syncPagamento($spreadsheetId, $aba, $userId),
```

4. **Adicionar método no controller** (para o endpoint HTTP):
```php
public function syncPagamento(Request $request): RedirectResponse
{
    $count = $this->sheets->syncPagamento(..., userId: Auth::id());
    return back()->with('success', "{$count} registros sincronizados.");
}
```

5. **Adicionar rota** em `routes/web.php`:
```php
Route::post('/pagamento', [SpreadsheetSyncController::class, 'syncPagamento'])
    ->name('pagamento');
```

---

## Abas implementadas

| Aba | Model(s) | Método no service |
|---|---|---|
| Abertura | `Agent`, `Category`, `Project`, `Opening` | `importSheet()` |
| Formalização | `Formalization` | `syncFormalization()` |
| Orçamento | `Budget` | `syncBudget()` |

## Abas pendentes (aguardando confirmação da equipe)

- Parcela → `Installment`
- Pagamento → `Payment`
- Monitoramento → `Monitoring`
- Prestação de Contas → (a definir)
