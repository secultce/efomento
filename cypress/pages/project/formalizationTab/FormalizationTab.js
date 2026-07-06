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

    fillRequiredFields(reportStatusSelected, termNumber, statusTerm, signatureStatus) {
        this.selectDropdownOption(el.reportStatusSelect, reportStatusSelected);

        cy.get(el.termNumberInput).type(termNumber);

        this.selectDropdownOption(el.statusTermSelect, statusTerm);

        this.selectDropdownOption(el.signatureStatusOfficeSelect, signatureStatus);
    }

    displayMessageErrorRequiredFields() {
        cy.get(el.snackbarAlert, { timeout: 5000 })
            .contains(
                'Preencha os campos obrigatórios antes de tramitar: Informe regularidade e inadimplência, Número do termo, Status do termo, Status de assinatura pelo Gabinete, Deliberação é obrigatória.'
            )
            .should('be.visible');
    }

    clickTramitButton() {
        cy.get(el.tramitButton).should('be.visible').and('not.be.disabled').contains('tramitar').click();
    }
}

export default new FormalizationTab();
