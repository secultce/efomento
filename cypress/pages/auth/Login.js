import { elements as el } from './elements';

class Login {
    successLogin(email, password, name) {
        cy.get(el.emailFielInput).type(email);
        cy.get(el.passwordFieldField).type(password);
        cy.get(el.btnLogin).should('be.visible').click();
        cy.url().should('be.equal', `${Cypress.config('baseUrl')}/editais`);
        cy.contains(el.welcomeMessage + name).should('be.visible');
    }

    fillEmailField(email) {
        cy.get(el.emailFielInput).should('be.visible').type(email);
    }

    fillPasswordField(password) {
        cy.get(el.passwordFieldInput).should('be.visible').type(password);
    }

    clickLoginButton() {
        cy.get(el.enterLoginButton).should('be.visible').click();
    }

    fillVerificationCode(code) {
        cy.get(el.verificationCode).should('be.visible').type(code);
    }

    checkTrustDevice() {
        cy.get(el.trustDeviceChekbox).should('be.visible').check();
    }

    clickConfirmAndEnter() {
        cy.get(el.confirmAndEnterButton).should('be.visible').click();
    }

    verifyEmailRequiredField() {
        cy.get(el.emailFielInput).should('have.prop', 'validity').and('have.property', 'valueMissing', true);
    }

    verifyPasswordRequiredField() {
        cy.get(el.passwordFieldInput).should('have.prop', 'validity').and('have.property', 'valueMissing', true);
    }

    verifyInvalidEmailFormat() {
        cy.get(el.emailFielInput).should('have.prop', 'validity').and('have.property', 'typeMismatch', true);
    }

    verifyInvalidCredentialsMessage() {
        cy.get('p').contains(el.passwordErrorMessage).should('be.visible');
    }
    verifyVerificationCodePage() {
        cy.contains('Verificação de acesso').should('be.visible');

        cy.get(el.verificationCode).should('be.visible');
    }

    verifyInvalidVerificationCodeMessage() {
        cy.get('p').contains(el.invalidVerificationCodeMessage).should('be.visible');
    }

    verifyRedirectedToNoticesPage() {
        cy.url().should('be.equal', `${Cypress.config('baseUrl')}/editais`);

        cy.get('[data-cy="table-notice-list"]').should('be.visible');
    }

    clickUserAvatar() {
        cy.get(el.btnUserAvatar).should('be.visible').click();
    }

    clickLogout() {
        cy.get(el.btnLogout).should('be.visible').click();
    }
}

export default new Login();
