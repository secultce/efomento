import { elements as el } from './OpeningTabElements';

class OpeningTab {
    goToOpeningTab() {
        cy.get(el.openingTab).click();
    }

    validatePage() {
        cy.get(el.openingTab).should('have.attr', 'aria-selected', 'true');
        cy.get(el.projecNupOpeningTab).should('be.visible');
    }
}

export default new OpeningTab();
