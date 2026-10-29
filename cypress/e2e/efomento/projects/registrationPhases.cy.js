import ProjectWorkflow from '../../../support/workflows/ProjectWorkflow.js';
import LoginWorkflow from '../../../support/workflows/LoginWorkflow.js';

describe('Registration phases modal', () => {
    it('opens and closes the modal with the registration of each phase', () => {
        cy.fixture('users').then(({ fomentation }) => {
            LoginWorkflow.goToLoginPage();
            LoginWorkflow.loginWithTwoFactorAndTrustDevice(fomentation.email, fomentation.password);
            cy.location('pathname').should('eq', '/editais');
        });

        cy.fixture('notices').then(({ notice }) => {
            cy.fixture('projects').then(({ opening }) => {
                ProjectWorkflow.accessProjectByNup({ notice, project: { projectNup: opening.nup } });
            });
        });

        cy.contains('[data-cy="registration-phases-button"]', 'CONFERIR DOCUMENTAÇÃO').click();
        cy.contains('Dados completos do proponente').should('be.visible');

        cy.contains('button', 'Fechar').click();
        cy.contains('Dados completos do proponente').should('not.exist');

        cy.get('[data-cy="registration-phases-button"]').click();
        cy.get('body').type('{esc}');
        cy.contains('Dados completos do proponente').should('not.exist');
    });
});
