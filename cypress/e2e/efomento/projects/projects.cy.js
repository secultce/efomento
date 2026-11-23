import { PHASES } from '../../../support/constants/phases.js';
import Project from '../../../pages/project/ProjectPage';
import ProjectWorkflow from '../../../support/workflows/ProjectWorkflow';

describe('Project Page', () => {
    beforeEach(() => {
        cy.resetCypressData();
        cy.loginByRole('fomentation');

        cy.fixture('users').as('user');
        cy.fixture('notices').as('notice');
        cy.fixture('projects').as('project');
    });

    describe('Search Functionality', () => {
        beforeEach(function () {
            ProjectWorkflow.accessProjectListByNotice(this.notice.notice);
        });

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
            Project.findProjectByNonExistentProjectNup();
        });

        it('should search for a project by agent name', function () {
            Project.findProjectByAgentName();
        });
    });

    describe('Supervisor Assignment', () => {
        it('should display the assigned supervisor in the opening project details', function () {
            // Arrange
            const notice = this.notice.notice;
            const project = this.project.opening;

            ProjectWorkflow.accessProjectListByNotice(notice);

            // Act
            ProjectWorkflow.assignSupervisor({
                project,
                phase: PHASES.OPENING,
            }).then((supervisorName) => {
                // Assert
                ProjectWorkflow.validateSupervisorAssigned({ notice, project, supervisorName });
            });
        });
    });
});
