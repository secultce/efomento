import { elements as el } from './elements';

class Profile {
    accessProfilePage() {
        cy.intercept('GET', '/profile').as('getProfile');
        cy.visit('/profile').wait('@getProfile');
    }
    alterNameAndEmailAndSave(name, email) {
        cy.get(el.profileNameInput).clear();
        cy.get(el.profileNameInput).type(name);
        cy.get(el.profileEmailInput).clear();
        cy.get(el.profileEmailInput).type(email);
        cy.get(el.saveProfileInformationButton).click();
        cy.get(el.savedMessageProfileInformation).should('be.visible');
    }

    alterPasswordAndSave(defaultPassword, newPassword) {
        cy.get(el.currentPasswordInput).type(defaultPassword);
        cy.get(el.passwordInput).type(newPassword);
        cy.get(el.passwordConfirmationInput).type(newPassword);
        cy.get(el.saveUpdatePasswordButton).click();
        cy.get(el.savedMessageProfileInformation).should('be.visible');
    }

    returnPasswordUpdateErrors(currentPassword, newPassword, passwordConfirmation) {
        cy.get(el.currentPasswordInput).type(currentPassword);
        cy.get(el.passwordInput).type(newPassword);
        cy.get(el.passwordConfirmationInput).type(passwordConfirmation);
        cy.get(el.saveUpdatePasswordButton).click();
        cy.get(el.errorMessagePasswordConfirmation).should('be.visible');
    }
}

export default new Profile();
