import ProjectWorkflow from '../../../support/workflows/ProjectWorkflow';
import Project from '../../../pages/project/ProjectPage';
import { PHASES } from '../../../support/constants/phases';

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
            Project.findProjectByNonExistentProjectNup();
        });

        it('should search for a project by agent name', function () {
            Project.findProjectByAgentName();
        });
    });

    describe('Filter Functionality', () => {
        it('should filter projects in Opening Phase', function () {
            // Arrange
            const notice = this.notice.notice;
            const project = this.project.opening;
            const phase = PHASES.OPENING;

            ProjectWorkflow.accessProjectListByNotice(notice);
            Project.validateOpeningActionsAbsent();

            // Act
            ProjectWorkflow.filterByPhase(phase);

            // Assert
            ProjectWorkflow.validateProjectsFilteredByPhase({ project, phase, phaseQuery: 'abertura' });
            Project.validateOpeningActions();

            Project.clearPhaseFilter();
            Project.getProjectRowByNup(this.project.legal_analisys.nup).should('be.visible');
            Project.validateOpeningActionsAbsent();
        });
    });
});
