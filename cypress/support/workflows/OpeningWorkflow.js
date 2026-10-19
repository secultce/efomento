import '../commands.js';
import Notice from '../../pages/notice/NoticePage.js';
import Project from '../../pages/project/ProjectPage';
import OpeningTab from '../../pages/project/openingTab/OpeningTab.js';
import ProcessTramit from '../../pages/project/processTramit/ProcessTramit.js';

class OpeningWorkflow {
    createCI({ role, notice, project, phase, documentType }) {
        cy.loginByRole(role);

        Notice.visitPage();
        Notice.searchNoticeByNup(notice.noticeNup);
        Notice.goToNoticeDetailsPage(notice.noticeNup);

        Project.clickFilterFormalizationPhase(phase);
        Project.validateFilterFormalizationPhase(phase);

        Project.findProjectByProjectNup(project.projectNup);
        Project.selectProject(project.projectNup);

        Project.clickCreateCI(documentType.createButton);
        Project.fillDocument(documentType.text);
        cy.intercept('GET', '**/editais/*/projetos?phase=abertura&search=*').as('reloadProjects');

        Project.saveDocument();
        Project.verifySuccessMessageSaveDocument();

        Project.selectProject(project.projectNup);

        cy.wait('@reloadProjects');

        Project.validateDocumentCreated(project.projectNup, documentType.chip);
        Project.clickEditCI(documentType.editButton);
        Project.validateDocumentContent(documentType.text);
        Project.clickCancelDocumentButton();
    }

    accessOpeningTab({ role, notice, project }) {
        cy.loginByRole(role);

        Notice.visitPage();
        Notice.searchNoticeByNup(notice.noticeNup);
        Notice.goToNoticeDetailsPage(notice.noticeNup);

        Project.findProjectByProjectNup(project.nup);
        Project.goToProjectDetailsPage(project.nup);

        OpeningTab.goToOpeningTab();
        OpeningTab.validatePage();
    }

    tramitWithMissingDocuments({ role, notice, project, missingDocuments, presentDocuments = [] }) {
        this.accessOpeningTab({ role, notice, project });

        ProcessTramit.clickTramitButton();
        ProcessTramit.confirmTramit();
        ProcessTramit.displayMissingDocumentsError(missingDocuments, presentDocuments);
    }

    tramitWithRequiredDocuments({ role, notice, project }) {
        this.accessOpeningTab({ role, notice, project });

        ProcessTramit.tramit();
    }
}

export default new OpeningWorkflow();
