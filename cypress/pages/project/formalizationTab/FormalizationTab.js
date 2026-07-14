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

    fillRequiredFields(reportStatusSelected, termNumber, deliberationOption) {
        cy.get(el.asjurFinalisticProcessingDate).type('01/01/2023');

        cy.get(el.asjurProcessReceivedDate).type('10/03/2023');

        cy.get(el.processAssignedToInput).type('João da Silva');

        this.selectDropdownOption(el.reportStatusSelect, reportStatusSelected);

        cy.get(el.eparceriasCertificateDate).type('10/03/2023');

        cy.get(el.responsibleAtAsjurInput).type('Maria da Silva');

        cy.get(el.asjurProcessingDateInput).type('10/03/2023');

        cy.get(el.termNumberInput).should('be.visible').clear();

        cy.get(el.termNumberInput).should('be.visible').type(termNumber);

        cy.get(el.termSignatureSentAtInput).should('be.visible').type('10/03/2023');

        cy.get(el.termSignedAtInput).type('10/03/2023');

        cy.get(el.sentToOfficeAt).should('be.visible').type('10/03/2023');

        cy.get(el.signedByOfficeAtInput).should('be.visible').type('10/03/2023');

        cy.get(el.saccNumberInput).should('be.visible').type('123456');

        cy.get(el.cgeAtendeTicketInput).should('be.visible').type('123456');

        this.selectDropdownOption(el.deliberationSelect, deliberationOption).should('be.visible').click();

        cy.get(el.sentToChiefOfStaffAtInput).should('be.visible').type('10/03/2023');

        cy.get(el.officialGazettePublishedAtInput).should('be.visible').type('10/03/2023');

        cy.get(el.officialGazetteFileInput).should('be.visible').attachFile('test.pdf');

        cy.get(el.instrumentValidityStartAtInput).should('be.visible').type('10/03/2023');

        cy.get(el.instrumentValidityEndAtInput).type('10/03/2023');

        cy.get(el.legalOpinionDateInput).should('be.visible').type('10/03/2023');

        cy.get(el.tramitButton).should('be.visible').click();
    }

    displayRequiredFieldsMessageError() {
        cy.get(el.snackbarAlert, { timeout: 5000 })
            .should('exist')
            .and(
                'contain.text',
                'Preencha os campos obrigatórios antes de tramitar: Data de tramitação da finalística para a ASJUR, Data de recebimento do processo pela ASJUR, Processo distribuído para, Informe regularidade e inadimplência, Data da certidão, Data de tramitação na ASJUR, Responsável (Distribuido para), Número do termo, Data do envio para assinatura do termo, Data da assinatura do termo, Data de envio para Gabinete, Data de assinatura do termo pelo Gabinete, Número do SACC, Chamado CGE atende, Data de envio para Casa Civil, Data de Publicação do Diário Oficial do Estado, Data de início da vigência do instrumento, Data de término da vigência do instrumento, Data do parecer jurídico, Anexo do documento do Diário Oficial do Estado.'
            );
    }

    clickTramitDisable() {
        cy.get(el.tramitContainer).should('be.visible').click();
    }
}

export default new FormalizationTab();
