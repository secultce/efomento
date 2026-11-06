import ProjectWorkflow from './ProjectWorkflow.js';
import Project from '../../pages/project/ProjectPage.js';
import NoticeWorkflow from './NoticeWorkflow.js';

class DocumentWorkflow {
    create({ notice, project, phase, documentType }) {
        NoticeWorkflow.accessNoticeProjectsList(notice.noticeNup);

        ProjectWorkflow.filterByPhase(phase);

        Project.findProjectByProjectNup(project.nup);

        Project.selectProject(project.nup);

        Project.clickCreateDocument(documentType);

        Project.fillDocument(documentType.text);

        Project.saveDocument();

        Project.verifySuccessMessageSaveDocument();
    }

    edit({ notice, project, phase, documentType }) {
        NoticeWorkflow.accessNoticeProjectsList(notice.noticeNup);

        ProjectWorkflow.filterByPhase(phase);

        Project.findProjectByProjectNup(project.nup);

        Project.selectProject(project.nup);

        Project.clickEditDocument(documentType);

        Project.fillDocument(documentType.text);

        Project.saveDocument();

        Project.verifySuccessMessageSaveDocument();
    }
}

export default new DocumentWorkflow();
