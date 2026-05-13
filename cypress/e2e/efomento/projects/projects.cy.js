import Login from '../../../pages/auth';
import Notice from '../../../pages/notice';
import Project from '../../../pages/project';

describe('Página de Projetos', () => {
  let user;
  let notice;
  beforeEach(() => {
    cy.fixture('users').as('user');
    cy.fixture('notices').as('notice');

    cy.get('@user').then((user) => {
      Login.acessarPaginaDeLogin();
      Login.loginComSucesso(user.valid_email, user.password, user.name);
    });

    cy.get('@notice').then(() => {
        Notice.acessarPaginaDeEditais();
    });

  })

  it('Garante que é possível buscar um projeto pelo número do processo', function() {
    Project.accessProjectPage(this.notice.id);
    Project.findByProccessNumber(this.notice.nup)
  })
});
