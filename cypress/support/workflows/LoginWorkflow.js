import Login from '../../pages/auth/Login.js';

class LoginWorflow {
    goToLoginPage() {
        cy.visit('/login');
    }

    login(email, password) {
        Login.fillEmailField(email);
        Login.fillPasswordField(password);
        Login.clickLoginButton();
    }

    completeTwoFactorAuthentication(code, trustDevice = false) {
        Login.fillVerificationCode(code);

        if (trustDevice) {
            Login.checkTrustDevice();
        }

        Login.clickConfirmAndEnter();
    }

    dis;

    logout() {
        Login.clickUserAvatar();
        Login.clickLogout();
    }
}

export default new LoginWorflow();
