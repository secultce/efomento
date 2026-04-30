import Login from '../../../pages/auth'
import ListProjects from '../../../pages/ListProjects'

let data

describe('Lista de Projetos — controle de botões por seleção de checkbox', () => {

    before(() => {
        cy.fixture('users').then((tData) => {
            data = tData
        })
    })

    beforeEach(() => {
        Login.acessarPaginaDeLogin()
        Login.loginComSucesso(data.valid_email, data.password, data.name)
        ListProjects.acessarPaginaDeProjetosDoFirstEdital()
    })

    context('Sem nenhum projeto selecionado', () => {

        it('"Conferir documentos" fica desabilitado', () => {
            ListProjects.verificarBotaoConferirDesabilitado()
        })

        it('"Criar CI" fica desabilitado', () => {
            ListProjects.verificarBotaoCriarCIDesabilitado()
        })

        it('"Atribuir Fiscal" fica desabilitado', () => {
            ListProjects.verificarBotaoAtribuirFiscalDesabilitado()
        })

    })

    context('Com um projeto selecionado', () => {

        beforeEach(() => {
            ListProjects.selecionarPrimeiroItem()
        })

        it('"Conferir documentos" fica habilitado', () => {
            ListProjects.verificarBotaoConferirHabilitado()
        })

        it('"Criar CI" fica habilitado (requer permissão ci.create ou super_admin)', () => {
            ListProjects.verificarBotaoCriarCIHabilitado()
        })

        it('"Atribuir Fiscal" fica habilitado (requer permissão opening.assign_supervisor ou super_admin)', () => {
            ListProjects.verificarBotaoAtribuirFiscalHabilitado()
        })

    })

    context('Ao desmarcar todos os projetos', () => {

        it('"Conferir documentos" volta a ficar desabilitado', () => {
            ListProjects.selecionarPrimeiroItem()
            ListProjects.desmarcarPrimeiroItem()
            ListProjects.verificarBotaoConferirDesabilitado()
        })

    })

    context('Criar CI', () => {

        it('Ao selecionar um projeto e clicar em "Criar CI", o modal de criação é exibido', () => {
            ListProjects.selecionarPrimeiroItem()
            ListProjects.clicarBotaoCriarCI()
            ListProjects.verificarModalCriarCIAberto()
        })

    })

})
