import '../commands.js';
import Project from '../../pages/project/ProjectPage.js';
import Notice from '../../pages/notice/NoticePage.js';

class ProjectWorkflow {
    accessProjectByNup({ notice, project }) {
        Notice.visitPage();
        Notice.searchNoticeByNup(notice.noticeNup);
        Notice.goToNoticeDetailsPage(notice.noticeNup);

        Project.findProjectByProjectNup(project.projectNup);
        Project.goToProjectDetailsPage(project.projectNup);
    }

    filterByPhase(phase) {
        Project.clickFilterPhase(phase);
        Project.validateFilterPhase(phase);
    }

    validateProjectPhase({ notice, project, phase }) {
        cy.reload();

        Notice.visitPage();
        Notice.goToNoticeDetailsPage(notice.noticeNup);

        Project.findProjectByProjectNup(project.projectNup);
        Project.validateCurrentPhase(project.projectNup, phase);
    }
}

export default new ProjectWorkflow();
