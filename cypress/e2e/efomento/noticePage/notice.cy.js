import NoticeWorkflow from '../../../support/workflows/NoticeWorkflow';
import Notice from '../../../pages/notice/NoticePage';

describe('Notice Page - E2E Tests', () => {
    beforeEach(() => {
        cy.fixture('users').as('user');
        cy.fixture('notices').as('notice');
    });

    describe('Dashboard', () => {
        it('should display the notice dashboard metrics', function () {
            // Arrange
            cy.loginByRole('fomentation');

            // Act
            NoticeWorkflow.gotToNoticePage();

            // Assert
            Notice.verifyDashboardCardsAreVisible();
        });

        it('should display all dashboard metric cards', function () {
            // Arrange
            cy.loginByRole('fomentation');

            // Act
            NoticeWorkflow.gotToNoticePage();

            // Assert
            Notice.verifyAllDashboardMetrics();
        });
    });

    describe('Identification Data', () => {
        it('should open the identification data form', function () {
            // Arrange
            cy.loginByRole('fomentation');
            NoticeWorkflow.gotToNoticePage();

            // Act
            NoticeWorkflow.openIdentificationDataForm();

            // Assert
            NoticeWorkflow.validateIdentificationDataFormIsVisible();
        });

        it('should fill and submit the identification data form', function () {
            // Arrange
            const notice = this.notice;
            cy.loginByRole('fomentation');

            // Act
            NoticeWorkflow.fillNoticeIdentificationData(notice);
            Notice.submitIdentificationDataForm();

            // Assert
            Notice.verifySuccessMessageIdentificationDataForm();
        });
    });

    describe('Search Functionality', () => {
        it('should find a notice by title', function () {
            // Arrange
            const notice = this.notice;
            cy.loginByRole('fomentation');

            // Act
            NoticeWorkflow.gotToNoticePage();
            Notice.searchNoticeByTitle(notice.title);

            // Assert
            Notice.validateResultSearchByTitle(notice.title);
        });

        it('should find a notice by NUP', function () {
            // Arrange
            const notice = this.notice;
            cy.loginByRole('fomentation');

            // Act
            NoticeWorkflow.gotToNoticePage();
            Notice.searchNoticeByNup(notice.noticeNup);

            // Assert
            Notice.validateResultSearchByNup(notice.noticeNup);
        });

        it('should clear search and display all notices', function () {
            // Arrange
            const notice = this.notice;
            cy.loginByRole('fomentation');

            NoticeWorkflow.gotToNoticePage();

            Notice.getTotalNotices().then((initialTotal) => {
                Notice.searchNoticeByNup(notice.noticeNup);

                // Act
                Notice.clearNoticeSearch();

                // Assert
                Notice.validateAllNoticesAreDisplayed(initialTotal);
            });
        });
    });

    describe('Filtering', () => {
        it('should filter notices by process status', function () {
            // Arrange
            const notice = this.notice;
            cy.loginByRole('fomentation');

            NoticeWorkflow.gotToNoticePage();

            // Act
            Notice.filterByProcessStatus(notice.processStatus);

            // Assert
            Notice.validateNoticesByStatus(notice.processStatus);
        });

        it('should filter notices by instrument type', function () {
            // Arrange
            const notice = this.notice;
            cy.loginByRole('fomentation');

            NoticeWorkflow.gotToNoticePage();

            // Act
            Notice.filterByInstrumentType(notice.instrumentType);

            // Assert
            Notice.validateNoticesByInstrumentType(notice.instrumentType);
        });
    });

    describe('Notice Details View', () => {
        it('should open notice details page', function () {
            // Arrange
            const notice = this.notice;
            cy.loginByRole('fomentation');

            NoticeWorkflow.gotToNoticePage();

            // Act
            Notice.searchNoticeByNup(notice.noticeNup);
            Notice.goToNoticeDetailsPage(notice.noticeNup);

            // Assert
            NoticeWorkflow.validateNoticeDetailsPageUrl();
        });

        it('should display all information in detail view', function () {
            // Arrange
            const notice = this.notice;
            cy.loginByRole('fomentation');
            NoticeWorkflow.gotToNoticePage();

            // Act
            Notice.searchNoticeByNup(notice.noticeNup);
            Notice.goToNoticeDetailsPage(notice.noticeNup);
            Notice.clickShowAllInformationButton();

            // Assert
            Notice.verifyDetailViewElements();
            Notice.verifyIdentificationData(notice);
        });
    });

    describe('Pagination', () => {
        it('should change the number of items displayed per page', function () {
            // Arrange
            const noticesPerPage = this.notice.quantityPerPage;
            cy.loginByRole('fomentation');
            NoticeWorkflow.gotToNoticePage();

            // Act
            Notice.changeItemsPerPage(noticesPerPage);

            // Assert
            Notice.validateNoticesPerPage(noticesPerPage);
        });

        it('should navigate to next page and highlight current page number', function () {
            // Arrange
            const pageNumber = '1';
            cy.loginByRole('fomentation');

            NoticeWorkflow.gotToNoticePage();

            // Act
            Notice.goToPage(pageNumber);

            // Assert
            Notice.verifyPageIsActive(pageNumber);
        });
    });

    describe('Form Error Handling', () => {
        it('should display error when submitting form with invalid data', function () {
            // Arrange
            cy.loginByRole('fomentation');

            NoticeWorkflow.gotToNoticePage();

            Notice.openIdentificationDataForm();

            // Act
            Notice.submitIdentificationDataForm();

            // Assert
            Notice.verifyNoticeNupRequiredFieldError();
        });
    });

    describe('Update Notice Data', () => {
        it('should update data about notice and save', function () {
            const currentNotice = this.notice;
            const updateData = this.notice;

            Notice.goToNoticeDetailsPage(currentNotice.noticeNup);
            Notice.clickShowAllInformationButton();
            Notice.verifyDetailViewElements();
            Notice.updateDataAboutProcess(updateData.noticeInstrumentType, updateData.noticeManagerEmail);
            Notice.verifySuccessMessageUpdateNoticeData();
            Notice.verifyUpdatedDataAboutProcess(updateData.noticeInstrumentType, updateData.noticeManagerEmail);
        });
    });

    describe('Upload Payment Report', () => {
        it('should upload payments report', function () {
            // Arrange
            cy.loginByRole('financial');

            // Act
            NoticeWorkflow.uploadPaymentsReportFile();

            // Assert
            NoticeWorkflow.validatePaymentReportUpload();
        });
    });
});
