import '../commands.js';
import Notice from '../../pages/notice/index.js';
import Project from '../../pages/project/ProjectPage';

class OpeningWorkflow {
    createCI({ role, notice, project, phase, documentType }) {
        cy.loginByRole(role);

        Notice.visitPage();
        Notice.searchNoticeByNup(notice.noticeNup);
        Notice.goToNoticeDetailsPage(notice.noticeNup);

        Project.clickFilterFormalizationPhase(phase);
        Project.validateFilterFormalizationPhase(phase);
        Project.findProjectByProjectNup(project.projectNup);

        Project.selectProject();

        Project.clickCreateDocument(documentType.createButton);
        Project.fillDocument(documentType.text);
        Project.saveDocument();
        Project.verifySuccessMessageSaveDocument();
        Project.validateDocumentCreated(documentType.chip);
        Project.clickEditDocument(documentType.editButton);
        Project.validateDocumentContent(documentType.text);
        Project.clickCancelDocumentButton();
    }
}

export default new OpeningWorkflow();
