import Login from '../../pages/auth/Login.js';

class LoginWorkflow {
    goToLoginPage() {
        cy.visit('/login');
    }

    login(email, password) {
        Login.fillEmailField(email);
        Login.fillPasswordField(password);
        Login.clickLoginButton();

        cy.location('pathname', { timeout: 10000 }).should('eq', '/two-factor-challenge');
    }

    completeTwoFactorAuthentication({ trustDevice = false } = {}) {
        const code = Cypress.env('TWO_FACTOR_UNIVERSAL_CODE');

        expect(code, 'TWO_FACTOR_UNIVERSAL_CODE').to.be.a('string').and.not.be.empty;

        Login.fillVerificationCode(code);

        if (trustDevice) {
            Login.checkTrustDevice();
        }

        Login.clickConfirmAndEnter();
    }

    loginWithTwoFactorAndTrustDevice(email, password) {
        this.login(email, password);
        this.completeTwoFactorAuthentication();
    }

    logout() {
        Login.clickUserAvatar();
        Login.clickLogout();
    }
}

export default new LoginWorkflow();
