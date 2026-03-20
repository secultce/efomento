// ***********************************************
// This example commands.js shows you how to
// create various custom commands and overwrite
// existing commands.
//
// For more comprehensive examples of custom
// commands please read more here:
// https://on.cypress.io/custom-commands
// ***********************************************
//
//
// -- This is a parent command --
// Cypress.Commands.add('login', (email, password) => { ... })
//
//
// -- This is a child command --
// Cypress.Commands.add('drag', { prevSubject: 'element'}, (subject, options) => { ... })
//
//
// -- This is a dual command --
// Cypress.Commands.add('dismiss', { prevSubject: 'optional'}, (subject, options) => { ... })
//
//
// -- This will overwrite an existing command --
// Cypress.Commands.overwrite('visit', (originalFn, url, options) => { ... })

Cypress.Commands.add('login', (email, password, name) => {
    cy.visit('/login');
    cy.get('#email').type(email);
    cy.get('#password').type(password);
    cy.get('button').contains('Entrar').click();
    cy.get('p')
      .contains('Bem-vindo ao seu espaço, ' + name)
      .should('be.visible')
});

Cypress.Commands.add('logout', () => {
    cy.visit('/editais');
    cy.get('span>.v-avatar')
      .click()
    cy.contains('Sair')
      .should('be.visible')
      .click()
    cy.url()
      .should('be.equal', `${Cypress.config("baseUrl")}/`)
});

Cypress.Commands.add('resetEmail', (updated_email,password, email, updated_name, name) => {
    cy.login(updated_email, password,updated_name)
    cy.visit('/profile')
    cy.get('#name')
      .clear()
      .type(name)
    cy.get('#email')
      .clear()
      .type(email)
    cy.contains('Save')
      .click()
    cy.get('p')
      .contains('Saved')
      .should('be.visible')
    cy.logout()
});

Cypress.Commands.add('resetPassword', (email, password, updated_password,name) => {
    cy.login(email,updated_password, name)
    cy.visit('/profile')
    cy.get('#current_password')
      .type(updated_password)
    cy.get('#password')
      .type(password)
    cy.get('#password_confirmation')
      .type(password)
    cy.get(':nth-child(2) > .max-w-xl > .mt-6 > .flex > .inline-flex')
      .click()
    cy.get('p')
      .contains('Saved')
      .should('be.visible')
    cy.logout()
});
