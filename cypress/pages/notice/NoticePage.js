import { elements as el } from './elements';

class Notice {
    visitPage() {
        cy.visit('/editais');
    }

    verifyPageLoaded() {
        cy.get(el.noticeListTable, { timeout: 10000 }).should('be.visible');
    }

    verifyDashboardCardsAreVisible() {
        cy.get(el.dashboardCard).should('have.length.at.least', 1);
    }

    verifyAllDashboardMetrics() {
        const expectedMetrics = [
            'Editais pendentes para abertura de processo',
            'Editais com processos em andamento',
            'Processos Formalizados',
        ];

        expectedMetrics.forEach((metric) => {
            cy.get(el.dashboardCard).contains(metric).should('be.visible');
        });
    }

    verifyLoggedUserDisplayedInHeader(username) {
        cy.get(el.userAvatarButton).should('be.visible').and('contain', username);
    }

    verifyWelcomeMessageDisplaysUsername(username) {
        cy.get(el.welcomeMessage).should('be.visible').and('contain', username);
    }

    verifyIdentificationDataFormIsVisible() {
        cy.get(el.noticeNupInput).should('be.visible');
        cy.get(el.instrumentTypeSelect).should('be.visible');
        cy.get(el.totalAmountInput).should('be.visible');
        cy.get(el.noticeManagerInput).should('be.visible');
        cy.get(el.managerEmailInput).should('be.visible');
        cy.get(el.quotaNumberInput).should('be.visible');
        cy.get(el.publicPolicySelect).should('be.visible');
        cy.get(el.budgeAllocationNupInput).should('be.visible');
        cy.get(el.budgetAllocationRequestDateInput).should('be.visible');
        cy.get(el.creditorRegistrationNup).should('be.visible');
        cy.get(el.creditorRegistratioRequestDate).should('be.visible');
        cy.get(el.closeIdentificationDataButton).should('be.visible');
        cy.get(el.submitFormButton).should('be.visible');
    }

    openIdentificationDataForm() {
        cy.get(el.identificationDataFormButton).should('be.visible').first().click();
    }

    fillIdentificationDataForm(formData) {
        const { noticeNup, instrumentType, totalAmount, accompanimentManager, managerEmail, quotaNumber } = formData;

        cy.get(el.noticeNupInput).should('be.visible').type(noticeNup);
        this.selectDropdownOption(el.instrumentTypeSelect, instrumentType);
        cy.get(el.totalAmountInput).should('be.visible').type(totalAmount);
        cy.get(el.noticeManagerInput).should('be.visible').type(accompanimentManager);
        cy.get(el.managerEmailInput).should('be.visible').type(managerEmail);
        cy.get(el.quotaNumberInput).should('be.visible').type(quotaNumber);
    }

    submitIdentificationDataForm() {
        cy.get(el.submitFormButton).should('be.visible').click();
    }

    verifyNoticeNupRequiredFieldError() {
        cy.get(el.noticeNupInput).closest('.v-input').contains('Campo obrigatório').should('be.visible');
    }

    verifySuccessMessageIdentificationDataForm() {
        cy.get(el.successAlert, { timeout: 20000 })
            .contains('Número do processo salvo com sucesso')
            .should('be.visible');
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

    searchNoticeByTitle(title) {
        cy.get(el.findSpecificNoticeInput).should('be.visible').type(title);
    }

    verifyNoticeWithTitleIsDisplayed(title) {
        cy.get(el.noticeListTable).within(() => {
            cy.get(el.noticeTitleNoticesList).contains(title).should('be.visible');
        });
    }

    searchNoticeByNup(nup) {
        const expectedNup = this.normalizeNup(nup);

        cy.get(el.findSpecificNoticeInput).should('be.visible').type(expectedNup);
    }

    validateResultSearchByNup(nup) {
        const expectedNup = this.normalizeNup(nup);

        cy.get(el.noticeListTable).within(() => {
            cy.get(el.noticeNupNoticesList)
                .invoke('text')
                .then((text) => {
                    const formattedNup = this.normalizeNup(text);

                    expect(formattedNup).to.equal(expectedNup);
                });
        });
    }

    validateResultSearchByTitle(noticeTitle) {
        cy.get(el.noticeListTable).within(() => {
            cy.get(el.noticeTitleNoticesList).contains(noticeTitle).should('be.visible');
        });
    }

    clearNoticeSearch() {
        cy.get(`${el.findSpecificNoticeInput} input`).clear();
    }

    getTotalNotices() {
        return cy
            .get(el.noticeTotalCount)
            .invoke('text')
            .then((text) => Number(text.trim()));
    }

    validateNoticeListAfterClearingSearch(expectedTotal, expectedRows = 10) {
        this.getTotalNotices().should('eq', expectedTotal);

        cy.get(`${el.noticeListTable} tbody tr`).should('have.length', expectedRows);
    }

    filterByProcessStatus(status) {
        this.selectDropdownOption(el.filterProcessStatusSelect, status);
    }

    verifyNoticesAreFilteredByStatus(status) {
        cy.get(el.noticeListTable).should('be.visible').and('contain', status);
    }

    filterByInstrumentType(instrumentType) {
        this.selectDropdownOption(el.filterInstrumentTypeSelect, instrumentType);
    }

    verifyNoticesAreFilteredByInstrumentType(instrumentType) {
        cy.get(el.noticeListTable).should('be.visible').and('contain', instrumentType);
    }

    goToNoticeDetailsPage(nup) {
        const expectedNup = this.normalizeNup(nup);

        cy.get(el.noticeTableRow)
            .filter((_, row) => {
                const currentNup = this.normalizeNup(Cypress.$(row).find(el.noticeNupNoticesList).text());

                return currentNup === expectedNup;
            })
            .first()
            .find(el.accessNoticeInformationButton)
            .click();
    }

    verifyNoticeDetailsPageIsDisplayed() {
        cy.url({ timeout: 10000 }).should('match', /\/editais\/\d+\/projetos$/);
    }

    verifyDetailViewElements() {
        const detailElements = [
            el.noticeTitleDetail,
            el.noticeNupDetail,
            el.instrumentTypeDetail,
            el.accompanimentManagerDetail,
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

    verifyIdentificationData(notice) {
        cy.get(el.noticeTitleDetail).should('contain', notice.title);

        cy.get(el.noticeNupDetail)
            .invoke('text')
            .then((displayedNup) => {
                expect(this.normalizeNup(displayedNup)).to.equal(this.normalizeNup(notice.noticeNup));
            });

        cy.get(el.instrumentTypeDetail).should('contain', notice.instrumentType);
    }

    displayCorrectNupInDetailView(nup) {
        const formatedNup = this.normalizeNup(nup);
        cy.get('[data-cy=notice-nup-show-all-information]').should('be.visible').and('contain', formatedNup);
    }

    changeItemsPerPage(quantity) {
        const quantityStr = quantity.toString();
        this.selectDropdownOption(el.quantityPerPageSelect, quantityStr);
    }

    validateNoticesPerPage(noticesPerPage) {
        cy.get(`${el.noticeListTable} tbody tr`, { timeout: 5000 }).should('have.length', noticesPerPage);
    }

    goToPage(pageNumber) {
        const pageStr = pageNumber.toString();

        cy.get(el.paginationNumber).contains(pageStr).should('be.visible').click();
    }

    verifyPageIsActive(pageNumber) {
        const pageStr = pageNumber.toString();

        cy.get(el.paginationNumber)
            .contains(pageStr)
            .parent()
            .should('have.css', 'background-color', 'rgb(255, 193, 7)');
    }

    normalizeNup(value) {
        return String(value).replace(/\D/g, '');
    }

    updateDataAboutProcess(newAccompanimentManager, newManagerEmail) {
        const newNoticeManager = String(newAccompanimentManager);

        cy.get(el.accompanimentManagerDetail).should('be.visible').children().eq(1).click();

        cy.get(el.noticeEditTextField).find('input').should('be.visible').clear();

        cy.get(el.noticeEditTextField).find('input').should('be.visible').type(newNoticeManager);

        cy.get(el.managerEmailDetail).children().eq(1).click();

        cy.get(el.noticeEditTextField).find('input').should('be.visible').clear();

        cy.get(el.noticeEditTextField).find('input').should('be.visible').type(newManagerEmail);

        cy.get(el.updateDataButton).click({ force: true });
    }

    verifySuccessMessageUpdateNoticeData() {
        cy.get(el.successAlert, { timeout: 20000 }).contains('Dados atualizados com sucesso').should('be.visible');
    }

    verifyUpdatedDataAboutProcess(newaccompanimentManager, newManagerEmail) {
        cy.reload();

        this.clickShowAllInformationButton();

        cy.get(el.accompanimentManagerDetail)
            .children()
            .eq(1)
            .invoke('text')
            .then((text) => {
                expect(text.trim()).to.eq(newaccompanimentManager);
            });

        cy.get(el.managerEmailDetail)
            .children()
            .eq(1)
            .invoke('text')
            .then((text) => {
                expect(text.trim()).to.eq(newManagerEmail);
            });
    }

    validateNoticesByStatus(status) {
        cy.get(`${el.noticeListTable} tbody tr`).each(($row) => {
            cy.wrap($row).should('contain.text', status);
        });
    }

    validateNoticesByInstrumentType(instrumentType) {
        cy.get(`${el.noticeListTable} tbody tr`).each(($row) => {
            cy.wrap($row).should('contain.text', instrumentType);
        });
    }

    clickUploadPaymentsReportButton() {
        cy.get(el.uploadBPaymentsReportButton)
            .should('be.visible')
            .and('not.be.disabled')
            .contains('Subir relatório de pagamentos')
            .click();
    }

    clickShowAllInformationButton() {
        cy.get(el.showAllInformationButton).should('be.visible').click();
    }

    uploadPaymentsReport() {
        cy.intercept('POST', '/editais/projetos/pagamento/import').as('importPayments');

        cy.get(el.paymentsReportFileInput).selectFile('cypress/fixtures/documents/payments-report.csv', {
            force: true,
        });
    }

    displaySuccessMessagePaymentReportUploaded() {
        cy.wait('@importPayments');

        cy.get(el.successAlert)
            .contains('Importação concluída. 1 projeto(s) tiveram parcela(s) atualizada(s) com sucesso.')
            .should('be.visible');
    }
}

export default new Notice();
