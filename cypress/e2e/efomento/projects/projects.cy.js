import Login from '../../../pages/auth';
import Notice from '../../../pages/notice';
import Project from '../../../pages/project';

describe('Página de Projetos', () => {
    beforeEach(() => {
        cy.fixture('users').as('user');
        cy.fixture('notices').as('notice');

        cy.intercept('GET', '**/editais/*/projetos*').as('projectRequest');

        cy.get('@user').then((user) => {
            Login.acessarPaginaDeLogin();
            Login.loginComSucesso(user.valid_email, user.password, user.name);
        });

        cy.get('@notice').then((notice) => {
            Notice.acessarPaginaDeEditais();
            Project.accessProjectPage(notice.id);

            cy.wait('@projectRequest').then(() => {
                cy.wrap(notice).as('currentNotice');
            });
        });
    });

    it('Garante que é possível buscar um projeto pelo número do processo', function() {
        cy.get('@currentNotice').then((notice) => {
            Project.findByProcessNumber(notice.nup);
        });
    });

    it('Garante que a busca não retorna resultados para um número de processo inválido', function() {
        Project.findByNonExistentProcessNumber();
    });
});

