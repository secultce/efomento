import FormalizationWorkflows from '../../../support/workflows/FormalizationWorkflow.js';
import Login from '../../../pages/auth/index.js';
import Notice from '../../../pages/notice/index.js';
import Project from '../../../pages/project/index.js';

describe('Formalization', () => {
    beforeEach(() => {
        cy.fixture('users').as('user');
        cy.fixture('notices').as('notice');
        cy.fixture('projects').as('project');

        // cy.get('@user').then((user) => {

        // });

        Login.accessLoginPage();
        Login.successLogin('glaucivane.portela@secult.ce.gov.br', 'password', 'Glaucivane Silveira Barbosa Portel');

        Notice.visitPage();
        Notice.verifyPageLoaded();
    });

    it('should not proccess with required field empty', function () {
        const noticeNup = this.notice.noticeNup;
        const projectNup = this.project.project_nup;

        Notice.visitPage();
        Notice.searchNoticeByNup(noticeNup);
        Notice.goToNoticeDetailsPage(noticeNup);
        FormalizationWorkflows.accessFilterFormalizationPhase();
        Project.findProjectByProjectNup(projectNup);
        FormalizationWorkflows.createCulturalExecutionTerm();
    });
});
