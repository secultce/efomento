import { elements as el } from '../formalizationTab/elements';

class FormalizationTab {
    goToFormalizationTab() {
        cy.get(el.formalizationTab);
    }

    fillRequiredFields() {
        cy.get(el.projecNupOpeningTab).should();
    }
}

export default new FormalizationTab();
