import LoginWorkflow from '../../../support/workflows/LoginWorkflow.js';
import Login from '../../../pages/auth/Login.js';
// import Notice from '../../../pages/notice/NoticePage.js';

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
        it('should display the verification code screen after valid credentials', function () {});

        it('should not authenticate with an invalid verification code', function () {});

        it('should not authenticate with an expired verification code', function () {});

        it('should authenticate with a valid verification code', function () {});
    });

    describe('Trusted Device', () => {
        it('should authenticate and trust the device when the option is selected', () => {});
    });

    describe('Successful authentication', () => {
        it('should redirect to the notices page after successful authentication', () => {});
    });

    describe('Session', () => {
        it('should redirect an unauthenticated user to the login page', () => {});

        it('should redirect to the login page after logout', () => {});
    });
});
