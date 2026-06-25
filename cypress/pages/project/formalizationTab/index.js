import { elements as el } from '../formalizationTab/elements';

class FormalizationTab {
    goToFormalizationTab() {
        cy.get(el.formalizationTab).click();
    }

    fillRequiredFields() {
        cy.get(el.projecNupOpeningTab).should();
    }

    validatePage() {
        cy.get(el.formalizationTab).should('have.value', 'formalization').and('have.attr', 'aria-selected', 'true');
        cy.get(el.rightPainel).should('exist').and('be.visible');
    }
}

export default new FormalizationTab();
