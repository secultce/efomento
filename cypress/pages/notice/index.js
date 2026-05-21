import { elements as el } from './elements';

class Notice {
    // Navigation and Page Access
    visitPage() {
        cy.visit('/editais');
    }

    verifyPageLoaded() {
        cy.get(el.noticeListTable, { timeout: 10000 }).should('be.visible');
    }

    // Dashboard
    verifyDashboardCardsAreVisible() {
        cy.get(el.dashboardCard).should('have.length.at.least', 1);
    }

    verifyAllDashboardMetrics() {
        const expectedMetrics = [
            'Todos os editais disponíveis',
            'Editais com processos em andamento',
            'Processos Finalizados',
        ];

        expectedMetrics.forEach((metric) => {
            cy.get(el.dashboardCard).contains(metric).should('be.visible');
        });
    }

    // User Info
    verifyLoggedUserDisplayedInHeader(username) {
        cy.get(el.userAvatarButton).should('be.visible').and('contain', username);
    }

    verifyWelcomeMessageDisplaysUsername(username) {
        cy.get(el.welcomeMessage).should('be.visible').and('contain', username);
    }

    // Form Navigation
    openIdentificationDataForm() {
        cy.get(el.identificationDataFormButton).should('be.visible').first().click();
    }

    // Form Filling
    fillIdentificationDataForm(formData) {
        const { noticeNup, instrumentType, totalAmount, noticeManager, managerEmail, quotaNumber } = formData;

        // Fill NUP field
        cy.get(el.noticeNupInput).should('be.visible').type(noticeNup);

        // Select instrument type
        cy.get(el.instrumentTypeSelect).should('be.visible').click();

        cy.contains('.v-list-item', instrumentType).should('be.visible').click();

        // Fill amount
        cy.get(el.totalAmountInput).should('be.visible').type(totalAmount);

        // Fill manager name
        cy.get(el.noticeManagerInput).should('be.visible').type(noticeManager);

        // Fill email
        cy.get(el.managerEmailInput).should('be.visible').type(managerEmail);

        // Fill quota number
        cy.get(el.quotaNumberInput).should('be.visible').type(quotaNumber);

        // Submit form
        cy.get(el.submitFormButton).should('be.visible').click();
    }

    verifySuccessMessage() {
        // Verify success message
        cy.get('.v-snackbar', { timeout: 20000 }).contains('Número do processo salvo com sucesso').should('be.visible');
    }

    /**
     * Select an option from a dropdown implemented with v-list
     * @param {string} selector - selector for the dropdown trigger
     * @param {string|number} value - visible text of the option to choose
     */
    selectDropdownOption(selector, value) {
        const valueStr = value.toString();
        cy.get(selector).should('be.visible').click();

        cy.contains('.v-list-item', valueStr).should('be.visible').click();
    }

    // Search and Filtering
    searchNoticeByTitle(title) {
        cy.get(el.findSpecificNoticeInput).should('be.visible').type(title);

        cy.get(el.noticeListTable).within(() => {
            cy.contains(title).should('be.visible');
        });
    }

    searchNoticeByNup(nup) {
        cy.get(el.findSpecificNoticeInput).should('be.visible').type(nup);

        cy.get(el.noticeNupNoticesList).should('be.visible').and('contain', nup);
    }

    filterByProcessStatus(status) {
        this.selectDropdownOption(el.filterProcessStatusSelect, status);

        // Verify the table shows items matching the selected status
        cy.get(el.noticeListTable).should('be.visible').and('contain', status);
    }

    filterByInstrumentType(instrumentType) {
        this.selectDropdownOption(el.filterInstrumentTypeSelect, instrumentType);

        // Verify the table lists the expected instrument type
        cy.get(el.noticeListTable).should('be.visible').and('contain', instrumentType);
    }

    // Detail View
    goToNoticeDetailsPage(nup) {
        cy.get(el.noticeNupNoticesList)
            .contains(nup)
            .closest('tr')
            .find(el.accessNoticeInformationButton)
            .should('be.visible')
            .click();

        cy.url({ timeout: 10000 }).should('match', /\/editais\/\d+\/projetos$/);
    }

    clickShowAllInformationButton() {
        cy.get(el.showAllInformationButton).should('be.visible').click();
    }

    verifyDetailViewElements() {
        const detailElements = [
            el.noticeTitleDetail,
            el.noticeNupDetail,
            el.instrumentTypeDetail,
            el.noticeManagerDetail,
            el.budgetAllocationRequestDateDetail,
            el.totalAmountDetail,
            el.valueInFullDetail,
            el.managerEmailDetail,
            el.processNumberCreditorDetail,
            el.quotaNumberDetail,
            el.processNumberBudgetAllocationDetail,
            el.budgetAllocationCreditorDateDetail,
        ];

        detailElements.forEach((element) => {
            cy.get(element, { timeout: 5000 }).should('be.visible');
        });
    }

    // Pagination
    changeItemsPerPage(quantity) {
        const quantityStr = quantity.toString();
        this.selectDropdownOption(el.quantityPerPageSelect, quantityStr);

        cy.get(`${el.noticeListTable} tbody tr`, { timeout: 5000 }).should('have.length', quantity);
    }

    goToPage(pageNumber) {
        const pageStr = pageNumber.toString();

        cy.get(el.paginationNumber).contains(pageStr).should('be.visible').click();

        cy.get(el.paginationNumber)
            .contains(pageStr)
            .parent()
            .should('have.css', 'background-color', 'rgb(255, 193, 7)');
    }
}

export default new Notice();
