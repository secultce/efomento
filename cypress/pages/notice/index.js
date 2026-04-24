import { elements as el} from "./elements"

class Notice {

    acessarPaginaDeEditais(){
        cy.visit('/editais')
    }

    visualizarDashboard() {
        cy.get('[data-cy=cardDashboard]')
            .contains('Todos os editais disponíveis')
            .should('be.visible')

        cy.get('[data-cy=cardDashboard]')
            .contains('Editais com processos em andamento')
            .should('be.visible')

        cy.get('[data-cy=cardDashboard]')
            .contains('Processos Finalizados')
            .should('be.visible')
    }

    acessarFomularioDadosDeIdentificacao() {
        cy.get(el.buttonIdentificationData)
          .should('be.visible')
          .click()
    }
    preencherDadosDeIdentificacaoDoEdital(nup, tipoDeInstrumento, valorTotalEdital, nomeGestorEdital, emailGestorEdital, numeroDeParcelas) {
        cy.get(el.inputProcessNumber)
          .type(nup)

        cy.get(el.selectInstrumentTypeIdentificationData)
          .should('be.visible')
          .click()

        cy.contains('.v-list-item',tipoDeInstrumento)
          .click()
        //   .should('be.selected')

        cy.get(el.inputTotalValueNotice)
          .type(valorTotalEdital)

        cy.get(el.inputNoticeManagerAccompaniment)
          .type(nomeGestorEdital)

        cy.get(el.inputManagerEmail)
          .type(emailGestorEdital)

        cy.get(el.inputQuotaNumber)
          .type(numeroDeParcelas)

        cy.get(el.buttonAddData)
          .click()

        cy.get(el.snackAlert)
        .contains('O valor indicado para o campo nup já se encontra registrado')
        //   .contains('Número do processo salvo com sucesso')
          .should('be.visible')

    }

    buscarEditalPorTítulo(tituloDoEdital) {
        cy.get(el.inputFindEspecificNotice)
          .should('be.visible')
          .type(tituloDoEdital)
        cy.get('.v-data-table__tr > .v-data-table-column--align-start')
          .contains(tituloDoEdital)
          .should('be.visible')
    }

    filtrarEditalPorStatusDoProcesso(statusDoProcesso) {
        cy.get(el.selectFilterProcessStatus)
          .should('be.visible')
          .type(statusDoProcesso)
        cy.get(el.badgeProcessStatus)
          .should('be.have',statusDoProcesso)
          .and('be.visible')
    }

    filtrarEditalPorTipoDeInstrumento(tipoDeInstrumento) {
        cy.get(el.selectFilterInstrumentType)
          .should('be.visible')
          .click()

        cy.contains('.v-list-item',tipoDeInstrumento)
          .click()

        cy.get(el.tbodyInstrumentTypeNoticesList)
    }

}

export default new Notice()
