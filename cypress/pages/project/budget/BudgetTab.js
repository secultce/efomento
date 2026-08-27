import { elements as el } from './elements.js';

class BudgetTab {
    goToBudgetTab() {
        cy.get(el.budgetTab).click();
    }

    validatePage() {
        cy.get(el.budget).should('have.attr', 'aria-selected', 'true');
        cy.get(el.rightPanel).should('exist').and('be.visible');
    }
}

export default new BudgetTab();
