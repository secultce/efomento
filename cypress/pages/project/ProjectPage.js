import { elements as el, TIMEOUTS } from './elements';
import { elements as noticeEl } from '../notice/elements';
import Notice from '../notice/index.js';

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

        cy.get(el.rowTableProjectList)
            .contains(el.projectNupProjectList, expectedNup)
            .closest(el.rowTableProjectList)
            .find(el.openProjectOpeningTabButton)
            .click();

        cy.url({ timeout: 10000 }).should('match', /\/editais\/\d+\/projetos\/\d+$/);
    }

    getProjectData() {
        const projects = [];

        return cy.get(el.rowTableProjectList).then(($rows) => {
            Cypress.$($rows).each((_, row) => {
                const agentName = Cypress.$(row).find(el.agentNameProjectList).text().trim();

                const projectNup = Cypress.$(row).find(el.projectNupProjectList).text().trim();

                projects.push({
                    agentName,
                    projectNup: projectNup && projectNup !== '-' ? projectNup : null,
                });
            });

            const project = Math.floor(Math.random() * projects.length);

            return projects[project];
        });
    }

    findProjectByProjectNup(projectNup) {
        if (projectNup) {
            cy.log('projectNup', projectNup);

            cy.get(`${el.findProjectPageInput} input`, { timeout: TIMEOUTS.DEFAULT }).should('be.visible').clear();

            cy.get(`${el.findProjectPageInput} input`).type(projectNup);

            cy.get(el.projectList).within(() => {
                cy.get(el.projectNupProjectList, { timeout: TIMEOUTS.SEARCH })
                    .contains(projectNup)
                    .should('be.visible');
            });
        } else {
            this.getProjectData().then((data) => {
                const projectNup = data.projectNup;

                // cy.log('projectNup', projectNup);

                cy.get(`${el.findProjectPageInput} input`, { timeout: TIMEOUTS.DEFAULT }).should('be.visible').clear();

                cy.get(`${el.findProjectPageInput} input`).type(projectNup);

                cy.get(el.projectList).within(() => {
                    cy.get(el.projectNupProjectList, { timeout: TIMEOUTS.SEARCH })
                        .contains(projectNup)
                        .should('be.visible');
                });
            });
        }
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

        cy.get(el.messageNoDataAvailable, { timeout: TIMEOUTS.SEARCH })
            .should('be.visible')
            .should('contain', 'No data available');
    }

    findProjectByAgentName() {
        this.getProjectData().then((data) => {
            const agentName = data.agentName;

            cy.log('agentName', agentName);

            cy.get(`${el.findProjectPageInput} input`, { timeout: TIMEOUTS.DEFAULT }).should('be.visible').clear();

            cy.get(`${el.findProjectPageInput} input`).type(agentName);

            cy.get(el.projectList).within(() => {
                cy.get(el.agentNameProjectList, { timeout: TIMEOUTS.SEARCH }).contains(agentName).should('be.visible');
            });
        });
    }

    clickFilterFormalizationPhase() {
        cy.get(el.filterProjectPhaseCard).contains('Formalização').should('be.visible').click();
    }

    validateFilterFormalizationPhase() {
        cy.get(el.projectPhase).contains('Formalização').should('be.visible');
    }

    selectProject() {
        cy.get(el.rowTableProjectList).within(() => {
            cy.get(el.checkboxProjectList).click();
        });
    }

    clickCreateExecutionTerm() {
        cy.get(el.createDocumentButon)
            .should('be.visible')
            .and('not.be.disabled')
            .contains('Criar termo de execução cultural (TC)')
            .click();
    }

    saveExecutionTerm() {
        cy.get(el.saveDocumentButton).click();
    }

    validateExecutionTermContent() {
        cy.window().then((win) => {
            const editor = win.tinymce.activeEditor;

            expect(editor.getContent()).to.contain('My new text');
        });
    }

    validateExecutionTermCreated() {
        cy.get(el.rowTableProjectList).within(() => {
            cy.get(el.chipProjectList).should('be.visible').contains('TC');
        });
    }

    fillExecutionTerm(text) {
        cy.window()
            .its('tinymce.activeEditor')
            .should('exist')
            .should((editor) => {
                expect(editor.initialized).to.be.true;
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
        cy.get(el.successAlert, { timeout: 20000 }).contains('Documento salvo com sucesso!').should('be.visible');
    }
}

export default new Project();
