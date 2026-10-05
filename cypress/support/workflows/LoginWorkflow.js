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

    completeTwoFactorAuthentication(options = {}) {
        const { code = Cypress.env('TWO_FACTOR_UNIVERSAL_CODE'), trustDevice = false } =
            typeof options === 'string' ? { code: options } : options;

        expect(code, 'verification code').to.be.a('string').and.not.be.empty;

        Login.fillVerificationCode(code);

        if (trustDevice) {
            Login.checkTrustDevice();
        }

        Login.clickConfirmAndEnter();
    }

    loginWithTwoFactorAndTrustDevice(email, password) {
        this.login(email, password);
        this.completeTwoFactorAuthentication({ trustDevice: true });
    }

    logout() {
        Login.clickUserAvatar();
        Login.clickLogout();
    }
}

export default new LoginWorkflow();
