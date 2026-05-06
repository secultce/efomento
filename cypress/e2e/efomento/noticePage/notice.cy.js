import Login from '../../../pages/auth';
import Notice from '../../../pages/notice';

describe('Página de Editais', () => {

  let user;
  let notice;
  beforeEach(() => {
    cy.fixture('users').as('user');
    cy.fixture('notices').as('notice');

    cy.get('@user').then((user) => {
      Login.acessarPaginaDeLogin();
      Login.loginComSucesso(user.valid_email, user.password, user.name);
    });

    cy.get('@notice').then((notice) => {
        Notice.acessarPaginaDeEditais();
    });

  })

  it('Garante que é possível acessar a página de editais', function() {
    cy.get('[data-cy=tabelaListaDeEditais]').should('be.visible');
  })


 it('Garante que é possível visualizar o nome do usuário no Header da página', function() {
    Notice.visualizarNomeDoUsuarioLogadoNoHeader(this.user.name)

  })

  it('Garante que é possível visualizar o dashboard de editais', function() {
    Notice.visualizarDashboard()
  })

  it('Garante que é possível visualizar a mensagem de boas vindas contendo o nomde do usuário logado', function() {
    Notice.visualizarMensagemDeBoasVindasComNomeDoUsuario(this.user.name)
  })

  it('Garante que é possível buscar editais pelo Título', function() {
    Notice.buscarEditalPorTítulo(this.notice.title)
  })

  it('Garante que é possível buscar editais pelo número do processo mãe', function() {
    Notice.buscarEditalPorNumeroDoProcessoMae(this.notice.nup)
  })

  it('Garante que é possível filtrar editais Status do processo', function() {
    Notice.filtrarEditalPorStatusDoProcesso(this.notice.processsStatus)
  })

  it('Garante que é possível filtrar editais pelo Tipo de Instrumento', function() {
    Notice.filtrarEditalPorTipoDeInstrumento(this.notice.processInstrumentType)
  })

  it('Garante que é possível acessar o formulário dados de identificação e processos', function() {
    Notice.acessarFomularioDadosDeIdentificacao()
  })

  it('Garante que é possível preencher o formulário dados de identificação e processos', function() {
    Notice.acessarFomularioDadosDeIdentificacao()
    Notice.preencherDadosDeIdentificacaoDoEdital(this.notice.nup, this.notice.processInstrumentType, this.notice.noticeTotalValue, this.notice.noticeAccompanimentManager, this.notice.noticeManagerEmail, this.notice.quotaNumber)
  })

  it('Garante que é possível acessar as informações do processo', function() {
    Notice.buscarEditalPorNumeroDoProcessoMae(this.notice.nup)
    Notice.visualizarInformaçõesDoProcesso(this.notice.nup)
  })

  it('Garante que é possível alterar a quantidade de itens exibidos na lista de editais', function() {
    Notice.alterarQuantidadeDeExibicaoListaDeEditais(this.notice.quantityPerPage)
  })

  it('Garante que é possível mudar a página na listagem de editais', function() {
    Notice.alterarPaginaDaListaDeEditais()
  })

});
