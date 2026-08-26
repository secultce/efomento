import '../commands.js';
// import Project from '../../pages/project/ProjectPage.js';
import TramitProcess from '../../pages/project/processTramit/ProcessTramit.js';
import LegalAnalisysTab from '../../pages/project/legalAnalysisTab/LegalAnalysisTab.js';
import ProcessReturn from '../../pages/project/returnProcess/ReturnProcess.js';
import ProjectWorkflow from './ProjectWorkflow.js';

class LegalAnalisysWorkflow {
    accessLegalAnalysisTab({ notice, project }) {
        ProjectWorkflow.accessProjectByNup({
            notice,
            project,
        });

        LegalAnalisysTab.goToLegalAnalisysTab();

        LegalAnalisysTab.validatePage();
    }

    tramitProcessToFormalizationPhase({ notice, project, fileStatus }) {
        this.accessLegalAnalysisTab({ notice, project });

        LegalAnalisysTab.selectFileStatus(fileStatus);
        TramitProcess.tramit();
    }

    returnProcessToOpeningPhase({ role, notice, project, documentType }) {
        this.accessLegalAnalysisTab({ role, notice, project });

        ProcessReturn.retunProcesss(documentType);
    }
}

export default new LegalAnalisysWorkflow();
