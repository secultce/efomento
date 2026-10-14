import ProjectWorkflow from '../../../support/workflows/ProjectWorkflow.js';
import LoginWorkflow from '../../../support/workflows/LoginWorkflow.js';

describe('Opening bank selection', () => {
    it('searches the local catalog and preserves the selected bank after saving', () => {
        // Vuetify overlays can defer resize notifications until the next frame.
        cy.on('uncaught:exception', (error) => {
            if (error.message.includes('ResizeObserver loop completed with undelivered notifications.')) return false;
        });

        cy.intercept('https://brasilapi.com.br/**', { forceNetworkError: true }).as('externalBanks');
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

        cy.get('[data-cy="bank-select"] input').clear();
        cy.get('[data-cy="bank-select"] input').type('banco do brasil');
        cy.contains('[role="option"]', '001 - Banco do Brasil S.A.').should('be.visible');

        cy.get('[data-cy="bank-select"] input').clear();
        cy.get('[data-cy="bank-select"] input').type('itau');
        cy.contains('[role="option"]', '341 - ITAÚ UNIBANCO S.A.').should('be.visible');

        cy.get('[data-cy="bank-select"] input').clear();
        cy.get('[data-cy="bank-select"] input').type('banco inexistente xyz');
        cy.contains('Nenhum banco encontrado').should('be.visible');

        cy.get('[data-cy="bank-select"] input').clear();
        cy.get('[data-cy="bank-select"] input').type('001');
        cy.contains('[role="option"]', '001 - Banco do Brasil S.A.').click();

        cy.intercept('PATCH', '**/abertura/*/atualizar').as('saveOpening');
        cy.contains('button', 'Salvar Alterações').click();
        cy.wait('@saveOpening').its('request.body.opening.bank_id').should('be.a', 'number');
        cy.contains('Abertura atualizada com sucesso').should('be.visible');
        cy.reload();
        cy.get('[data-cy="bank-select"]').should('contain', '001 - Banco do Brasil S.A.');

        cy.get('@externalBanks.all').should('have.length', 0);
    });
});
