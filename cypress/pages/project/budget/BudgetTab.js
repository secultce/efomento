import { elements as el } from './elements.js';

class BudgetTab {
    goToBudgetTab() {
        cy.get(el.budgetTab).click();
    }

    validatePage() {
        cy.get(el.budgetTab).should('have.attr', 'aria-selected', 'true');
        cy.get(el.budgeRightPanel).should('exist').and('be.visible');
    }

    fillRequiredFieldsBudgetOpninion(quotaNumber, installmentAmount) {
        cy.get(el.noticeInstallmentNumberInput).should('be.visible').type(String(quotaNumber));
        cy.get(el.installmentAmountInput).should('be.visible').clear();
        cy.get(el.installmentAmountInput).should('be.visible').type(String(installmentAmount));
    }
}

export default new BudgetTab();
