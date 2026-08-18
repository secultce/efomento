import { elements as el } from './elements';

class ReturnProcess {
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

    clickSentReturnButton() {
        cy.get(el.retunrProcessSentButton).should('be.visible').click();
    }

    displayModalConfirmReturn() {
        cy.contains('Confirmar envio?')
            .closest('.v-card')
            .within(() => {
                cy.contains(
                    'A devolução deixará de ser editável e as pessoas responsáveis receberão sua notificação.'
                ).should('be.visible');

                cy.contains('button', /^Sim$/).should('be.visible').click();
            });
    }

    displayProcessReturnedSuccessMessage() {
        cy.contains('Devolução realizada')
            .closest('.v-card')
            .within(() => {
                cy.contains('O processo foi devolvido aos responsáveis!').should('be.visible');

                cy.contains('button', 'Entendi').should('be.visible').click();
            });
    }

    retunProcesss(text) {
        this.clickReturnProcessButton();
        this.fillReturnMotive(text);
        this.clickSentReturnButton();
        this.displayModalConfirmReturn();
        this.displayProcessReturnedSuccessMessage();
    }
}

export default new ReturnProcess();
