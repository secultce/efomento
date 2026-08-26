import { elements as el } from './elements';

class FormalizationTab {
    goToFormalizationTab() {
        cy.get(el.formalizationTab).click();
    }

    validatePage() {
        cy.get(el.formalizationTab).should('have.attr', 'aria-selected', 'true');
        cy.get(el.rightPanel).should('exist').and('be.visible');
    }

    selectDropdownOption(selector, value) {
        const valueStr = value.toString();
        cy.get(selector).should('be.visible').click();

        cy.contains('.v-list-item', valueStr).should('be.visible').click();
    }

    fillRequiredFields(reportStatusSelected, termNumber, deliberationOption) {
        cy.get(el.asjurFinalisticProcessingDate).type('01/01/2023');

        cy.get(el.asjurProcessReceivedDate).type('10/03/2023');

        // Processo distribuído para já vem pré-selecionado com o usuário logado.

        this.selectDropdownOption(el.reportStatusSelect, reportStatusSelected);

        cy.get(el.eparceriasCertificateDate).type('10/03/2023');

        cy.get(el.asjurProcessingDateInput).type('10/03/2023');

        cy.get(el.termNumberInput).should('be.visible').clear();

        cy.get(el.termNumberInput).should('be.visible').type(termNumber);

        cy.get(el.termSignedAtInput).should('be.visible').type('10/03/2023');

        cy.get(el.sentToOfficeAt).should('be.visible').type('10/03/2023');

        cy.get(el.signedByOfficeAtInput).should('be.visible').type('10/03/2023');

        cy.get(el.saccNumberInput).should('be.visible').type('123456');

        this.selectDropdownOption(el.cgeAtendeTicketSelect, 'Aberto');

        this.selectDropdownOption(el.deliberationSelect, deliberationOption);

        cy.get(el.sentToChiefOfStaffAtInput).should('be.visible').type('10/03/2023');

        cy.get(el.officialGazettePublishedAtInput).should('be.visible').type('10/03/2023');

        cy.get(el.instrumentValidityStartAtInput).should('be.visible').type('10/03/2023');

        cy.get(el.instrumentValidityEndAtInput).type('10/03/2023');

        cy.get(el.legalOpinionDateInput).should('be.visible').type('10/03/2023');

        cy.get(el.tramitButton).should('be.visible').click();
    }

    displayRequiredFieldsMessageError() {
        cy.get(el.snackbarAlert, { timeout: 5000 })
            .should('exist')
            .and('contain.text', 'Preencha e salve todos os campos obrigatórios em destaque antes de tramitar.');
    }

    clickTramitDisable() {
        cy.get(el.tramitContainer).should('be.visible').click();
    }

    clickTramimtButton() {
        cy.get(el.tramitButton).click();
    }

    displayReturnSuccessMessage() {
        cy.get(el.returnProcessSuccessMessage, { timeout: 5000 }).contains('Processo devolvido com sucesso!');
    }
}

export default new FormalizationTab();
