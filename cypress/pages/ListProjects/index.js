import { elements as el } from './elements';

class ListProjects {
    acessarPaginaDeProjetosDoFirstEdital() {
        cy.visit('/editais');
        cy.get(el.btnAcessarProjetos).not('[disabled]').first().click();
        cy.url().should('include', '/projetos');
    }

    selecionarPrimeiroItem() {
        cy.get(el.checkboxItem).not('[disabled]').first().click();
    }

    desmarcarPrimeiroItem() {
        cy.get(el.checkboxItem).not('[disabled]').first().click();
    }

    verificarBotaoConferirDesabilitado() {
        cy.get(el.btnConferirDocumentos).should('be.disabled');
    }

    verificarBotaoConferirHabilitado() {
        cy.get(el.btnConferirDocumentos).should('not.be.disabled');
    }

    verificarBotaoCriarCIDesabilitado() {
        cy.get(el.btnCriarCI).should('be.disabled');
    }

    verificarBotaoCriarCIHabilitado() {
        cy.get(el.btnCriarCI).should('not.be.disabled');
    }

    verificarBotaoAtribuirFiscalDesabilitado() {
        cy.get(el.btnAtribuirFiscal).should('be.disabled');
    }

    verificarBotaoAtribuirFiscalHabilitado() {
        cy.get(el.btnAtribuirFiscal).should('not.be.disabled');
    }

    clicarBotaoCriarCI() {
        cy.get(el.btnCriarCI).click();
    }

    verificarModalCriarCIAberto() {
        cy.get(el.modalCriarCITitulo).should('be.visible');
    }
}

export default new ListProjects();
