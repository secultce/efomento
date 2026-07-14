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

    displayMessageErrorRequiredFields() {
        cy.get(el.snackbarAlert, { timeout: 5000 })
            .contains(
                'Preencha os campos obrigatórios antes de tramitar: Informe regularidade e inadimplência, Número do termo, Status do termo, Status de assinatura pelo Gabinete, Deliberação é obrigatória.'
            )
            .should('be.visible');
    }

    clickTramitButton() {
        cy.get(el.tramitButton).should('be.visible').and('not.be.disabled').contains('tramitar').click();
    }

    clickReturnProcessButton() {
        cy.get(el.returnProcessButton).should('be.visible').click();
    }

    fillReturnMotive(text) {
        cy.window({ timeout: 10000 }).then((win) => {
            cy.wrap(null, { log: false }).should(() => {
                expect(win.tinymce).to.exist;
                expect(win.tinymce.activeEditor).to.exist;
                expect(win.tinymce.activeEditor.initialized).to.be.true;
            });
        });

        cy.window().then((win) => {
            const editor = win.tinymce.activeEditor;
            editor.setContent(`<p>${text}</p>`);
            editor.focus();

            editor.selection.select(editor.getBody(), true);

            editor.execCommand('Bold');
            editor.fire('change');
            editor.save();
        });
    }

    clickSaveReturnMotiveButton() {
        cy.get(el.retunrProcessSentButton).contains('Enviar').click();
    }

    displayReturnSuccessMessage() {
        cy.get(el.returnProcessSuccessMessage, { timeout: 5000 }).contains('Processo devolvido com sucesso!');
    }
}

export default new FormalizationTab();
