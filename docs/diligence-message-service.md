# DiligenceMessageService — Serviço de Mensagens de Diligência

**Arquivo:** `app/Services/DiligenceMessageService.php`

## O que é uma diligência?

Diligência é uma comunicação oficial entre a equipe do e-Fomento e o agente
cultural responsável por um projeto. Por exemplo: durante o monitoramento, a
equipe pode pedir um relatório atualizado ou documentos pendentes. Essa
conversa acontece por **e-mail**, mas fica registrada no sistema como um
**chat**: cada mensagem enviada e cada resposta recebida aparecem na tela do
projeto, em ordem, como uma conversa.

Este serviço é o "carteiro" do sistema: ele cuida de **enviar** as mensagens
da equipe por e-mail e de **buscar** as respostas dos agentes na caixa de
entrada, guardando tudo no banco de dados para o chat exibir.

---

## Funções

### `send()` — Enviar uma mensagem de diligência

É a função usada quando alguém da equipe escreve uma mensagem no chat de
diligência e clica em enviar. Ela recebe a etapa do projeto à qual a
diligência pertence (por exemplo, o monitoramento), o assunto, o texto da
mensagem, o e-mail do destinatário e quem está enviando.

O que ela faz, passo a passo:

1. **Verifica se já existe conversa anterior.** Se já houve outras mensagens
   nessa diligência, a nova mensagem é marcada como "resposta" da mais
   recente. É isso que faz o e-mail chegar como continuação da mesma conversa
   na caixa do agente, em vez de abrir um e-mail novo solto.

2. **Cria uma "etiqueta de identificação" única para o e-mail.** Todo e-mail
   tem um código invisível que o identifica no mundo (o *Message-ID*). O
   serviço gera um código exclusivo começando com `diligence_`, que depois
   serve para reconhecer quando uma resposta do agente pertence a essa
   conversa.

3. **Guarda a mensagem no banco de dados.** Registra quem enviou, para quem,
   assunto, texto, data e a etiqueta de identificação. É esse registro que
   aparece no chat na tela do projeto.

4. **Envia o e-mail de verdade** para o agente cultural, usando o modelo de
   e-mail de diligência do sistema.

Ao final, devolve a mensagem que acabou de ser criada.

### `syncIncoming()` — Buscar as respostas dos agentes

É a função que "vai até a caixa de correio buscar as cartas". Ela é executada
automaticamente pelo sistema **a cada 5 minutos** (pelo comando agendado
`app:sync-diligence-emails`), para verificar se algum agente cultural
respondeu a uma diligência.

O que ela faz, passo a passo:

1. **Conecta na caixa de entrada de e-mails** do e-Fomento e pega apenas as
   mensagens ainda não lidas.

2. Para cada e-mail encontrado:
   - **Ignora se já foi importado antes** — assim a mesma resposta nunca
     aparece duplicada no chat.
   - **Confere se é resposta de uma diligência conhecida**, usando a etiqueta
     de identificação (aquele código `diligence_...`). Se o e-mail não for
     resposta de nenhuma diligência do sistema (um spam, por exemplo), ele é
     simplesmente ignorado.
   - **Guarda a resposta no banco de dados**, ligada à mesma conversa da
     diligência original, com remetente, assunto, texto e data. Se o e-mail
     veio só em formato visual (HTML), o serviço extrai apenas o texto puro,
     para o chat exibir a mensagem limpa.

3. **Desconecta da caixa de entrada** e informa quantas respostas novas foram
   importadas.

Depois disso, as respostas aparecem automaticamente no chat de diligência do
projeto, como mensagens recebidas.

### `messageIdDomain()` — Definir o "sobrenome" da etiqueta do e-mail

Função interna de apoio (só o próprio serviço usa). A etiqueta de
identificação do e-mail tem o formato `diligence_código@dominio`, parecido
com um endereço de e-mail. Esta função decide qual domínio usar na parte
final:

- Se o endereço de resposta das diligências estiver configurado no sistema
  (por exemplo, `diligencias@efomento.ce.gov.br`), usa o domínio dele
  (`efomento.ce.gov.br`).
- Se não houver configuração válida, usa `efomento.ce.gov.br` como padrão.

Isso garante que a etiqueta sempre tenha um formato válido, aceito pelos
servidores de e-mail.

---

## Como as peças se encaixam

```
Equipe escreve no chat ──► send() ──► e-mail chega ao agente cultural
                                            │
                                            ▼
                                     agente responde
                                            │
Chat exibe a resposta ◄── syncIncoming() ◄──┘
                          (executado periodicamente pelo sistema)
```

## Arquivos relacionados

- `app/Mail/DiligenceMail.php` — monta o e-mail enviado ao agente.
- `app/Http/Controllers/DiligenceMessageController.php` — recebe as ações do
  chat na tela do projeto.
- `resources/js/Components/DiligenceChat.vue` — a tela do chat.
- `routes/console.php` — agenda o comando `app:sync-diligence-emails`, que
  executa o `syncIncoming()` a cada 5 minutos.
- Testes: `tests/Feature/DiligenceMessageServiceTest.php`.
