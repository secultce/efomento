import LegalAnalysisWorkflow from '../../../support/workflows/LegalAnalysisWorkflow.js';
import { DOCUMENTS } from '../../../support/constants/documents.js';

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
            LegalAnalysisWorkflow.tramitProcessToFormalizationPhase({
                role: 'formalization',
                notice: this.notice,
                project: this.project,
                fileStatus: 'De acordo',
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
