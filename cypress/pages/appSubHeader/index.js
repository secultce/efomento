import { elements as el } from './elements'

class AppSubHeader {

    acessarPaginaSemLogin(url) {
        cy.visit(url)
        cy.url().should('include', '/login')
    }

    realizarLogin(email, password) {
        cy.get(el.inputEmail).type(email)
        cy.get(el.inputPassword).type(password)
        cy.get(el.btnLogin).should('be.visible').click()
    }

    clicarBotaoVoltar() {
        cy.get(el.btnVoltar).should('be.visible').click()
    }

    simularReferrerDeLogin() {
        cy.window().then((win) => {
            Object.defineProperty(win.document, 'referrer', {
                get: () => `${Cypress.config('baseUrl')}/login`,
                configurable: true,
            })
        })
    }

    verificarUrl(path) {
        cy.url().should('eq', `${Cypress.config('baseUrl')}${path}`)
    }
}

export default new AppSubHeader()
