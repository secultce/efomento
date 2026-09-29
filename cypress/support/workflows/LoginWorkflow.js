import Login from '../../pages/auth/Login.js';

class LoginWorflow {
    goToLoginPage() {
        cy.visit('/login');
    }

    login(email, password, code) {
        Login.fillEmailField(email);
        Login.fillPasswordField(password);
        Login.fillVerificationCode(code);
        Login.clickLoginButton();
    }

    completeTwoFactorAuthentication(code, trustDevice = false) {
        Login.fillVerificationCode(code);

        if (trustDevice) {
            Login.checkTrustDevice();
        }

        Login.clickConfirmAndEnter();
    }

    logout() {
        Login.clickUserAvatar();
        Login.clickLogout();
    }
}

export default new LoginWorflow();
