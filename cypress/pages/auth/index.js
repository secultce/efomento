import { elements as el } from './elements';

class Login {
    accessLoginPage() {
        cy.visit('/login');
    }

    fillTwoFactorCode(code = Cypress.env('TWO_FACTOR_UNIVERSAL_CODE') || '123456', trustDevice = true) {
        cy.get(el.inputCode).should('be.visible').type(code);
        if (trustDevice) {
            cy.get(el.chkTrustDevice).check({ force: true });
        }
        cy.get(el.btnSubmitCode).should('be.visible').click();
    }

    successLogin(
        email,
        password,
        name,
        { trustDevice = true, code = Cypress.env('TWO_FACTOR_UNIVERSAL_CODE') || '123456' } = {}
    ) {
        cy.get(el.email).type(email);
        cy.get(el.password).type(password);
        cy.get(el.btnLogin).should('be.visible').click();

        cy.location('pathname', { timeout: 10000 })
            .should((pathname) => {
                expect(pathname).to.be.oneOf(['/login-code', '/editais']);
            })
            .then((pathname) => {
                if (pathname === '/login-code') {
                    this.fillTwoFactorCode(code, trustDevice);
                }
            });

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

    validateUnloggedUserRedirectsToLogin() {
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
