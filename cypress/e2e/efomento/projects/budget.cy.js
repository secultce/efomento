import { DOCUMENTS } from '../../../support/constants/documents.js';
import { PHASES } from '../../../support/constants/phases.js';
import BudgetWorkflow from '../../../support/workflows/BudgetWorkflow.js';
import ProjectWorkflow from '../../../support/workflows/ProjectWorkflow.js';

describe('Budget', () => {
    beforeEach(() => {
        cy.resetCypressData();

        cy.fixture('notices').as('notice');
        cy.fixture('projects').as('project');
    });

    describe('Generate Documents', () => {
        it('should create budget order', function () {
            // Arrange
            cy.loginByRole('budgetary');

            // Act
            BudgetWorkflow.createBudgetOpinion({
                notice: this.notice.notice,
                project: this.project.budgetary,
                phase: PHASES.BUDGET,
                documentType: DOCUMENTS.budgetOrder,
            });

            // Assert
            ProjectWorkflow.validateDocumentCreated({
                project: this.project.budgetary,
                documentType: DOCUMENTS.budgetOrder,
            });
        });
    });

    describe('Process and Return Project', () => {
        it('should process project to payment phase', function () {
            // Arrange
            cy.loginByRole('budgetary');

            //act
            ProjectWorkflow.accessProjectByNup({
                notice: this.notice.notice,
                project: this.project.budgetary,
            });

            BudgetWorkflow.processProjecToPaymentPhase({
                notice: this.notice.notice,
                project: this.project.budgetary,
            });

            // Assert
            ProjectWorkflow.validateProjectPhase({
                notice: this.notice.notice,
                project: this.project.budgetary,
                phase: PHASES.PAYMENT,
            });
        });

        it('should return project to formalization phase', function () {
            // Assert
            cy.loginByRole('budgetary');

            //act
            ProjectWorkflow.accessProjectByNup({
                notice: this.notice.notice,
                project: this.project.budgetary,
            });

            BudgetWorkflow.returnProjectToFormalizationPhase({
                notice: this.notice.notice,
                project: this.project.budgetary,
                devolutionMotiveText: DOCUMENTS.devolutionMotive,
            });

            // Assert
            ProjectWorkflow.validateProjectPhase({
                notice: this.notice.notice,
                project: this.project.budgetary,
                phase: PHASES.FORMALIZATION,
            });
        });
    });
});
