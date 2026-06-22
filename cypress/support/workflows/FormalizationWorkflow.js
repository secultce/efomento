import { elements as el } from '../../pages/project/elements';

class FormalizationWorkflow {
    accessFilterFormalizationPhase() {
        cy.get(el.filterProjectPhaseCard).contains('Formalização').click();
    }
    createCulturalExecutionTerm() {
        cy.get(el.rowTableProjectList).within(() => {
            cy.get(el.checkboxProjectList).click();
        });

        cy.get(el.createDocumentButon)
            .should('be.visible')
            .and('not.be.disabled')
            .contains('Criar termo de execução cultural (TC)')
            .click();

        // Wait until TinyMCE is initialized
        cy.window()
            .its('tinymce.activeEditor')
            .should('exist')
            .should((editor) => {
                expect(editor.initialized).to.be.true;
            });

        // Set the content
        cy.window().then((win) => {
            const editor = win.tinymce.activeEditor;
            editor.setContent('<p>My new text</p>');
            editor.focus();

            // Select all text
            editor.selection.select(editor.getBody(), true);

            // Toggle bold
            editor.execCommand('Bold');
            editor.fire('change');
            editor.save();
        });

        // After: verify the content was updated
        cy.window().then((win) => {
            const editor = win.tinymce.activeEditor;

            expect(editor.getContent()).to.contain('My new text');
        });

        // Save cultural execution term text
        cy.get(el.saveDocumentButton).click();

        cy.get(el.rowTableProjectList).within(() => {
            cy.get(el.chipProjectList).should('be.visible').contains('TC');
        });
    }
}

export default new FormalizationWorkflow();
