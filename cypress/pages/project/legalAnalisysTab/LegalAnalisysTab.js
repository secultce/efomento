import { elements as el } from './elements';

class LegalAnalisysTab {
    goToLegalAnalisysTab() {
        cy.get(el.legalAnalisysTab).click();
    }

    validatePage() {
        cy.get(el.legalAnalisysTab).should('have.attr', 'aria-selected', 'true');
        cy.get(el.rightPanel).should('exist').and('be.visible');
    }
}

export default new LegalAnalisysTab();
