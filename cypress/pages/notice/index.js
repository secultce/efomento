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

        cy.get(el.tableNoticeList)
          .should('be.visible')
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
        .contains('Número do processo salvo com sucesso')
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
        cy.get(el.buttonAccessIdentificationdata)
          .should('be.visible')
          .first()
          .click()

        cy.url()
         .should('match', /\/editais\/\d+\/projetos$/)

        cy.get(el.motherProcessNumberProcessInformation)
         .contains(numeroProcessoMae)
         .should('be.visible')

        cy.get(el.btnShowAllInformation)
          .should('be.visible')
          .click()

        cy.get(el.noticeNameProcessInformation)
          .should('be.visible')

        cy.get(el.motherProcessNumberProcessInformation)
          .should('be.visible')

        cy.get(el.intrumentTypeProccessInformation)
          .should('be.visible')

        cy.get(el.budgetAlocationDateProcessInformation)
          .should('be.visible')

        cy.get(el.totalAmountProcessInformation)
          .should('be.visible')

        cy.get(el.valueInFullProcessInformation)
          .should('be.visible')

        cy.get(el.managerEmailProcessInformation)
          .should('be.visible')

        cy.get(el.processNumberCreditorProcessInformation)
          .should('be.visible')

        cy.get(el.quotaNumberProcessInformation)
          .should('be.visible')

        cy.get(el.processNumberBudgetAlocationProcessInformation)
          .should('be.visible')

        cy.get(el.budgetAlocationCreditorRegister)
          .should('be.visible')

    }

    alterarQuantidadeDeExibicaoListaDeEditais(quantidadePorPágina) {
        cy.get(el.selectQuantityListNotices)
          .should('be.visible')
          .click()

        cy.contains('.v-list-item', quantidadePorPágina)
          .should('be.visible')
          .click()

        cy.get(`${el.tableNoticeList} tbody tr` )
          .should('have.length', quantidadePorPágina)
    }

    alterarPaginaDaListaDeEditais() {
        cy.get(el.pageNumberNoticesList)
          .contains('2')
          .should('be.visible')
          .click()

        cy.get(el.pageNumberNoticesList)
          .contains('2')
          .parent()
          .should('have.css', 'background-color','rgb(255, 193, 7)')
    }

}

export default new Notice()
