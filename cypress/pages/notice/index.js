import { elements as el } from './elements';

class Notice {
    accessNoticePage() {
        cy.request({
            method: 'GET',
            url: '/editais',
            failOnStatusCode: false,
        }).then((response) => {
            expect(response.status).to.eq(200);
        });
    }

    showTableNoticeList() {
        cy.get(el.tableNoticeList).should('be.visible');
    }

    viewDashboard() {
        cy.get(el.dashboardCard).contains('Todos os editais disponíveis').should('be.visible');

        cy.get(el.dashboardCard).contains('Editais com processos em andamento').should('be.visible');

        cy.get(el.dashboardCard).contains('Processos Finalizados').should('be.visible');
    }

    accessIdentificationDataForm() {
        cy.get(el.identificationDataFormButton).should('be.visible').first().click();
    }
    fillNoticeIdentificationDataForm(
        noticeNup,
        instrumentType,
        totalAmountNotice,
        noticeManagerAccompaniment,
        managerEmail,
        quotaNumber
    ) {
        cy.get(el.noticeNupIdentificationDataFormInput).type(noticeNup);

        cy.get(el.instrumentTypeIdentificationDataFormSelect)
            .should('be.visible')

            .click();

        cy.contains('.v-list-item', instrumentType).click();

        cy.get(el.totalAmountNoticeIdentificationDataFormInput).type(totalAmountNotice);

        cy.get(el.noticeManagerAccompanimentIdentificationDataFormInput).type(noticeManagerAccompaniment);

        cy.get(el.managerEmailIdentificationDataFormInput).type(managerEmail);

        cy.get(el.quotaNumberIdentificationDataFormInput).type(quotaNumber);

        cy.get(el.addDataIdentificationDataFormButton).click();

        cy.get(el.snackAlert).contains('Número do processo salvo com sucesso').should('be.visible');
    }

    findNoticeByTitle(noticeTitle) {
        cy.get(el.findSpecificNoticeInput).should('be.visible').type(noticeTitle);
        cy.get('.v-data-table__tr > .v-data-table-column--align-start').contains(noticeTitle).should('be.visible');
    }

    findNoticeByNoticeNup(noticeNup) {
        cy.get(el.findSpecificNoticeInput).should('be.visible').type(noticeNup);
        cy.get(el.noticeNupNoticesList).should('contain', noticeNup).should('be.visible');
    }

    filterNoticeByProcessStatus(processStatus) {
        cy.get(el.processStatusBadge).should('be.visible').type(processStatus);
        cy.get(el.processStatusBadge).should('contain', processStatus).and('be.visible');
    }

    filterNoticeByInstrumentType(instrumentType) {
        cy.get(el.instrumentTypeIdentificationDataFormSelect).should('be.visible').click();
        cy.contains('.v-list-item', instrumentType).click();
        cy.get(el.instrumentTypeNoticesList).should('be.visible').and('contain', instrumentType);
    }

    showLoggedUsernameOnHeader(nameLoggedUser) {
        cy.get(el.userAvatarButton).should('be.visible').and('contain', nameLoggedUser);
    }

    showWelcomeMessageWithNameLoggedUser(nameloggedUser) {
        cy.get(el.welcomeMessage).should('be.visible').and('contain', nameloggedUser);
    }

    showAllInformationAboutProcess(noticeNup) {
        cy.get(el.noticeNupNoticesList)
            .contains(noticeNup)
            .closest('tr')
            .find(el.accessNoticeInformationButton)
            .click();

        cy.url().should('match', /\/editais\/\d+\/projetos$/);

        cy.get(el.noticeNupShowAllInformation).contains(noticeNup).should('be.visible');

        cy.get(el.showAllInformationButton).should('be.visible').click();

        cy.get(el.noticeTitleShowAllInformation).should('be.visible');

        cy.get(el.noticeNupShowAllInformation).should('be.visible');

        cy.get(el.instrumentTypeShowAllInformation).should('be.visible');

        cy.get(el.noticeManagerShowAllInformation).should('be.visible');

        cy.get(el.budgetAllocationRequestDateShowAllInformation).should('be.visible');

        cy.get(el.totalAmountShowAllInformation).should('be.visible');

        cy.get(el.valueInFullShowAllInformation).should('be.visible');

        cy.get(el.managerEmailShowAllInformation).should('be.visible');

        cy.get(el.processNumberCreditorShowAllInformation).should('be.visible');

        cy.get(el.quotaNumberShowAllInformation).should('be.visible');

        cy.get(el.processNumberBudgetAlocationShowAllInformation).should('be.visible');

        cy.get(el.budgetAllocationCreditorRegisterDateShowAllInformation).should('be.visible');
    }

    modifyQuantityPerPageOnNoticesList(quantityPerPage) {
        cy.get(el.quantityListNoticesSelect).should('be.visible').click();

        cy.contains('.v-list-item', quantityPerPage).should('be.visible').click();

        cy.get(`${el.noticeListTable} tbody tr`).should('have.length', quantityPerPage);
    }

    changePageOnNoticesList() {
        cy.get(el.pageNumberNoticesList).contains('2').should('be.visible').click();
        cy.get(el.pageNumberNoticesList)
            .contains('2')
            .parent()
            .should('have.css', 'background-color', 'rgb(255, 193, 7)');
    }
}

export default new Notice();
