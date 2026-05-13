import { elements as el } from './elements';

class Profile {
    acessarPaginaDePerfil() {
        cy.intercept('GET', '/profile').as('getProfile');
        cy.visit('/profile').wait('@getProfile');
    }
    alterarNomeEEmailESalvar(name, email) {
        cy.get(el.inputProfileName).clear();
        cy.get(el.inputProfileName).type(name);
        cy.get(el.inputProfileEmail).clear();
        cy.get(el.inputProfileEmail).type(email);
        cy.get(el.saveButtonProfileInformation).click();
        cy.get(el.savedMessageProfileInformation).should('be.visible');
    }

    alterarSenhaESalvar(currentPassword, newPassword) {
        cy.get(el.inputCurrentPassword).type(currentPassword);
        cy.get(el.inputPassword).type(newPassword);
        cy.get(el.inputPasswordConfirmation).type(currentPassword);
        cy.get(el.saveButtonProfileInformation).click();
        cy.get(el.savedMessageProfileInformation).should('be.visible');
    }

    retornarErroDeSenhas(currentPassord, newPassword, passwordConfirmation) {
        cy.get(el.inputCurrentPassword).type(currentPassord);
        cy.get(el.inputPassword).type(newPassword);
        cy.get(el.inputPasswordConfirmation).type(passwordConfirmation);
        cy.get(el.saveButtonUpdatePassword).click();
        cy.get(el.errorMessagePasswordConfirmation).should('be.visible');
    }
}

export default new Profile();
