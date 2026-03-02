/**
 * cy.login(email, password)
 *
 * Realiza login via formulário e armazena a sessão em cache com cy.session().
 * Depois do primeiro uso, o login não é refeito enquanto a sessão for válida.
 */
Cypress.Commands.add('login', (email = 'test@example.com', password = 'password') => {
    cy.session(
        [email, password],
        () => {
            cy.visit('/login')
            cy.get('#email').type(email)
            cy.get('#password').type(password)
            cy.get('button[type="submit"]').click()
            cy.url().should('include', '/editais')
        },
        {
            validate() {
                cy.visit('/editais')
                cy.url().should('include', '/editais')
            },
        },
    )
})

/**
 * cy.logout()
 *
 * Abre o menu do usuário no AppHeader e clica em "Sair".
 */
Cypress.Commands.add('logout', () => {
    cy.contains('button', /TU|Test User/i).click()
    cy.contains('Sair').click()
    cy.url().should('include', '/login')
})
