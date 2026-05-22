import Login from '../../../pages/auth';
import Notice from '../../../pages/notice';
import Project from '../../../pages/project';

describe('Project Page', () => {
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

    it('Ensure it is possible to search for a project by process number', function () {
        cy.get('@currentNotice').then((notice) => {
            Project.findByProcessNumber(notice.nup);
        });
    });

    it('Ensure search returns no results for a invalid process number', function () {
        Project.findByNonExistentProcessNumber();
    });
});
