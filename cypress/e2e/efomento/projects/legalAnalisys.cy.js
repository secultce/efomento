import LegalAnalisysWorkflow from '../../../support/workflows/FormalizationWorkflow.js';

describe('Legal Analisys', () => {
    beforeEach(() => {
        cy.fixture('notices').as('notice');
        cy.fixture('projects').as('project');
    });

    describe('Navigation', () => {
        it('should access legal analisys tab', function () {
            LegalAnalisysWorkflow.accessFormalizationTab({
                role: 'formalization',
                notice: this.notice,
                project: this.project,
            });
        });
    });
});
