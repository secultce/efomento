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
import LoginPage from '../pages/auth';

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

Cypress.Commands.add('loginByRole', (role) => {
    cy.fixture('users').then((users) => {
        if (!users[role]) {
            throw new Error(`Role "${role}" not found in users.json`);
        }
        const user = users[role];

        cy.session(
            role,
            () => {
                LoginPage.accessLoginPage();

                LoginPage.successLogin(user.valid_email, user.password, user.name);
            },
            {
                validate() {
                    cy.visit('/editais');
                    cy.contains(user.name).should('be.visible');
                },
            }
        );
    });
});
