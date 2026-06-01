import Profile from '../../../pages/profile';
import Login from '../../../pages/auth';
import CredentialsReset from '../../../pages/auth/credentialsReset.js';

describe('Página de Perfil', () => {
    beforeEach(() => {
        cy.fixture('users').as('user');

        cy.get('@user').then((user) => {
            Login.accessLoginPage();
            Login.successLogin(user.valid_email, user.password, user.name);
        });

        Profile.accessProfilePage();
    });

    it('should update profile information and save', function () {
        Profile.alterNameAndEmailAndSave(this.user.updated_name, this.user.updated_email);
        CredentialsReset.resetNameAndEmail(this.user.valid_email, this.user.name);
    });

    it('should update password and save', function () {
        Profile.alterPasswordAndSave(this.user.password, this.user.new_password);
        CredentialsReset.resetPassword(this.user.new_password, this.user.password);
        Login.logout();
    });

    it('should display error message when passwords do not match during password update', function () {
        Profile.returnPasswordUpdateErrors(this.user.password, this.user.new_password, this.user.incorrect_password);
        Login.logout();
    });
});
