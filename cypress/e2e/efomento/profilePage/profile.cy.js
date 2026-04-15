import Profile from '../../../pages/profile';
import Login from '../../../pages/auth';
import CredentialsReset from '../../../pages/auth/credentialsReset.js';

describe('Página de Perfil', () => {

  let data;
  beforeEach(() => {
    cy.fixture('users').then((tData) => {
      data = tData;
    });
  })


  it('Garante que é possível atualizar nome e e-mail do perfil do usuário e salvar', () => {
    Login.acessarPaginaDeLogin()
    Login.loginComSucesso(data.valid_email, data.password, data.name)
    Profile.acessarPaginaDePerfil()
    Profile.alterarNomeEEmailESalvar(data.updated_name, data.updated_email)
    CredentialsReset.resetarNomeEEmail(data.valid_email, data.name)
  })

  it('Garante que é possível alterar a senha e salvar ', () => {
    Login.acessarPaginaDeLogin()
    Login.loginComSucesso(data.valid_email, data.password, data.name)
    Profile.acessarPaginaDePerfil()
    Profile.alterarSenhaESalvar(data.updated_password, data.password)
    Login.deslogar()

  })

   it('Garante que é exibida mensagem de erro quando as senha não conferem durante alteração da senha', () => {
    Login.acessarPaginaDeLogin()
    Login.loginComSucesso(data.valid_email, data.password, data.name)
    Profile.acessarPaginaDePerfil()
    Profile.retornarErroDeSenhas(data.password, data.updated_password, data.incorrect_password)
    Login.deslogar()
  })

});
