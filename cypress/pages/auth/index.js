import { elements as el } from './elements';

class Login {
    accessLoginPage() {
        cy.visit('/login');
    }

    successLogin(email, password, name) {
        cy.get(el.email).type(email);
        cy.get(el.password).type(password);
        cy.get(el.btnLogin).should('be.visible').click();
        cy.url().should('be.equal', `${Cypress.config('baseUrl')}/editais`);
        cy.contains(el.welcomeMessage + name).should('be.visible');
    }

    loginWithInvalidPassword(email, password) {
        cy.get(el.email).type(email);
        cy.get(el.password).type(password);
        cy.get(el.btnLogin).should('be.visible').click();
        cy.get('p').contains(el.passwordErrorMessage).should('be.visible');
    }

    loginWithInvalidEmail(email, password) {
        cy.get(el.email).type(email);
        cy.get(el.email).should('have.prop', 'validity').and('include', { valid: false });
        cy.get(el.password).type(password);
        cy.get(el.btnLogin).should('be.visible').click();
    }

    validateUnlogedUserRedirectsToLogin() {
        cy.url().should('be.equal', `${Cypress.config('baseUrl')}/login`);
    }

    validateLogoutRedirectsToLoginPage() {
        cy.get(el.btnUserAvatar).click();
        cy.get(el.btnLogout).should('be.visible').click();
        cy.url().should('be.equal', `${Cypress.config('baseUrl')}/login`);
    }

    logout() {
        cy.get(el.btnUserAvatar).click();
        cy.get(el.btnLogout).should('be.visible').click();
        cy.url().should('be.equal', `${Cypress.config('baseUrl')}/login`);
    }
}

export default new Login();
