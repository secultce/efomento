import Login from '../../../pages/auth'

describe('Login', () => {

  let data;

  before(() => {
    cy.fixture('users').then((tData) => {
      data = tData;
    })
  })

  it('Login com sucesso', () => {
    Login.acessarPaginaDeLogin()
    Login.loginComSucesso(data.valid_email, data.password, data.name)
  })

  it('Login com senha incorreta',() => {
    Login.acessarPaginaDeLogin()
    Login.loginComSenhaIncorreta(data.valid_email, data.incorrect_password)
  })

  it('Login com email inválido', () => {
    Login.acessarPaginaDeLogin()
    Login.loginComEmailInvalido(data.invalid_email, data.password)
  })

  it('Valida que acesso à rota /editais deslogado redireciona para /login', () => {
    Login.acessarPaginaDeEditais()
    Login.validarRedirecionamentoDeslogado()
  })

  it('Valida que ao clicar no botão de logout redireciona para /login', () => {
    Login.acessarPaginaDeLogin()
    Login.loginComSucesso(data.valid_email, data.password, data.name)
    Login.validarQueLogoutRedirecionaParaLogin()
  })
})
