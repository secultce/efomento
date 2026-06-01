import { elements as el, TIMEOUTS } from './elements';
import { elements as noticeEl } from '../notice/elements';

class Project {
    accessProjectPage(noticeId) {
        cy.visit(`/editais/${noticeId}/projetos`);
        cy.get(noticeEl.appContainer, { timeout: TIMEOUTS.LONG }).should('be.visible');
    }

    displayProjectList() {
        cy.get(el.projectList, { timeout: TIMEOUTS.DEFAULT }).should('be.visible');
    }

    getProjectData() {
        const projects = [];

        return cy.get(el.rowTableProjectList).then(($rows) => {
            Cypress.$($rows).each((_, row) => {
                const agentName = Cypress.$(row).find(el.agentNameProjectList).text().trim();

                const projectNup = Cypress.$(row).find(el.projectNupProjectList).text().trim();

                projects.push({
                    agentName,
                    projectNup,
                });
            });

            const project = Math.floor(Math.random() * projects.length);

            return projects[project];
        });
    }

    findProjectByProjectNup(projectNup) {
        cy.get(el.findProjectPageInput, { timeout: TIMEOUTS.DEFAULT }).should('be.visible');

        cy.get(el.findProjectPageInput).type(projectNup, { delay: 50 });

        cy.get(el.projectList).within(() => {
            cy.get(el.projectNupProjectList, { timeout: TIMEOUTS.SEARCH })
                .closest(el.projectNupProjectList)
                .contains(projectNup)
                .should('be.visible')
                .first();
        });
        cy.get(el.projectNupProjectList, { timeout: TIMEOUTS.SEARCH })
            .closest(el.projectNupProjectList)
            .should('be.visible')
            .contains(projectNup)
            .should('be.visible')
            .first();
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
}

export default new Project();
