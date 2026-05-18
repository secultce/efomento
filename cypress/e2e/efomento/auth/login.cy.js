import Login from '../../../pages/auth';
import Notice from '../../../pages/notice';

describe('Login', () => {
    let data;

    before(() => {
        cy.fixture('users').then((tData) => {
            data = tData;
        });
    });

    it('Validate it is possible to login with valid credentials', () => {
        Login.accessLoginPage();
        Login.successLogin(data.valid_email, data.password, data.name);
    });

    it('Validate it is not possible to login with invalid password', () => {
        Login.accessLoginPage();
        Login.loginWithInvalidPassword(data.valid_email, data.incorrect_password);
    });

    it('Validate it is possible to login with invalid email', () => {
        Login.accessLoginPage();
        Login.loginWithInvalidEmail(data.invalid_email, data.password);
    });

    it('Validate that accessing notice page when logged out redirects to login page', () => {
        Notice.acessarPaginaDeEditais();
        Login.validateUnlogedUserRedirectsToLogin();
    });

    it('Validate click on logout button redirects to login page', () => {
        Login.accessLoginPage();
        Login.successLogin(data.valid_email, data.password, data.name);
        Login.validateLogoutRedirectsToLoginPage();
    });
});
