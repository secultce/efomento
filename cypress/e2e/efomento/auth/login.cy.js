import Login from '../../../pages/auth';
import Notice from '../../../pages/notice';

describe('Login', () => {
    beforeEach(() => {
        cy.fixture('users').as('user');
        Login.accessLoginPage();
    });

    describe('Positive Tests', () => {
        it('Validate it is possible to login with valid credentials', function () {
            Login.successLogin(this.user.valid_email, this.user.password, this.user.name);
        });

        it('Validate that accessing notice page when logged out redirects to login page', () => {
            Notice.acessarPaginaDeEditais();
            Login.validateUnloggedUserRedirectsToLogin();
        });

        it('Validate click on logout button redirects to login page', function () {
            Login.successLogin(this.user.valid_email, this.user.password, this.user.name);
            Login.validateLogoutRedirectsToLoginPage();
        });
    });

    describe('Negative Tests', () => {
        it('Validate it is not possible to login with invalid password', function () {
            Login.loginWithInvalidPassword(this.user.valid_email, this.user.incorrect_password);
        });

        it('Validate it is not possible to login with invalid email', function () {
            Login.loginWithInvalidEmail(this.user.invalid_email, this.user.password);
        });
    });
});
