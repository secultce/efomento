import LoginWorkflow from '../../../support/workflows/LoginWorkflow.js';
import Login from '../../../pages/auth/Login.js';

describe('Login', () => {
    beforeEach(() => {
        cy.fixture('users').as('user');
    });

    describe('Credentials', () => {
        it('should display required field validation when email is empty', function () {
            // Arrange
            const password = this.user.fomentation.password;
            LoginWorkflow.goToLoginPage();

            // Act
            Login.fillPasswordField(password);
            Login.clickLoginButton();

            // Assert
            Login.verifyEmailRequiredField();
        });

        it('should display required field validation when password is empty', function () {
            // Arrange
            const email = this.user.fomentation.email;
            LoginWorkflow.goToLoginPage();

            // Act
            Login.fillEmailField(email);
            Login.clickLoginButton();

            // Assert
            Login.verifyPasswordRequiredField();
        });

        it('should not authenticate with an invalid email', function () {
            // Arrange
            const invalidEmail = this.user.fomentation.invalidEmail;
            const password = this.user.fomentation.password;
            LoginWorkflow.goToLoginPage();

            // Act
            Login.fillEmailField(invalidEmail);
            Login.fillPasswordField(password);
            Login.clickLoginButton();

            // Assert
            Login.verifyInvalidEmailFormat();
        });

        it('should not authenticate with an invalid password', function () {
            // Arrange
            const email = this.user.fomentation.email;
            const invalidPassword = this.user.fomentation.invalidPassword;

            LoginWorkflow.goToLoginPage();

            // Act
            Login.fillEmailField(email);
            Login.fillPasswordField(invalidPassword);
            Login.clickLoginButton();

            // Assert
            Login.verifyInvalidCredentialsMessage();
        });
    });

    describe('Two-factor authentication', () => {
        it('should display the verification code screen after valid credentials', function () {
            // Arrange
            const user = this.user.fomentation;
            LoginWorkflow.goToLoginPage();

            // Act
            LoginWorkflow.login(user.email, user.password);

            // Assert
            Login.verifyVerificationCodePage();
        });

        it('should not authenticate with an invalid verification code', function () {
            // Arrange
            const user = this.user.fomentation;
            const invalidCode = '000000';

            LoginWorkflow.goToLoginPage();
            LoginWorkflow.login(user.email, user.password);

            // Act
            LoginWorkflow.completeTwoFactorAuthentication({ code: invalidCode });

            // Assert
            Login.verifyInvalidVerificationCodeMessage();
        });

        it('should authenticate with a valid verification code', function () {
            // Arrange
            const user = this.user.fomentation;
            const validCode = '123456';

            LoginWorkflow.goToLoginPage();
            LoginWorkflow.login(user.email, user.password);

            // Act
            LoginWorkflow.completeTwoFactorAuthentication({ code: validCode });

            // Assert
            Login.verifyRedirectedToNoticesPage();
        });
    });

    describe('Trusted Device', () => {
        it('should authenticate and trust the device when the option is selected', function () {
            // Arrange
            const user = this.user.fomentation;

            LoginWorkflow.goToLoginPage();

            // Act
            LoginWorkflow.login(user.email, user.password);
            LoginWorkflow.completeTwoFactorAuthentication({
                trustDevice: true,
            });

            // Assert
            Login.verifyRedirectedToNoticesPage();
        });
    });

    describe('Session', () => {
        it('should redirect an unauthenticated user to the login page', () => {
            // Arrange
            // Não realizar login.

            // Act
            cy.visit('/editais');

            // Assert
            cy.location('pathname').should('eq', '/login');
        });

        it('should redirect to the login page after logout', function () {
            // Arrange
            const user = this.user.fomentation;

            LoginWorkflow.goToLoginPage();
            LoginWorkflow.login(user.email, user.password);
            LoginWorkflow.completeTwoFactorAuthentication();

            // Act
            LoginWorkflow.logout();

            // Assert
            cy.location('pathname').should('eq', '/login');
        });
    });
});
