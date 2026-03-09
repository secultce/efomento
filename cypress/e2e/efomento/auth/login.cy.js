// import users from '..'

describe('Login', () => {

  let data;

  before(() => {
    cy.fixture('users').then((tData) => {
      data = tData;
    })
  })

  it('Login com sucesso', () => {
    cy.visit('/login')
    cy.get('#email')
      .type(data.valid_email)
    cy.get('#password')
      .type(data.password)
    cy.get('button').contains('Entrar')
      .should('be.visible')
      .click()
    cy.url()
      .should('be.equal', `${Cypress.config("baseUrl")}/editais`)  
    cy.get('p')
      .contains('Bem-vindo ao seu espaço,')
      .should('be.visible')

  })

  it.only('Login com senha incorreta',() => {
    cy.visit('/login')
    cy.get('#email')
      .type(data.valid_email)
    cy.get('#password')
      .type(data.incorret_password)
    cy.get('button').contains('Entrar')
      .should('be.visible')
      .click()
    cy.get('p')
      .contains('These credentials do not match our records.')
      .should('be.visible')
  })

  it('Login com email inválido', () => {
    cy.visit('/login')
    cy.get('#email')
      .type(data.invalid_email)
      .should('have.prop', 'validity')
      .and('include', { valid: false})
    cy.get('#password')
      .type(data.password)
    cy.get('button').contains('Entrar')
      .should('be.visible')
      .click()
  })

  it('Valida que acesso à rota /editais deslogado redireciona para /login', () => {
    cy.visit('/editais')
    cy.url()
      .should('be.equal', `${Cypress.config("baseUrl")}/login`)  
  })

  it('Valida que ao clicar no botão de logout redireciona para /login', () => {
    cy.login(data.valid_email,data.password);
    cy.get('span>.v-avatar')
      .click()
    cy.contains('Sair')
      .should('be.visible')
      .click()
    cy.url()
      .should('be.equal', `${Cypress.config("baseUrl")}/`)
  })
})