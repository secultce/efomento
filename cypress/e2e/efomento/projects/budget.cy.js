import { DOCUMENTS } from '../../../support/constants/documents.js';
import { PHASES } from '../../../support/constants/phases.js';
import BudgetWorkflow from '../../../support/workflows/BudgetWorkflow.js';
import ProjectWorkflow from '../../../support/workflows/ProjectWorkflow.js';

describe('Budget', () => {
    beforeEach(() => {
        cy.fixture('notices').as('notice');
        cy.fixture('projects').as('project');
    });

    it('should create budget order', function () {
        // Arrange
        cy.loginByRole('budgetary');

        // Act
        BudgetWorkflow.createBudgetOrder({
            notice: this.notice,
            project: this.project,
            phase: PHASES.BUDGET,
            documentType: DOCUMENTS.budgetOrder,
        });

        // Assert
        ProjectWorkflow.validateDocumentCreated({
            project: this.project,
            documentType: DOCUMENTS.budgetOrder,
        });
    });
});
