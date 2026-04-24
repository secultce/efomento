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
    Notice.acessarFomularioDadosDeIdentificacao()
    Notice.preencherDadosDeIdentificacaoDoEdital(notice.nup, notice.processInstrumentType, notice.noticeTotalValue, notice.noticeAccompanimentManager, notice.noticeManagerEmail, notice.quotaNumber)
  })

  it('Garante que é possível buscar editais pelo Título', () => {
    Login.acessarPaginaDeLogin()
    Login.loginComSucesso(user.valid_email, user.password, user.name)
    Notice.acessarPaginaDeEditais()
    Notice.buscarEditalPorTítulo(notice.title)
  })

  it('Garante que é possível filtrar editais Status do processo', () => {
    Login.acessarPaginaDeLogin()
    Login.loginComSucesso(user.valid_email, user.password, user.name)
    Notice.acessarPaginaDeEditais()
    Notice.filtrarEditalPorStatusDoProcesso(notice.processsStatus)
  })

  it.only('Garante que é possível filtrar editais pelo Tipo de Instrumento', () => {
    Login.acessarPaginaDeLogin()
    Login.loginComSucesso(user.valid_email, user.password, user.name)
    Notice.acessarPaginaDeEditais()
    Notice.filtrarEditalPorTipoDeInstrumento(notice.processInstrumentType)
  })

});
