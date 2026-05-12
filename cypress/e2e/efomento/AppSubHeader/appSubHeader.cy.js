import AppSubHeader from '../../../pages/appSubHeader';

describe('AppSubHeader — Botão Voltar', () => {
    let data;

    before(() => {
        cy.fixture('users').then((users) => {
            data = users;
        });
    });

    afterEach(() => {
        cy.window().then((win) => {
            try {
                Object.defineProperty(win.document, 'referrer', {
                    get: () => '',
                    configurable: true,
                });
            } catch (_) {
                /* intentional: ignora erro se já definido */
            }
        });
    });

    it('Acesso direto sem login: após autenticar, botão voltar retorna para /editais', () => {
        AppSubHeader.acessarPaginaSemLogin('/editais/48/projetos');

        AppSubHeader.realizarLogin(data.valid_email, data.password);
        AppSubHeader.verificarUrl('/editais/48/projetos');

        AppSubHeader.simularReferrerDeLogin();

        AppSubHeader.clicarBotaoVoltar();
        AppSubHeader.verificarUrl('/editais');
    });

    it('Navegação normal: botão voltar retorna para a página anterior via histórico', () => {
        cy.visit('/login');
        AppSubHeader.realizarLogin(data.valid_email, data.password);
        AppSubHeader.verificarUrl('/editais');

        cy.visit('/editais/48/projetos');
        AppSubHeader.verificarUrl('/editais/48/projetos');

        cy.visit('/editais/53/projetos');
        AppSubHeader.verificarUrl('/editais/53/projetos');

        AppSubHeader.clicarBotaoVoltar();
        AppSubHeader.verificarUrl('/editais/48/projetos');
    });
});
