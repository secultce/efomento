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

    displayRequiredFieldsMessageError() {
        cy.get(el.snackbarAlert, { timeout: 5000 })
            .should('exist')
            .and(
                'contain.text',
                'Preencha os campos obrigatórios antes de tramitar: Informe regularidade e inadimplência, Número do termo, Status do termo, Status de assinatura pelo Gabinete.'
            );
    }

    clickTramitDisable() {
        cy.get(el.tramitContainer).should('be.visible').click();
    }
}

export default new FormalizationTab();
