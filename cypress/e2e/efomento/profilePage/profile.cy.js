describe('Página de Perfil', () => {
    
  let data;
  beforeEach(() => {
    cy.fixture('users').then((tData) => {
      data = tData;
    });
  })


  it('Garante que é possível atualizar nome e e-mail do perfil do usuário e salvar', () => {
    cy.login(data.valid_email, data.password,data.name)
    cy.intercept('GET','/profile').as('getProfile')
    cy.visit('/profile')
      .wait('@getProfile')
    cy.get('#name')
      .clear()
      .type(data.updated_name)
    cy.get('#email')
      .clear()
      .type(data.updated_email)
    cy.contains('Save')
      .click()
    cy.get('p')
      .contains('Saved')
      .should('be.visible')    
    cy.logout()
    cy.login(data.updated_email, data.password, data.updated_name)
    cy.logout()   
    cy.resetEmail(data.updated_email,data.password,data.valid_email,data.updated_name, data.name);
  })

  it('Garante que é possível alterar a senha e salvar ', () => {
    // cy.resetPassword(data.valid_email, data.password, data.updated_password,data.name)
    cy.login(data.valid_email,data.password,data.name)
    cy.intercept('GET','/profile').as('getProfile')
    cy.visit('/profile')
      .wait('@getProfile')
    cy.get('#current_password')
      .type(data.password)
    cy.get('#password')
      .type(data.updated_password)
    cy.get('#password_confirmation')
      .type(data.updated_password)
    cy.get(':nth-child(2) > .max-w-xl > .mt-6 > .flex > .inline-flex')
      .click()
    cy.get('p')
      .contains('Saved')
      .should('be.visible')    
    cy.logout()
    cy.login(data.valid_email,data.updated_password,data.name)
    cy.logout()
    cy.resetPassword(data.valid_email, data.password, data.updated_password, data.name)
  })

   it('Garante que é exibida mensagem de erro quando as senha não conferem durante alteração da senha', () => {
    cy.login(data.valid_email, data.password,data.name)
    cy.intercept('GET','/profile').as('getProfile')
    cy.visit('/profile')
    .wait('@getProfile')
    cy.get('#current_password')
      .type(data.password)
    cy.get('#password')
      .type(data.updated_password)
    cy.get('#password_confirmation')
      .type(data.incorrect_password)
    cy.get(':nth-child(2) > .max-w-xl > .mt-6 > .flex > .inline-flex')
      .click()
    cy.get('p')
      .contains('A confirmação para o campo password não coincide.')
      .should('be.visible')
  })
    
});