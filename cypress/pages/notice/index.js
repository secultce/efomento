import { elements as el} from "./elements"

class Notice {

    acessarPaginaDeEditais(){
        cy.visit('/editais')//
        cy.request({
          method: 'GET',
          url:'/editais',
          failOnStatusCode: false
        }).then((response) => {
          expect(response.status).to.eq(200);
        });
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
          .first()
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

    buscarEditalPorNumeroDoProcessoMae(numeroProcessoMae) {
        cy.get(el.inputFindEspecificNotice)
          .should('be.visible')
          .type(numeroProcessoMae)
        cy.get(el.motherProcessNumber)
          .should('be.have',numeroProcessoMae)
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

    visualizarNomeDoUsuarioLogadoNoHeader(nomeDoUsuarioLogado) {
        cy.get(el.buttonUserAvatar)
          .should('be.visible')
          .and('be.have', nomeDoUsuarioLogado)
    }

    visualizarMensagemDeBoasVindasComNomeDoUsuario(nomeDoUsuarioLogado, numeroProcessoMae) {
        cy.get(el.welcomeMessage)
          .should('be.visible')
          .and('be.have', nomeDoUsuarioLogado)
    }

    visualizarInformaçõesDoProcesso(numeroProcessoMae) {
        cy.get(el.tableNoticeList)
          .should()

        cy.get(el.motherProcessNumber)
          .invoke('val')
          .should()

        cy.get(el.buttonAccessIdentificationdata)
          .should('be.visible')
          .first()
          .click()

       cy.url()
         .should('match', /\/editais\/\d+\/projetos$/)

       cy.get(el.motherProcessNumberProjectPage)
         .contains(numeroProcessoMae)
         .should('be.visible')


    }

}

export default new Notice()
