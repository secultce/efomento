# Notificação de pagamento ao proponente

Ao importar o relatório de pagamentos, a transição para pago e regular (valores
previsto, empenhado, liquidado e pago iguais) dispara `InstallmentPaidEvent` após o
commit. O listener `SendInstallmentPaidEmail` usa a fila `default`, já atendida pelo
worker geral. Configure `QUEUE_CONNECTION=database` (ou `redis`) e o transporte
`MAIL_*`; não use `sync` em produção para esse fluxo.

O destinatário é o e-mail válido de `Agent.latestSnapshot`, com fallback para
`Agent.director_email`. Sem destinatário válido, o envio é registrado como `failed`
e não é tentado. O histórico fica em `agent_email_logs`, com agente, projeto,
parcela (relacionamento polimórfico), destinatário, assunto, status e erro.
As mudanças de `status`, `error_message` e `sent_at` são preservadas em `audits`,
inclusive nas tentativas executadas pelo worker com `AUDITING_CONSOLE=false`,
respeitando `AUDITING_ENABLED`. Uma falha é persistida antes de sinalizar a nova
tentativa à fila. A exclusão física de um agente preserva os registros de e-mail,
definindo `agent_id` como nulo.
`recipient_email` fica vazio quando nenhum endereço válido está disponível.

Há uma chave única por notificação de parcela e bloqueio do registro durante o
envio para evitar duplicidade em reimportações e processamento concorrente.
Falhas de transporte são registradas e permitem até três tentativas automáticas,
com intervalo de 60 segundos. Como SMTP e banco não compartilham transação, uma
interrupção após o servidor aceitar a mensagem e antes de registrar `sent` pode
resultar em reenvio.

Para validar localmente, configure o SMTP de teste, execute as migrations e mantenha
o worker ativo. Importe pela lista de editais um relatório que torne uma parcela
paga e regular; confira a caixa de entrada no Roundcube (porta 8025) e o registro
`sent`, com `sent_at` preenchido. Reimporte o mesmo relatório para conferir que não
há outro envio.

```bash
docker compose exec app php artisan migrate
docker compose up -d queue greenmail roundcube
docker compose exec app php artisan test tests/Feature/InstallmentPaidEmailTest.php tests/Feature/InstallmentImportServiceTest.php tests/Unit/InstallmentPaidMailTest.php
```
