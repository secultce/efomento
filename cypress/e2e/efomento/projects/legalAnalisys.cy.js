import LegalAnalysisWorkflow from '../../../support/workflows/LegalAnalysisWorkflow.js';
import ProjectWorkflow from '../../../support/workflows/ProjectWorkflow.js';
import { PHASES } from '../../../support/constants/phases.js';

describe('Legal Analysis', () => {
    beforeEach(() => {
        cy.fixture('notices').as('notice');
        cy.fixture('projects').as('project');
    });

    describe('Navigation', () => {
        it('should access legal analysis tab', function () {
            LegalAnalysisWorkflow.accessLegalAnalysisTab({
                role: 'formalization',
                notice: this.notice,
                project: this.project,
            });
        });
    });

    describe('Process Project', () => {
        it.only('should process project to formalization successfully', function () {
            //Arrange
            cy.loginByRole('formalization');

            //Act
            LegalAnalysisWorkflow.tramitProcessToFormalizationPhase({
                notice: this.notice,
                project: this.project,
                fileStatus: 'De acordo',
            });

            //Assert
            ProjectWorkflow.validateProjectPhase({
                notice: this.notice,
                project: this.project,
                phase: PHASES.FORMALIZATION,
            });
        });
    });
});
