# Sincronização de Monitoramento — Estratégia e Plano de Ação

## Contexto

Na fase de Monitoramento, o agente cultural envia sua prestação de contas diretamente no Mapa Cultural, inscrevendo-se em uma **oportunidade de monitoramento** (identificada por um selo específico, diferente do edital original). O efomento precisa:

1. Sincronizar essas inscrições de monitoramento via job assíncrono
2. Cruzar a inscrição de monitoramento com o `Project` local pelo campo `number`
3. Registrar um `ProfileSnapshot` com `source = MONITORING` como flag de que a inscrição foi encontrada
4. Habilitar o botão **"VISUALIZAR FICHA DA FASE DO MONITORAMENTO"** na aba, que abre um dialog mostrando `project.data_registration`

**Decisões de design:**
- O dialog exibe `project.data_registration` (dados da inscrição original, já armazenada no projeto)
- `ProfileSnapshot` serve apenas como **flag** — sem nova coluna de dados (sem migração de schema)
- Matching entre inscrição de monitoramento e projeto efomento: `registration.number == project.number`

---

## Fluxo Completo

```
[Scheduler — diário às 07:00]
     ↓
SyncMonitoringJob  (queue: high)
  └── MapasClient::monitoringOpportunities()   ← filtra pelo MAPAS_MONITORING_SEAL
      ↓ para cada opportunity
  SyncMonitoringRegistrationsJob  (queue: medium)
    └── MapasClient::selectedRegistrations($opportunityId)
        ↓ para cada registration (em chunks)
    SyncMonitoringRegistrationJob  (queue: details)
      ├── MapasClient::registrationDetails($registrationId)  ← extrai registration.number
      ├── Project::where('number', $number)->first()          ← cruza com projeto local
      └── ProfileSnapshot::updateOrCreate(
              [object_id, object_type='project', source=MONITORING],
              [recorded_at = now()]
          )

[Frontend — MonitoringTab]
  project.has_monitoring_snapshot === true
     → botão habilitado (amarelo)
     → click → v-dialog exibe project.data_registration
```

---

## Configuração Necessária

Adicionar variável de ambiente para identificar o selo de monitoramento no Mapa Cultural:

```env
MAPAS_MONITORING_SEAL=<id-do-selo-de-monitoramento>
```

Mapeada em `config/efomento.php`:
```php
'mapas_monitoring_seal' => env('MAPAS_MONITORING_SEAL'),
```

---

## Componentes a Criar/Modificar

### Backend

#### 1. `app/Enums/ProfileSnapshotSource.php`
Adicionar novo case:
```php
case MONITORING = 'monitoring';
```

#### 2. `app/Services/MapasClient.php`
Novo método `monitoringOpportunities()`, análogo a `publishedNotices()` mas filtrando pelo `mapas_monitoring_seal`:
```php
public function monitoringOpportunities(): Collection
{
    return collect($this->get('/api/opportunity/find', [
        '@select'      => 'id,name,singleUrl',
        '@seals'       => config('efomento.mapas_monitoring_seal'),
        'publish_site' => 'EQ(Sim)',
    ], authenticated: false));
}
```
Reutiliza `selectedRegistrations()` e `registrationDetails()` existentes — sem duplicação.

#### 3. `app/Jobs/SyncMonitoringJob.php` *(novo)*
Entry point, padrão idêntico ao `SyncNoticesJob`:
- Queue: `high`
- Middleware: `WithoutOverlapping` (1h expiry)
- Chama `monitoringOpportunities()`, dispatcha `SyncMonitoringRegistrationsJob` para cada oportunidade

#### 4. `app/Jobs/SyncMonitoringRegistrationsJob.php` *(novo)*
Padrão idêntico ao `SyncNoticeRegistrationsJob`:
- Recebe `$opportunityId`
- Chama `selectedRegistrations($opportunityId)`
- Chunka resultados e dispatcha `SyncMonitoringRegistrationJob` para cada registration

#### 5. `app/Jobs/SyncMonitoringRegistrationJob.php` *(novo)*
Job central de processamento:
- Recebe `$registrationId`
- Chama `registrationDetails($registrationId)`
- Extrai `registration.number` via `data_get($details, 'registration.number')`
- Busca `Project::where('number', $number)->first()` — se não encontrar, loga warning e retorna
- `ProfileSnapshot::updateOrCreate(['object_id' => $project->id, 'object_type' => 'project', 'source' => ProfileSnapshotSource::MONITORING], ['recorded_at' => now()])`
- Middleware: `RateLimited('mapas-api')` + `WithoutOverlapping`
- Tries: 10, retryUntil: 2h, backoff: [1, 5, 10, 30, 60]

> Campos demográficos do `ProfileSnapshot` ficam `null` (todos são nullable) — o model é reutilizado como flag polimórfico.

#### 6. `app/Models/Project.php`
Nova relação `monitoringSnapshot()`:
```php
public function monitoringSnapshot(): MorphOne
{
    return $this->morphOne(ProfileSnapshot::class, 'object')
        ->where('source', ProfileSnapshotSource::MONITORING)
        ->latestOfMany('recorded_at');
}
```

#### 7. `app/Http/Controllers/ProjectController.php`
No método `projectDetail()`, incluir `'monitoringSnapshot'` no `with()` ou `load()`.

#### 8. `app/Http/Resources/ProjectResource.php`
Expor flag no array de retorno:
```php
'has_monitoring_snapshot' => $this->relationLoaded('monitoringSnapshot')
    ? $this->monitoringSnapshot !== null
    : false,
```

#### 9. `routes/console.php`
Agendamento diário:
```php
Schedule::job(new SyncMonitoringJob, 'high')
    ->dailyAt('07:00')
    ->timezone('America/Fortaleza')
    ->withoutOverlapping(60);
```

---

### Frontend

#### `resources/js/Pages/ProjectDetails/Partials/Tabs/MonitoringTab.vue`

**Computed e ref:**
```js
const hasMonitoringSnapshot = computed(() => props.project.has_monitoring_snapshot === true);
const monitoringDialogOpen = ref(false);
```

**Botão** (entre `<aux-links />` e `<diligence-chat>`):
```vue
<v-btn
    color="primary"
    class="rounded-lg w-full"
    :disabled="!hasMonitoringSnapshot"
    @click="monitoringDialogOpen = true"
>
    Visualizar ficha da fase do Monitoramento
</v-btn>
```

**Dialog** (ao final do template):
```vue
<v-dialog v-model="monitoringDialogOpen" max-width="800" scrollable>
  <v-card class="rounded-lg d-flex flex-column" max-height="85vh">
    <v-card-title class="pa-4">Ficha da fase do Monitoramento</v-card-title>
    <v-divider />
    <v-card-text class="pa-4">
      <!-- Campos de project.data_registration renderizados como pares label/valor -->
    </v-card-text>
    <v-divider />
    <v-card-actions class="pa-4 justify-end">
      <v-btn variant="outlined" @click="monitoringDialogOpen = false">Fechar</v-btn>
    </v-card-actions>
  </v-card>
</v-dialog>
```

O conteúdo do dialog exibirá os campos de `project.data_registration` priorizando: número da inscrição, título do projeto, nome do agente, status consolidado e datas.

---

## Tabela de Arquivos

| Arquivo | Ação |
|---|---|
| `app/Enums/ProfileSnapshotSource.php` | +case MONITORING |
| `app/Services/MapasClient.php` | +monitoringOpportunities() |
| `app/Jobs/SyncMonitoringJob.php` | novo |
| `app/Jobs/SyncMonitoringRegistrationsJob.php` | novo |
| `app/Jobs/SyncMonitoringRegistrationJob.php` | novo |
| `app/Models/Project.php` | +monitoringSnapshot() relation |
| `app/Http/Controllers/ProjectController.php` | +load monitoringSnapshot |
| `app/Http/Resources/ProjectResource.php` | +has_monitoring_snapshot |
| `config/efomento.php` | +mapas_monitoring_seal |
| `routes/console.php` | +schedule SyncMonitoringJob |
| `resources/js/Pages/ProjectDetails/Partials/Tabs/MonitoringTab.vue` | +botão +dialog |

---

## Verificação

1. Criar um `ProfileSnapshot` com `source = 'monitoring'` diretamente via tinker para o projeto de teste — verificar que o botão fica habilitado no frontend
2. Executar `SyncMonitoringJob::dispatch()` via tinker com o selo configurado — verificar logs e criação do snapshot
3. Clicar no botão → dialog abre exibindo os campos de `data_registration`
4. Projeto sem snapshot → botão desabilitado (cinza)
5. Rodar `./vendor/bin/pint` e `eslint` sem erros
