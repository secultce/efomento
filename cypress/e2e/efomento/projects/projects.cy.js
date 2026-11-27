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
        beforeEach(function () {
            cy.fixture('openingTramit').as('openingTramit');
            ProjectWorkflow.accessProjectListByNotice(this.notice.notice);
            Project.validateOpeningActionsAbsent();
        });

        it('should filter projects in Opening Phase', function () {
            // Arrange
            const expectedNups = [this.project.opening.nup, ...Object.values(this.openingTramit).map(({ nup }) => nup)];
            const phase = PHASES.OPENING;

            // Act
            ProjectWorkflow.filterByPhase(phase);

            // Assert
            ProjectWorkflow.validateProjectsFilteredByPhase({ expectedNups, phase, phaseQuery: 'abertura' });
            Project.validateOpeningActions();

            ProjectWorkflow.clearPhaseFilterAndValidateProjectVisible(this.project.legal_analisys.nup);
        });

        it('should filter projects in Legal Analysis Phase', function () {
            // Arrange
            const expectedNups = [this.project.legal_analisys.nup];
            const phase = PHASES.LEGAL_ANALYSIS;

            // Act
            ProjectWorkflow.filterByPhase(phase);

            // Assert
            ProjectWorkflow.validateProjectsFilteredByPhase({ expectedNups, phase, phaseQuery: 'analise_juridica' });
            Project.validateOpeningActionsAbsent();

            ProjectWorkflow.clearPhaseFilterAndValidateProjectVisible(this.project.opening.nup);
        });

        it('should filter projects in Formalization Phase', function () {
            // Arrange
            const expectedNups = [this.project.formalization.nup];
            const phase = PHASES.FORMALIZATION;

            // Act
            ProjectWorkflow.filterByPhase(phase);

            // Assert
            ProjectWorkflow.validateProjectsFilteredByPhase({ expectedNups, phase, phaseQuery: 'formalizacao' });
            Project.validateFormalizationActions();

            ProjectWorkflow.clearPhaseFilterAndValidateProjectVisible(this.project.opening.nup);
        });
    });
});
