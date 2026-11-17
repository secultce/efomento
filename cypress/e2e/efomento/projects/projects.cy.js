import ProjectWorkflow from '../../../support/workflows/ProjectWorkflow';
import Project from '../../../pages/project/ProjectPage';

describe('Project Page', () => {
    beforeEach(() => {
        cy.loginByRole('fomentation');

        cy.fixture('users').as('user');
        cy.fixture('notices').as('notice');
        cy.fixture('projects').as('project');
    });

    describe('Search Functionality', () => {
        it('should search for a project by project NUP', function () {
            // Arrange
            const notice = this.notice.notice;
            const project = this.project.opening;

            ProjectWorkflow.accessProjectListByNotice(notice);

            // Act
            Project.findProjectByProjectNup(project.nup);

            // Assert
            Project.verifySearchResultByNup(project.nup);
        });

        it('should return no results for an invalid process number', function () {
            // Arrange
            const notice = this.notice.notice;
            const project = this.project.opening;

            ProjectWorkflow.accessProjectListByNotice(notice);

            // Act
            Project.findProjectByNonExistentProjectNup();

            // Assert
            Project.verifySearchResultByInexistentNup();
        });

        it('should search for a project by agent name', function () {
            // Arrange
            const notice = this.notice.notice;

            ProjectWorkflow.accessProjectListByNotice(notice);

            Project.getProjectData().then(({ agentName, projectNup }) => {
                // Act
                Project.findProjectByAgentName(agentName);

                // Assert
                Project.verifySearchResultByAgent(agentName, projectNup);
            });
        });
    });
});
