import LegalAnalysisWorkflow from '../../../support/workflows/LegalAnalysisWorkflow.js';
import { DOCUMENTS } from '../../../support/constants/documents.js';
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

    describe('Process and return Project', () => {
        it('should process project to formalization successfully', function () {
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

        it('should return project to opening tab successfully', function () {
            LegalAnalysisWorkflow.returnProcessToOpeningPhase({
                role: 'formalization',
                notice: this.notice,
                project: this.project,
                documentType: DOCUMENTS.devolutionMotive,
            });
        });
    });
});
