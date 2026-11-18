import PaymentWorkflow from '../../../support/workflows/PaymentWorkflow.js';
import { DOCUMENTS } from '../../../support/constants/documents.js';
import { PHASES } from '../../../support/constants/phases.js';
import ProjectWorkflow from '../../../support/workflows/ProjectWorkflow.js';

describe('Payment', () => {
    beforeEach(() => {
        cy.resetCypressData();

        cy.fixture('notices').as('notice');
        cy.fixture('projects').as('project');
    });

    describe('Document Generation', () => {
        it('should create payment order', function () {
            // Arrange
            const notice = this.notice.notice;
            const project = this.project.payment;

            cy.loginByRole('financial');

            // Act
            PaymentWorkflow.createPaymentOrder({
                notice,
                project,
                phase: PHASES.PAYMENT,
            });

            // Assert
            ProjectWorkflow.validateDocumentCreated({
                project,
                documentType: DOCUMENTS.paymentOrder,
            });
        });
    });

    describe('Process', () => {
        it('should process projeto to monitoring phase payment order', function () {
            // Arrange
            const notice = this.notice.notice;
            const project = this.project.payment;

            cy.loginByRole('financial');

            PaymentWorkflow.createPaymentOrder({
                notice,
                project,
                phase: PHASES.PAYMENT,
            });

            // Act
            PaymentWorkflow.processProjectToMonitoring({
                notice,
                project,
                phase: PHASES.MONITORING,
            });

            // Assert
            ProjectWorkflow.validateProjectPhase({
                notice,
                project,
                phase: PHASES.MONITORING,
            });
        });
    });
});
