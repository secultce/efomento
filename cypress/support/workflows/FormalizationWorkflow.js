import '../commands.js';
import Notice from '../../pages/notice/index.js';
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

        Project.clickFilterFormalizationPhase();
        Project.validateFilterFormalizationPhase();
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

        Project.clickFilterFormalizationPhase();
        Project.validateFilterFormalizationPhase();
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

    createLegalOpinion({ role, notice, project, documentType }) {
        cy.loginByRole(role);

        Notice.visitPage();
        Notice.searchNoticeByNup(notice.noticeNup);
        Notice.goToNoticeDetailsPage(notice.noticeNup);

        Project.clickFilterFormalizationPhase();
        Project.validateFilterFormalizationPhase();
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
        FormalizationTab.clickTramitButton();
        FormalizationTab.displayModalConfirmTramit();
        FormalizationTab.displayRequiredFieldsMessageError();
    }

    returnProcessToLealAnalysisTab({ role, notice, project, documentType }) {
        this.accessFormalizationTab({ role, notice, project });
        FormalizationTab.clickReturnProcessButton();
        FormalizationTab.fillReturnMotive(documentType);
        FormalizationTab.clickSaveReturnMotiveButton();
        FormalizationTab.displayModalConfirmReturn();
        FormalizationTab.displayProcessReturnedSuccessMessage();
    }

    tramitProcessWithSuccessToBudget({ role, notice, project }) {
        this.accessFormalizationTab({ role, notice, project });
        FormalizationTab.fillRequiredFields({
            asjurFinalisticProcessingDate: project.asjurFinalisticProcessingDate,
            asjurProcessReceivedDate: project.asjurProcessReceivedDate,
            processAssignedTo: project.processAssignedTo,
            reportStatusSelected: project.reportStatusSelected,
            eparceriasCertificateDate: project.eparceriasCertificateDate,
            asjurProcessingDate: project.asjurProcessingDate,
            responsibleAtAsjur: project.responsibleAtAsjur,
            termNumber: project.termNumber,
            termSignatureSentAt: project.termSignatureSentAt,
            termSignedAt: project.termSignedAt,
            sentToOfficeAt: project.sentToOfficeAt,
            signedByOfficeAt: project.signedByOfficeAt,
            saccNumber: project.saccNumber,
            cgeAtendeTicket: project.cgeAtendeTicket,
            deliberationOption: project.deliberationOption,
            sentToChiefOfStaffAt: project.sentToChiefOfStaffAt,
            officialGazettePublishedAt: project.officialGazettePublishedAt,
            officialGazetteFile: project.officialGazetteFile,
            instrumentValidityStartAt: project.instrumentValidityStartAt,
            instrumentValidityEndAt: project.instrumentValidityEndAt,
        });
        FormalizationTab.clickTramitButton();
        FormalizationTab.displayModalConfirmTramit();
        FormalizationTab.displayModalTramitedProcess();
    }
}

export default new ForamlizationWorkflow();
