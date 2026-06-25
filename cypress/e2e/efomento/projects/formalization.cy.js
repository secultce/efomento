import FormalizationWorkflow from '../../../support/workflows/FormalizationWorkflow.js';

describe('Formalization', () => {
    beforeEach(() => {
        cy.fixture('notices').as('notice');
        cy.fixture('projects').as('project');
    });

    describe('Formalization Tab', () => {
        it('should access formalization tab', function () {
            FormalizationWorkflow.accessFormalizationTab({
                role: 'formalization',
                notice: this.notice,
                project: this.project,
            });
        });
    });
});
