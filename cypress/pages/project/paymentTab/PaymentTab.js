import { elements as el } from './elements';

class PaymentTab {
    goToPaymentTab() {
        cy.get(el.paymentTab).click();
    }

    validatePage() {
        cy.get(el.paymentTab).should('have.attr', 'aria-selected', 'true');
        cy.get(el.paymentPanel).should('be.visible');
    }
}

export default new PaymentTab();
