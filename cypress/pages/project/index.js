import { elements as el, TIMEOUTS } from './elements';
import { elements as noticeEl } from '../notice/elements';

class Project {

    accessProjectPage(noticeId) {
        cy.visit(`/editais/${noticeId}/projetos`)
        cy.get(noticeEl.appContainer, { timeout: TIMEOUTS.LONG })
            .should('be.visible')
    }

    findByProcessNumber(processNumber){
        cy.get(el.inputFindProjectPage, { timeout: TIMEOUTS.DEFAULT })
            .should('be.visible')
            .find('input')
            .should('be.visible')
            .clear()
            .type(processNumber, { delay: 50 })

        cy.get(el.processNumberProjectList, { timeout: TIMEOUTS.SEARCH })
            .should('be.visible')
            .contains(processNumber)
            .should('be.visible')
            .first()
    }

    findByNonExistentProcessNumber() {
        cy.get(el.inputFindProjectPage, { timeout: TIMEOUTS.DEFAULT })
            .should('be.visible')
            .find('input')
            .should('be.visible')
            .clear()
            .type('123456789012345678901', { delay: 50 })

        cy.get(el.messageNoDataAvailable, { timeout: TIMEOUTS.SEARCH })
            .should('be.visible')
            .should('contain', 'No data available')
    }

}

export default new Project()
