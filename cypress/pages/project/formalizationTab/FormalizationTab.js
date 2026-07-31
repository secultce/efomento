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

    fillRequiredFields({
        asjurFinalisticProcessingDate,
        asjurProcessReceivedDate,
        processAssignedTo,
        reportStatusSelected,
        eparceriasCertificateDate,
        asjurProcessingDate,
        responsibleAtAsjur,
        termNumber,
        termSignatureSentAt,
        termSignedAt,
        sentToOfficeAt,
        signedByOfficeAt,
        saccNumber,
        cgeAtendeTicket,
        deliberationOption,
        sentToChiefOfStaffAt,
        officialGazettePublishedAt,
        instrumentValidityStartAt,
        instrumentValidityEndAt,
    }) {
        cy.get(el.asjurFinalisticProcessingDate).find('input').type(asjurFinalisticProcessingDate);

        cy.get(el.asjurProcessReceivedDate).type(asjurProcessReceivedDate);

        cy.get(el.processAssignedToInput).type(processAssignedTo);

        cy.log(reportStatusSelected);

        this.selectDropdownOption(el.reportStatusSelect, reportStatusSelected);

        cy.get(el.eparceriasCertificateDate).type(eparceriasCertificateDate);

        cy.get(el.responsibleAtAsjurInput).type(responsibleAtAsjur);

        cy.get(el.asjurProcessingDateInput).type(asjurProcessingDate);

        cy.get(el.termNumberInput).should('be.visible').clear();

        cy.get(el.termNumberInput).should('be.visible').type(termNumber);

        cy.get(el.termSignatureSentAtInput).should('be.visible').type(termSignatureSentAt);

        cy.get(el.termSignedAtInput).type(termSignedAt);

        cy.get(el.sentToOfficeAt).should('be.visible').type(sentToOfficeAt);

        cy.get(el.signedByOfficeAtInput).should('be.visible').type(signedByOfficeAt);

        cy.get(el.saccNumberInput).should('be.visible').type(saccNumber);

        cy.get(el.cgeAtendeTicketInput).should('be.visible').type(cgeAtendeTicket);

        this.selectDropdownOption(el.deliberationSelect, deliberationOption);

        cy.get(el.sentToChiefOfStaffAtInput).should('be.visible').type(sentToChiefOfStaffAt);

        cy.get(el.officialGazettePublishedAtInput).should('be.visible').type(officialGazettePublishedAt);

        cy.get(el.officialGazetteFileInput).selectFile('cypress/fixtures/teste.pdf', { force: true });

        cy.get(el.instrumentValidityStartAtInput).should('be.visible').type(instrumentValidityStartAt);

        cy.get(el.instrumentValidityEndAtInput).type(instrumentValidityEndAt);
    }

    displayRequiredFieldsMessageError() {
        cy.get(el.snackbarAlert, { timeout: 5000 })
            .should('exist')
            .and('contain.text', 'Preencha e salve todos os campos obrigatórios em destaque antes de tramitar.');
    }

    clickTramitButton() {
        cy.get(el.tramitButton).should('be.visible').click();
    }

    clickTramitDisable() {
        cy.get(el.tramitContainer).should('be.visible').click();
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

    displayModalConfirmReturn() {
        cy.contains('Confirmar envio?')
            .closest('.v-card')
            .within(() => {
                cy.contains(
                    'A devolução deixará de ser editável e as pessoas responsáveis receberão sua notificação.'
                ).should('be.visible');

                cy.contains('button', /^Sim$/).should('be.visible').click();
            });

        // cy.contains('Confirmar envio?').should('be.visible');

        // cy.contains('A devolução deixará de ser editável e as pessoas responsáveis receberão sua notificação.').should('be.visible');

        // cy.contains('Sim').should('be.visible').click();
    }

    displayProcessReturnedSuccessMessage() {
        cy.contains('Devolução realizada')
            .closest('.v-card')
            .within(() => {
                cy.contains('O processo foi devolvido aos responsáveis!').should('be.visible');

                cy.contains('button', 'Entendi').should('be.visible').click();
            });
    }

    displayModalConfirmTramit() {
        cy.contains('Deseja realizar a tramitação?').should('be.visible');

        cy.contains(
            'As informações serão validadas e o processo será passado adiante, notificando os responsáveis.'
        ).should('be.visible');

        cy.contains('Confirmar').should('be.visible').click();
    }

    displayModalTramitedProcess() {
        cy.contains('Tramitação realizada').should('be.visible');

        cy.contains('O processo seguirá com outro setor a partir de agora.').should('be.visible');

        cy.contains('Entendi').should('be.visible').click();
    }
}

export default new FormalizationTab();
