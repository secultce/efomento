import { elements as el } from './elements';

class LegalAnalisysTab {
    goToLegalAnalisysTab() {
        cy.get(el.legalAnalysisTab).click();
    }

    validatePage() {
        cy.get(el.legalAnalysisTab).should('have.attr', 'aria-selected', 'true');
        cy.get(el.legalAnalysysRightPanel).should('exist').and('be.visible');
        cy.get(el.documentEvaluationList).should('exist');
    }

    selectDropdownOption(selector, value) {
        const valueStr = value.toString();
        cy.get(selector).should('be.visible').click();

        cy.contains('.v-list-item', valueStr).should('be.visible').click();
    }

    selectFileStatus(documentStatus) {
        cy.get(el.documentEvaluationItem)
            .should('have.length.greaterThan', 0)
            .each(($item) => {
                cy.wrap($item).find(el.documentEvaluationStatus).should('be.visible').click();

                cy.get('.v-overlay__content')
                    .should('be.visible')
                    .contains('.v-list-item', documentStatus)
                    .should('be.visible')
                    .click();
            });
    }
}

export default new LegalAnalisysTab();
