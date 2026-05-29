import Login from '../../../pages/auth';
import Notice from '../../../pages/notice';
import Project from '../../../pages/project';

describe('Project Page', () => {
    beforeEach(() => {
        cy.fixture('users').as('user');
        cy.fixture('notices').as('notice');
        cy.fixture('projects').as('project');

        cy.intercept('GET', '**/editais/*/projetos*').as('projectRequest');

        cy.get('@user').then((user) => {
            Login.accessLoginPage();
            Login.successLogin(user.valid_email, user.password, user.name);
        });

        cy.get('@notice').then((notice) => {
            Notice.visitPage();
            Project.accessProjectPage(notice.id);

            cy.wait('@projectRequest').then(() => {
                cy.wrap(notice).as('currentNotice');
            });
        });

        cy.get('@project').then((project) => {
            cy.wrap(project).as('currentProject');
            Project.displayProjectList();
        });
    });

    describe('Search Functionality', () => {
        it.only('should search for a project by project NUP', function () {
            cy.get('@currentProject').then((project) => {
                Project.findProjectByProjectNup(project.project_nup);
            });
        });

        it('should return no results for an invalid process number', function () {
            Project.findProjectByNonExistentProjectNup();
        });

        it('should search for a project by agent name', function () {
            Project.findProjectByAgentName();
        });
    });
});
