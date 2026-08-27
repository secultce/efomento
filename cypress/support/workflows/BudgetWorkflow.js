import Notice from '../../pages/notice/NoticePage.js';
import Project from '../../pages/project/ProjectPage.js';

class BudgetWorkflows {
    createBudgetOrder({ notice, project, phase, documentType }) {
        Notice.visitPage();
        Notice.searchNoticeByNup(notice.noticeNup);
        Notice.goToNoticeDetailsPage(notice.noticeNup);

        Project.findProjectByProjectNup(project.projectNup);
        Project.clickFilterPhase(phase);

        Project.selectProject(project.projectNup);

        Project.clickCreateBudgetOrder(documentType.createButton);

        Project.fillDocument(documentType.text);
        cy.intercept('GET', '**/editais/*/projetos?phase=abertura&search=*').as('reloadProjects');

        Project.saveDocument();
        Project.verifySuccessMessageSaveDocument();

        Project.selectProject(project.projectNup);
    }
}

export default new BudgetWorkflows();
