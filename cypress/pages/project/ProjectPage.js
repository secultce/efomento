import { elements as el, TIMEOUTS } from './elements';
import { elements as noticeEl } from '../notice/elements';
import Notice from '../notice/NoticePage.js';

class Project {
    accessProjectPage(noticeId) {
        cy.visit(`/editais/${noticeId}/projetos`);
        cy.get(noticeEl.appContainer, { timeout: TIMEOUTS.LONG }).should('be.visible');
    }

    displayProjectList() {
        cy.get(el.projectList, { timeout: TIMEOUTS.DEFAULT }).should('be.visible');
    }

    goToProjectDetailsPage(projectNup) {
        const expectedNup = Notice.normalizeNup(projectNup);

        cy.get(el.projectNupProjectList)
            .filter((index, element) => {
                const currentNup = Notice.normalizeNup(Cypress.$(element).text());

                return currentNup === expectedNup;
            })
            .first()
            .should('exist')
            .closest(el.rowTableProjectList)
            .find(el.openProjectOpeningTabButton)
            .should('be.visible')
            .click();

        cy.url({ timeout: 10000 }).should('match', /\/editais\/\d+\/projetos\/\d+(?:\?tab=.*)?$/);
    }

    getProjectData() {
        return cy
            .get(el.projectList)
            .find(el.rowTableProjectList)
            .filter((_, row) => {
                const agentName = Cypress.$(row).find(el.agentNameProjectList).text().trim();
                const projectNup = this.normalizeNup(Cypress.$(row).find(el.projectNupProjectList).text());

                return agentName.length > 0 && projectNup.length > 0;
            })
            .should('have.length.greaterThan', 0)
            .first()
            .then(($row) => ({
                agentName: $row.find(el.agentNameProjectList).text().trim(),
                projectNup: $row.find(el.projectNupProjectList).text().trim(),
            }));
    }

    findProjectByProjectNup(projectNup) {
        expect(projectNup, 'NUP utilizado na busca').to.exist;

        const expectedNup = Notice.normalizeNup(projectNup);
        const projectInput = `${el.findProjectPageInput} input`;

        cy.get(projectInput, {
            timeout: TIMEOUTS.DEFAULT,
        })
            .should('be.visible')
            .clear();

        cy.get(projectInput).type(projectNup);

        cy.get(el.projectList).within(() => {
            cy.get(el.projectNupProjectList, {
                timeout: TIMEOUTS.SEARCH,
            })
                .filter((index, element) => {
                    const currentNup = Notice.normalizeNup(Cypress.$(element).text());

                    return currentNup === expectedNup;
                })
                .should('be.visible');
        });
    }

    findProjectByNonExistentProjectNup() {
        cy.get(`${el.findProjectPageInput} input`, {
            timeout: TIMEOUTS.DEFAULT,
        })
            .should('be.visible')
            .clear();

        cy.get(`${el.findProjectPageInput} input`).type('123456789012345678901', {
            delay: 50,
        });
    }

    verifySearchResultByInexistentNup() {
        cy.get(el.noDataAvailableMessage, { timeout: TIMEOUTS.SEARCH })
            .should('be.visible')
            .should('contain', 'Não foi encontrado dados para sua pesquisa');
    }

    findProjectByAgentName(agentName) {
        expect(agentName, 'Nome do agente utilizado na busca').to.be.a('string').and.not.be.empty;

        cy.intercept('GET', '**/editais/*/projetos*', (request) => {
            const url = new URL(request.url);

            if (/\/editais\/\d+\/projetos$/.test(url.pathname) && url.searchParams.get('search') === agentName) {
                request.alias = 'searchProjectsByAgent';
            }
        });

        cy.get(`${el.findProjectPageInput} input`, { timeout: TIMEOUTS.DEFAULT }).should('be.visible').clear();

        cy.get(`${el.findProjectPageInput} input`).type(agentName, { parseSpecialCharSequences: false });

        return cy.wait('@searchProjectsByAgent').its('response.statusCode').should('eq', 200);
    }

    getProjectRowByNup(projectNup) {
        const expectedNup = Notice.normalizeNup(projectNup);

        return cy
            .get(el.projectNupProjectList, {
                timeout: TIMEOUTS.SEARCH,
            })
            .filter((index, element) => {
                const currentNup = Notice.normalizeNup(Cypress.$(element).text());

                return currentNup === expectedNup;
            })
            .first()
            .should('exist')
            .closest(el.rowTableProjectList);
    }

    clickFilterPhase(phase) {
        cy.get(el.filterProjectPhaseCard).contains(phase).should('be.visible').click();
    }

    validateFilterPhase(phase) {
        cy.get(el.projectPhase).contains(phase).should('be.visible');
    }

    selectProject(projectNup) {
        const expectedNup = Notice.normalizeNup(projectNup);

        cy.get(el.rowTableProjectList)
            .filter((index, row) => {
                const currentNup = Notice.normalizeNup(Cypress.$(row).find(el.projectNupProjectList).text());

                return currentNup === expectedNup;
            })
            .should('have.length', 1)
            .within(() => {
                cy.get(el.checkboxProjectList).should('be.visible').click();
            });
    }

    clickCreateDocument(documentType) {
        const { createButton, selectors } = documentType;

        cy.get(selectors.create, { timeout: TIMEOUTS.LONG }).should('be.visible').contains(createButton).click();
    }

    clickEditDocument(documentType) {
        const { editButton, selectors } = documentType;

        cy.get(selectors.edit, { timeout: TIMEOUTS.LONG }).should('be.visible').contains(editButton).click();
    }

    saveDocument() {
        cy.get(el.saveDocumentButton, { timeout: TIMEOUTS.LONG })
            .should('be.visible')
            .and('not.be.disabled')
            .should(($button) => {
                expect($button.css('pointer-events')).to.not.equal('none');
            })
            .click();
    }

    clickCancelDocumentButton() {
        cy.get(el.cancelDocumentButton).click();
    }

    validateDocumentContent(expectedText) {
        cy.window()
            .its('tinymce.activeEditor')
            .should('exist')
            .then((editor) => {
                cy.wrap(null).should(() => {
                    expect(editor.getContent({ format: 'text' })).to.contain(expectedText);
                });
            });
    }

    validateDocumentCreated(projectNup, chip) {
        expect(projectNup, 'O NUP do projeto deve ser informado para validar o documento').to.be.a('string').and.not.be
            .empty;

        const expectedNup = Notice.normalizeNup(projectNup);

        expect(expectedNup, 'NUP normalizado').to.not.be.empty;

        cy.get(el.rowTableProjectList, {
            timeout: TIMEOUTS.LONG,
        })
            .should('be.visible')
            .should('not.have.class', 'loading')
            .filter((index, row) => {
                const text = Cypress.$(row).find(el.projectNupProjectList).text();
                const currentNup = Notice.normalizeNup(text);

                return currentNup === expectedNup;
            })
            .should('have.length', 1)
            .within(() => {
                cy.get(el.chipProjectList).contains(chip).should('be.visible');
            });
    }

    fillDocument(text) {
        cy.window({ timeout: 10000 }).then((win) => {
            cy.wrap(null, { log: false }).should(() => {
                expect(win.tinymce).to.exist;
                expect(win.tinymce.activeEditor).to.exist;
                expect(win.tinymce.activeEditor.initialized).to.be.true;
            });
        });

        cy.window().then((win) => {
            const editor = win.tinymce.activeEditor;
            editor.setContent(`<p>${text}</p>`);
            editor.focus();

            editor.selection.select(editor.getBody(), true);

            editor.execCommand('Bold');
            editor.fire('change');
            editor.save();
        });
    }

    verifySuccessMessageSaveDocument() {
        // Verify success message
        cy.get(el.successAlert, { timeout: 5000 }).contains('Documento salvo com sucesso!').should('be.visible');
    }

    validateCurrentPhase(projectNup, expectedPhase) {
        const expectedNup = Notice.normalizeNup(projectNup);

        cy.get(el.projectNupProjectList)
            .filter((index, element) => {
                const currentNup = Notice.normalizeNup(Cypress.$(element).text());

                return currentNup === expectedNup;
            })
            .first()
            .closest(el.rowTableProjectList)
            .within(() => {
                cy.get(el.projectPhase).should('be.visible').and('have.text', expectedPhase);
            });
    }

    verifySearchResultByNup(nup) {
        const expectedNup = this.normalizeNup(nup);

        cy.get(el.projectList).within(() => {
            cy.get(el.projectNupProjectList).should(($elements) => {
                expect($elements, 'projetos encontrados').to.have.length(1);

                const actualNup = this.normalizeNup($elements.eq(0).text());

                expect(actualNup, 'NUP do projeto encontrado').to.equal(expectedNup);
            });
        });
    }

    verifySearchResultByAgent(agentName, projectNup) {
        const normalizeName = (value) => value.normalize('NFD').replace(/\p{M}/gu, '').toLowerCase();
        const expectedWords = [...new Set((agentName.match(/\p{L}[\p{L}\p{M}]*/gu) ?? []).map(normalizeName))];
        const expectedNup = this.normalizeNup(projectNup);

        expect(expectedWords, 'Palavras do nome utilizadas na busca').to.have.length.greaterThan(0);

        cy.get(el.projectList)
            .find(el.rowTableProjectList)
            .should(($rows) => {
                expect($rows, 'Projetos encontrados').to.have.length.greaterThan(0);

                const returnedNups = [];

                $rows.each((_, row) => {
                    const actualAgent = normalizeName(Cypress.$(row).find(el.agentNameProjectList).text());

                    expectedWords.forEach((word) => {
                        expect(actualAgent, `Agente do projeto encontrado deve conter "${word}"`).to.include(word);
                    });
                    returnedNups.push(this.normalizeNup(Cypress.$(row).find(el.projectNupProjectList).text()));
                });

                expect(returnedNups, 'NUP do projeto capturado antes da busca').to.include(expectedNup);
            });
    }

    normalizeNup(value) {
        return String(value).replace(/\D/g, '');
    }
}

export default new Project();
