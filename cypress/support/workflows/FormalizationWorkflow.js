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

    createCulturalExecutionTerm({ role, notice, project, documentType }) {
        cy.loginByRole(role);

        Notice.visitPage();
        Notice.searchNoticeByNup(notice.noticeNup);
        Notice.goToNoticeDetailsPage(notice.noticeNup);

        Project.clickFilterFormalizationPhase();
        Project.validateFilterFormalizationPhase();
        Project.findProjectByProjectNup(project.projectNup);

        Project.selectProject();

        Project.clickCreateDocument(documentType.button);
        Project.fillDocument(documentType.text);
        Project.saveDocument();
        Project.verifySuccessMessageSaveDocument();
        Project.validateDocumentCreated(documentType.chip);
    }

    createSummaryTerm({ role, notice, project, documentType }) {
        cy.loginByRole(role);

        Notice.visitPage();
        Notice.searchNoticeByNup(notice.noticeNup);
        Notice.goToNoticeDetailsPage(notice.noticeNup);

        Project.clickFilterFormalizationPhase();
        Project.validateFilterFormalizationPhase();
        Project.findProjectByProjectNup(project.projectNup);

        Project.selectProject();

        Project.clickCreateDocument(documentType.button);
        Project.fillDocument(documentType.text);
        Project.saveDocument();
        Project.verifySuccessMessageSaveDocument();
        Project.validateDocumentCreated(documentType.chip);
    }
}

export default new ForamlizationWorkflow();
