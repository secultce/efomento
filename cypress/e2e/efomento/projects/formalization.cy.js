import FormalizationWorkflow from '../../../support/workflows/FormalizationWorkflow.js';
import { DOCUMENTS } from '../../../support/constants/documents.js';

describe('Formalization', () => {
    beforeEach(() => {
        cy.resetCypressData();

        cy.fixture('notices').as('notice');
        cy.fixture('projects').as('project');
    });

    describe('Navigation', () => {
        it('should access formalization tab', function () {
            FormalizationWorkflow.accessFormalizationTab({
                role: 'formalization',
                notice: this.notice.notice,
                project: this.project.formalization,
            });
        });
    });

    describe('Validations', () => {
        it('should display a a message error when tramit process with required fields empty', function () {
            FormalizationWorkflow.tramitWithRequiredFieldsEmpty({
                role: 'formalization',
                notice: this.notice.notice,
                project: this.project.formalization,
            });
        });
    });

    describe('Document Generation', () => {
        it('should create cultural execution term', function () {
            FormalizationWorkflow.createCulturalExecutionTerm({
                role: 'formalization',
                notice: this.notice.notice,
                project: this.project.formalization,
                documentType: DOCUMENTS.executionTerm,
            });
        });

        it('should create summary term', function () {
            FormalizationWorkflow.createSummaryTerm({
                role: 'formalization',
                notice: this.notice.notice,
                project: this.project.formalization,
                documentType: DOCUMENTS.summaryTerm,
            });
        });

        it('should create legal opinion', function () {
            FormalizationWorkflow.createLegalOpinion({
                role: 'formalization',
                notice: this.notice.notice,
                project: this.project.formalization,
                documentType: DOCUMENTS.legalOpinion,
            });
        });
    });

    describe('Tramit and Return Process', () => {
        it('should return process to legal analysis phase', function () {
            FormalizationWorkflow.returnProcessToLealAnalysisTab({
                role: 'formalization',
                notice: this.notice.notice,
                project: this.project.formalization,
                documentType: DOCUMENTS.devolutionMotive,
            });
        });
    });
});
