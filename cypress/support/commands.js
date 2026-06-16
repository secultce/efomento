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

Cypress.Commands.add('setRoles', () => {
    cy.visit('/grupos');

    cy.get('#app').then(async ($app) => {
        const dataPage = $app.attr('data-page');
        const pageJson = JSON.parse(dataPage);

        // Evaluate the number of users
        const userCount = pageJson.props.users.find((user) => user.name === 'Lara Pimentel');

        cy.log(userCount.id);
        cy.visit(`/add-user/${userCount.id}/super_admin`);

        return pageJson.props.roles;
    });
});
