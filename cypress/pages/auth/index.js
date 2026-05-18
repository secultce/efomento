import { elements as el } from './elements';

class Login {
    acessarPaginaDeLogin() {
        cy.visit('/login');
    }

    acessarPaginaDeEditais() {
        cy.visit('/editais');
    }

    loginComSucesso(email, password, name) {
        cy.get(el.inputEmail).type(email);
        cy.get(el.inputPassword).type(password);
        cy.get(el.btnLogin).should('be.visible').click();
        cy.url().should('be.equal', `${Cypress.config('baseUrl')}/editais`);
        cy.contains(el.welcomeMessage + name).should('be.visible');
    }

    loginComSenhaIncorreta(email, password) {
        cy.get(el.inputEmail).type(email);
        cy.get(el.inputPassword).type(password);
        cy.get(el.btnLogin).should('be.visible').click();
        cy.get('p').contains(el.passwordErrorMessage).should('be.visible');
    }

    loginComEmailInvalido(email, password) {
        cy.get(el.inputEmail).type(email);
        cy.get(el.inputEmail).should('have.prop', 'validity').and('include', { valid: false });
        cy.get(el.inputPassword).type(password);
        cy.get(el.btnLogin).should('be.visible').click();
    }

    validarRedirecionamentoDeslogado() {
        cy.url().should('be.equal', `${Cypress.config('baseUrl')}/login`);
    }

    validarQueLogoutRedirecionaParaLogin() {
        cy.get(el.btnUserAvatar).click();
        cy.get(el.btnLogout).should('be.visible').click();
        cy.url().should('be.equal', `${Cypress.config('baseUrl')}/login`);
    }

    deslogar() {
        cy.get(el.btnUserAvatar).click();
        cy.get(el.btnLogout).should('be.visible').click();
        cy.url().should('be.equal', `${Cypress.config('baseUrl')}/login`);
    }
}

export default new Login();
