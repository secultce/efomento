import { elements as el } from '../formalizationTab/elements';

class FormalizationTab {
    goToFormalizationTab() {
        cy.get(el.formalizationTab).click();
    }

    validatePage() {
        cy.get(el.formalizationTab).should('have.attr', 'aria-selected', 'true');
        cy.get(el.rightPanel).should('exist').and('be.visible');
    }

    clickReturnProcessButton() {
        cy.get(el.returnProcessButton).should('be.visible').click();
    }
}

export default new FormalizationTab();
