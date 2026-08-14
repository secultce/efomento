import '../commands.js';
import Notice from '../../pages/notice/index.js';
import Project from '../../pages/project/ProjectPage';
import LegalAnalisysTab from '../../pages';

class LegalAnalisysWorkflow {
    accessLegalAnalisysTab({ role, notice, project }) {
        cy.loginByRole(role);

        Notice.visitPage();

        Notice.searchNoticeByNup(notice.noticeNup);

        Notice.goToNoticeDetailsPage(notice.noticeNup);

        Project.findProjectByProjectNup(project.projectNup);

        Project.goToProjectDetailsPage(project.projectNup);

        LegalAnalisysTab.goToFormalizationTab();
    }
}

export default new LegalAnalisysWorkflow();
