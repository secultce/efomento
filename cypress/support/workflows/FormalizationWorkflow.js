import '../commands.js';
import Notice from '../../pages/notice/index.js';
import Project from '../../pages/project/index';
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
}

export default new ForamlizationWorkflow();
