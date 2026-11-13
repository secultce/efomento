import { elements as el } from './elements';

class ProcessTramit {
    clickTramitButton() {
        cy.get(el.tramitButton).should('be.visible').click();
    }

    confirmTramit() {
        cy.contains('Deseja realizar a tramitação?').should('be.visible');

        cy.contains(
            'As informações serão validadas e o processo será passado adiante, notificando os responsáveis.'
        ).should('be.visible');

        cy.contains('Confirmar').should('be.visible').click();
    }

    clickUnderstandButton() {
        cy.contains('Tramitação realizada').should('be.visible');

        cy.contains('O processo seguirá com outro setor a partir de agora.').should('be.visible');

        cy.contains('Entendi').should('be.visible').click();
    }

    displayMissingDocumentsError(missingDocuments, presentDocuments = []) {
        cy.get('[data-cy=snack-alert]', { timeout: 5000 })
            .should('exist')
            .and('contain.text', 'Gere os documentos obrigatórios antes de tramitar')
            .and(($alert) => {
                missingDocuments.forEach((label) => expect($alert.text()).to.contain(label));
                presentDocuments.forEach((label) => expect($alert.text()).not.to.contain(label));
            });
    }

    tramit() {
        this.clickTramitButton();
        this.confirmTramit();
        this.clickUnderstandButton();
    }
}

export default new ProcessTramit();
