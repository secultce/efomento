import '../commands.js';
import Notice from '../../pages/notice/index.js';
import Project from '../../pages/project/ProjectPage.js';
import TramitProcess from '../../pages/project/processTramit/ProcessTramit.js';
import LegalAnalisysTab from '../../pages/project/legalAnalysisTab/LegalAnalysisTab.js';
import ProcessReturn from '../../pages/project/returnProcess/ReturnProcess.js';

class LegalAnalisysWorkflow {
    accessLegalAnalysisTab({ role, notice, project }) {
        cy.loginByRole(role);

        Notice.visitPage();

        Notice.searchNoticeByNup(notice.noticeNup);

        Notice.goToNoticeDetailsPage(notice.noticeNup);

        Project.findProjectByProjectNup(project.projectNup);

        Project.goToProjectDetailsPage(project.projectNup);

        LegalAnalisysTab.goToLegalAnalisysTab();

        LegalAnalisysTab.validatePage();
    }

    tramitProcessToFormalizationPhase({ role, notice, project, fileStatus }) {
        this.accessLegalAnalysisTab({ role, notice, project });

        LegalAnalisysTab.selectFileStatus(fileStatus);
        TramitProcess.tramit();
    }

    returnProcessToOpeningPhase({ role, notice, project, documentType }) {
        this.accessLegalAnalysisTab({ role, notice, project });

        ProcessReturn.retunProcesss(documentType);
    }
}

export default new LegalAnalisysWorkflow();
