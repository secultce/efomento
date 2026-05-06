import Login from '../../../pages/auth';
import Notice from '../../../pages/notice';

describe('Página de Editais', () => {

  let user;
  let notice;
  beforeEach(() => {
    cy.fixture('users').then((tData) => {
      user = tData;
    });
    cy.fixture('notices').then((tempData) => {
      notice = tempData;
    });

  })

  it('Garante que é possível acessar a página de editais', () => {
    Login.acessarPaginaDeLogin()
    Login.loginComSucesso(user.valid_email, user.password, user.name)
    Notice.acessarPaginaDeEditais()
  })


 it('Garante que é possível visualizar o nome do usuário no Header da página', () => {
    Login.acessarPaginaDeLogin()
    Login.loginComSucesso(user.valid_email, user.password, user.name)
    Notice.acessarPaginaDeEditais()
    Notice.visualizarNomeDoUsuarioLogadoNoHeader(user.name)

  })

  it('Garante que é possível visualizar o dashboard de editais', () => {
    Login.acessarPaginaDeLogin()
    Login.loginComSucesso(user.valid_email, user.password, user.name)
    Notice.acessarPaginaDeEditais()
    Notice.visualizarDashboard()
  })

  it('Garante que é possível visualizar a mensagem de boas vindas contendo o nomde do usuário logado', () => {
    Login.acessarPaginaDeLogin()
    Login.loginComSucesso(user.valid_email, user.password, user.name)
    Notice.acessarPaginaDeEditais()
    Notice.visualizarMensagemDeBoasVindasComNomeDoUsuario(user.name)
  })

  it('Garante que é possível buscar editais pelo Título', () => {
    Login.acessarPaginaDeLogin()
    Login.loginComSucesso(user.valid_email, user.password, user.name)
    Notice.acessarPaginaDeEditais()
    Notice.buscarEditalPorTítulo(notice.title)
  })

  it('Garante que é possível buscar editais pelo número do processo mãe', () => {
    Login.acessarPaginaDeLogin()
    Login.loginComSucesso(user.valid_email, user.password, user.name)
    Notice.acessarPaginaDeEditais()
    Notice.buscarEditalPorNumeroDoProcessoMae(notice.nup)
  })

  it('Garante que é possível filtrar editais Status do processo', () => {
    Login.acessarPaginaDeLogin()
    Login.loginComSucesso(user.valid_email, user.password, user.name)
    Notice.acessarPaginaDeEditais()
    Notice.filtrarEditalPorStatusDoProcesso(notice.processsStatus)
  })

  it('Garante que é possível filtrar editais pelo Tipo de Instrumento', () => {
    Login.acessarPaginaDeLogin()
    Login.loginComSucesso(user.valid_email, user.password, user.name)
    Notice.acessarPaginaDeEditais()
    Notice.filtrarEditalPorTipoDeInstrumento(notice.processInstrumentType)
  })

  it('Garante que é possível acessar o formulário dados de identificação e processos', () => {
    Login.acessarPaginaDeLogin()
    Login.loginComSucesso(user.valid_email, user.password, user.name)
    Notice.acessarPaginaDeEditais()
    Notice.acessarFomularioDadosDeIdentificacao()
  })

  it('Garante que é possível preencher o formulário dados de identificação e processos', () => {
    Login.acessarPaginaDeLogin()
    Login.loginComSucesso(user.valid_email, user.password, user.name)
    Notice.acessarPaginaDeEditais()
    Notice.acessarFomularioDadosDeIdentificacao()
    Notice.preencherDadosDeIdentificacaoDoEdital(notice.nup, notice.processInstrumentType, notice.noticeTotalValue, notice.noticeAccompanimentManager, notice.noticeManagerEmail, notice.quotaNumber)
  })

  it('Garante que é possível acessar as informações do processo', () => {
    Login.acessarPaginaDeLogin()
    Login.loginComSucesso(user.valid_email, user.password, user.name)
    Notice.acessarPaginaDeEditais()
    Notice.buscarEditalPorNumeroDoProcessoMae(notice.nup)
    Notice.visualizarInformaçõesDoProcesso(notice.nup)
  })

  it('Garante que é possível alterar a quantidade de itens exibidos na lista de editais', () => {
    Login.acessarPaginaDeLogin()
    Login.loginComSucesso(user.valid_email, user.password, user.name)
    Notice.acessarPaginaDeEditais()
    Notice.alterarQuantidadeDeExibicaoListaDeEditais(notice.quantityPerPage)
  })

  it('Garante que é possível mudar a página na listagem de editais', () => {
    Login.acessarPaginaDeLogin()
    Login.loginComSucesso(user.valid_email, user.password, user.name)
    Notice.acessarPaginaDeEditais()
    Notice.alterarPaginaDaListaDeEditais()
  })

});
