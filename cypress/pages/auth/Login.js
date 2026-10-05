import { elements as el } from './elements';

class Login {
    accessLoginPage() {
        cy.visit('/login');
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
        cy.get(el.verificationCode).should('be.visible').find('input').type(code);
    }

    checkTrustDevice() {
        cy.get(el.trustDeviceCheckbox).should('be.visible').find('input').check({ force: true });
    }

    clickConfirmAndEnter() {
        cy.get(el.confirmAndEnterButton).should('be.visible').click();
    }

    fillTwoFactorCode(code = Cypress.env('TWO_FACTOR_UNIVERSAL_CODE') || '123456', trustDevice = true) {
        this.fillVerificationCode(code);

        if (trustDevice) {
            this.checkTrustDevice();
        }
        this.clickConfirmAndEnter();
    }

    successLogin(email, password, name) {
        this.fillEmailField(email);
        this.fillPasswordField(password);
        this.clickLoginButton();
        this.fillVerificationCode(Cypress.env('TWO_FACTOR_UNIVERSAL_CODE') || '123456');
        this.checkTrustDevice();
        this.clickConfirmAndEnter();
        this.verifyRedirectedToNoticesPage();
    }

    loginComSucesso(email, password, name) {
        this.successLogin(email, password, name);
    }

    acessarPaginaDeLogin() {
        this.accessLoginPage();
    }

    loginWithInvalidPassword(email, password) {
        this.fillEmailField(email);
        this.fillPasswordField(password);
        this.clickLoginButton();
        this.verifyInvalidCredentialsMessage();
    }

    loginWithInvalidEmail(email, password) {
        this.fillEmailField(email);
        this.verifyInvalidEmailFormat();
        this.fillPasswordField(password);
        this.clickLoginButton();
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
        cy.get(el.confirmAndEnterButton).should('be.visible');
        cy.get(el.trustDeviceCheckbox).should('be.visible');
        cy.get(el.resentCodeButton).should('be.visible');
        cy.get(el.backToLoginButton).should('be.visible');
    }

    verifyInvalidVerificationCodeMessage() {
        cy.contains(el.invalidVerificationCodeMessage).should('be.visible');
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

    logout() {
        this.clickUserAvatar();
        this.clickLogout();
        this.validateLogoutRedirectsToLoginPage();
    }

    validateUnloggedUserRedirectsToLogin() {
        cy.url().should('be.equal', `${Cypress.config('baseUrl')}/login`);
    }

    validateLogoutRedirectsToLoginPage() {
        cy.url().should('be.equal', `${Cypress.config('baseUrl')}/login`);
    }
}

export default new Login();
