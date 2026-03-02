describe('Autenticação', () => {
    describe('Login', () => {
        beforeEach(() => {
            cy.visit('/login')
        })

        it('exibe o formulário de login', () => {
            cy.get('#email').should('be.visible')
            cy.get('#password').should('be.visible')
            cy.get('button[type="submit"]').should('contain.text', 'Log in')
        })

        it('realiza login com credenciais válidas e redireciona para /editais', () => {
            cy.get('#email').type('test@example.com')
            cy.get('#password').type('password')
            cy.get('button[type="submit"]').click()

            cy.url().should('include', '/editais')
        })

        it('exibe erro ao informar senha incorreta', () => {
            cy.get('#email').type('test@example.com')
            cy.get('#password').type('senha-errada')
            cy.get('button[type="submit"]').click()

            cy.url().should('include', '/login')
            cy.contains('These credentials do not match our records').should('be.visible')
        })

        it('exibe erro ao deixar campos obrigatórios em branco', () => {
            cy.get('button[type="submit"]').click()

            // Validação nativa HTML5 impede o envio — continua na página de login
            cy.url().should('include', '/login')
        })
    })

    describe('Proteção de rotas', () => {
        it('redireciona para /login ao acessar /editais sem autenticação', () => {
            cy.visit('/editais')
            cy.url().should('include', '/login')
        })

        it('redireciona para /login ao acessar /projects sem autenticação', () => {
            cy.visit('/projects')
            cy.url().should('include', '/login')
        })

        it('redireciona para /login ao acessar /profile sem autenticação', () => {
            cy.visit('/profile')
            cy.url().should('include', '/login')
        })
    })

    describe('Logout', () => {
        beforeEach(() => {
            cy.login()
            cy.visit('/editais')
        })

        it('encerra a sessão e redireciona para /login', () => {
            // Abre o menu do usuário no AppHeader
            cy.get('.v-app-bar').contains('Test User').click()
            cy.contains('Sair').click()

            cy.url().should('include', '/login')
        })

        it('após logout, acesso a /editais redireciona para /login', () => {
            cy.get('.v-app-bar').contains('Test User').click()
            cy.contains('Sair').click()

            cy.visit('/editais')
            cy.url().should('include', '/login')
        })
    })

    describe('Sessão autenticada', () => {
        beforeEach(() => {
            cy.login()
            cy.visit('/editais')
        })

        it('exibe o nome do usuário logado no header', () => {
            cy.get('.v-app-bar').should('contain.text', 'Test User')
        })

        it('exibe o link e-fomento no header', () => {
            cy.get('.v-app-bar').contains('e-fomento').should('be.visible')
        })

        it('exibe o botão Editais na navegação', () => {
            cy.get('.v-app-bar').contains('Editais').should('be.visible')
        })
    })
})
