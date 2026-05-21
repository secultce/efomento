import Login from '../../../pages/auth';
import Notice from '../../../pages/notice';

describe('Notice Page - E2E Tests', () => {
    beforeEach(() => {
        cy.fixture('users').as('user');
        cy.fixture('notices').as('notice');

        cy.get('@user').then((user) => {
            Login.accessLoginPage();
            Login.successLogin(user.valid_email, user.password, user.name);
        });

        Notice.visitPage();
        Notice.verifyPageLoaded();
    });

    describe('Page Access and Navigation', () => {
        it('should access the notice list page', function () {
            cy.url().should('include', '/editais');
        });

        it('should display the notice list table', function () {
            cy.get('[data-cy=table-notice-list]').should('be.visible');
        });
    });

    describe('Dashboard Visibility', () => {
        it('should display all dashboard cards', function () {
            Notice.verifyDashboardCardsAreVisible();
        });

        it('should display all dashboard metric cards', function () {
            Notice.verifyAllDashboardMetrics();
        });
    });

    describe('User Information Display', () => {
        it('should display logged user name in header avatar', function () {
            Notice.verifyLoggedUserDisplayedInHeader(this.user.name);
        });

        it('should display welcome message with user name', function () {
            Notice.verifyWelcomeMessageDisplaysUsername(this.user.name);
        });
    });

    describe('Identification Data Form', () => {
        it('should open the identification data form', function () {
            Notice.openIdentificationDataForm();
            cy.get('[data-cy=notice-nup-identification-data-form]').should('be.visible');
        });

        it('should fill and submit the identification data form', function () {
            Notice.openIdentificationDataForm();

            const formData = {
                noticeNup: this.notice.nup,
                instrumentType: this.notice.processInstrumentType,
                totalAmount: this.notice.noticeTotalValue,
                noticeManager: this.notice.noticeAccompanimentManager,
                managerEmail: this.notice.noticeManagerEmail,
                quotaNumber: this.notice.quotaNumber,
            };

            Notice.fillIdentificationDataForm(formData);
            Notice.verifySuccessMessage();
        });
    });

    describe('Search Functionality', () => {
        it('should find a notice by title', function () {
            Notice.searchNoticeByTitle(this.notice.title);
        });

        it('should find a notice by NUP number', function () {
            Notice.searchNoticeByNup(this.notice.nup);
        });

        it('should clear search and display all notices', function () {
            Notice.searchNoticeByNup(this.notice.nup);
            cy.get('[data-cy=find-specific-notice] input').clear();
            cy.get('[data-cy=table-notice-list] tbody tr').should('have.length.greaterThan', 1);
        });
    });

    describe('Filtering', () => {
        it('should filter notices by process status', function () {
            Notice.filterByProcessStatus(this.notice.processsStatus);
        });

        it('should filter notices by instrument type', function () {
            Notice.filterByInstrumentType(this.notice.processInstrumentType);
        });
    });

    describe('Notice Details View', () => {
        it('should open notice details page', function () {
            Notice.searchNoticeByNup(this.notice.nup);
            Notice.goToNoticeDetailsPage(this.notice.nup);
            cy.url().should('match', /\/editais\/\d+\/projetos$/);
        });

        it('should display all information in detail view', function () {
            Notice.searchNoticeByNup(this.notice.nup);
            Notice.goToNoticeDetailsPage(this.notice.nup);
            Notice.clickShowAllInformationButton();
            Notice.verifyDetailViewElements();
        });

        it('should display correct NUP in detail view', function () {
            Notice.searchNoticeByNup(this.notice.nup);
            Notice.goToNoticeDetailsPage(this.notice.nup);
            cy.get('[data-cy=notice-nup-show-all-information]').should('be.visible').and('contain', this.notice.nup);
        });
    });

    describe('Pagination', () => {
        it('should change the number of items displayed per page', function () {
            const itemsPerPage = this.notice.quantityPerPage;
            Notice.changeItemsPerPage(itemsPerPage);
        });

        it('should navigate to next page and highlight current page number', function () {
            Notice.goToPage(2);
            cy.get('[data-cy=pagination-number-notice-list]')
                .contains('2')
                .parent()
                .should('have.css', 'background-color', 'rgb(255, 193, 7)');
        });
    });

    describe('Form Error Handling', () => {
        it('should display error when submitting form with invalid data', function () {
            Notice.openIdentificationDataForm();

            // Try to submit with empty required fields
            cy.get('[data-cy=add-data-identification-data-form-button]').click();

            // Expect validation error
            cy.get('[role=alert]').should('be.visible');
        });
    });

    describe('Data Persistence', () => {
        it('should maintain notice list after page refresh', function () {
            Notice.searchNoticeByNup(this.notice.nup);
            cy.get('[data-cy=notice-nup-notices-list]').should('contain', this.notice.nup);

            cy.reload();
            Notice.verifyPageLoaded();

            Notice.searchNoticeByNup(this.notice.nup);
            cy.get('[data-cy=notice-nup-notices-list]').should('contain', this.notice.nup);
        });
    });
});
