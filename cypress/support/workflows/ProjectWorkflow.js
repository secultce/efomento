import '../commands.js';
import Project from '../../pages/project/ProjectPage.js';
import NoticeWorkflow from '../workflows/NoticeWorkflow.js';

class ProjectWorkflow {
    accessProjectListByNotice({ notice }) {
        NoticeWorkflow.accessNoticeProjectsList(notice.noticeNup);
    }

    accessProjectDetails({ notice, project }) {
        this.accessProjectListByNotice({ notice });

        Project.findProjectByProjectNup(project.nup ?? project.projectNup);
        Project.goToProjectDetailsPage(project.nup ?? project.projectNup);
    }

    accessProjectByNup({ notice, project }) {
        this.accessProjectDetails({ notice, project });
    }

    filterByPhase(phase) {
        Project.clickFilterPhase(phase);
        Project.validateFilterPhase(phase);
    }

    validateProjectPhase({ notice, project, phase }) {
        cy.reload();

        NoticeWorkflow.accessNoticeProjectsList(notice.noticeNup);

        Project.findProjectByProjectNup(project.nup ?? project.projectNup);
        Project.validateCurrentPhase(project.nup ?? project.projectNup, phase);
    }

    validateDocumentCreated({ project, documentType }) {
        cy.reload();

        Project.findProjectByProjectNup(project.nup ?? project.projectNup);
        Project.selectProject(project.nup ?? project.projectNup);

        Project.clickEditDocument(documentType);
        Project.validateDocumentContent(documentType.text);
        Project.clickCancelDocumentButton();

        Project.validateDocumentCreated(project.nup ?? project.projectNup, documentType.chip);
    }
}

export default new ProjectWorkflow();
