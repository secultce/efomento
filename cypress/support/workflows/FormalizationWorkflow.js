import '../commands.js';
import Notice from '../../pages/notice/index.js';
import Project from '../../pages/project/ProjectPage';
import FormalizationTab from '../../pages/project/formalizationTab/index.js';

class ForamlizationWorkflow {
    accessFormalizationTab({ role, notice, project }) {
        cy.loginByRole(role);

        Notice.visitPage();

        Notice.searchNoticeByNup(notice.noticeNup);

        Notice.goToNoticeDetailsPage(notice.noticeNup);

        Project.findProjectByProjectNup(project.projectNup);

        Project.goToProjectDetailsPage(project.projectNup);

        FormalizationTab.goToFormalizationTab();

        FormalizationTab.validatePage();
    }

    createCulturalExecutionTerm({ role, notice, project, text }) {
        cy.log(role);

        cy.log(notice.noticeNup);

        cy.log(project.projectNup);

        cy.log(text);
        cy.loginByRole(role);

        Notice.visitPage();
        Notice.searchNoticeByNup(notice.noticeNup);
        Notice.goToNoticeDetailsPage(notice.noticeNup);

        Project.clickFilterFormalizationPhase();
        Project.validateFilterFormalizationPhase();
        Project.findProjectByProjectNup(project.projectNup);

        Project.selectProject();

        Project.clickCreateExecutionTerm();
        Project.fillExecutionTerm(text);
        Project.saveExecutionTerm();
        Project.verifySuccessMessageSaveDocument();
        Project.validateExecutionTermCreated();
    }
}

export default new ForamlizationWorkflow();
