import { elements as el } from './emelents';

class Project {

    accessProjectPage(noticeId) {
        cy.visit(`/editais/${noticeId}/projetos`)
    }

    findByProccessNumber(processNumber){
        cy.get(el.inputFindProjectPage)
          .type(processNumber)

        cy.get(el.inputProcessNumberProjectList)
          .contains(processNumber)
          .should('be.visible')
     }

}

export default new Project()
