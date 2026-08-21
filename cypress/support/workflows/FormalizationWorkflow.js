import '../commands.js';
import Notice from '../../pages/notice/NoticePage.js';
import Project from '../../pages/project/ProjectPage';
import FormalizationTab from '../../pages/project/formalizationTab/FormalizationTab.js';

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

        Project.clickFilterPhase();
        Project.validateFilterPhase();
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

    createSummaryTerm({ role, notice, project, documentType }) {
        cy.loginByRole(role);

        Notice.visitPage();
        Notice.searchNoticeByNup(notice.noticeNup);
        Notice.goToNoticeDetailsPage(notice.noticeNup);

        Project.clickFilterPhase();
        Project.validateFilterPhase();
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

    tramitWithRequiredFieldsEmpty({ role, notice, project }) {
        this.accessFormalizationTab({ role, notice, project });

        FormalizationTab.clickTramitDisable();
        FormalizationTab.displayRequiredFieldsMessageError();
    }

    returnProcessToLealAnalysisTab({ role, notice, project, documentType }) {
        this.accessFormalizationTab({ role, notice, project });
        FormalizationTab.clickReturnProcessButton();
        FormalizationTab.fillReturnMotive(documentType);
        FormalizationTab.clickSaveReturnMotiveButton();
        FormalizationTab.displayReturnSuccessMessage();
    }
}

export default new ForamlizationWorkflow();
