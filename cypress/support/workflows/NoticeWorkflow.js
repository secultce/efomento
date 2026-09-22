import '../commands.js';
import Notice from '../../pages/notice/NoticePage.js';

class NoticeWorkflow {
    gotToNoticePage() {
        Notice.visitPage();
        Notice.verifyPageLoaded();
    }

    validateDashboardCardsAreVisible() {
        Notice.verifyDashboardCardsAreVisible();
    }

    validateAllDashBoardMetrics() {
        Notice.verifyAllDashboardMetrics();
    }

    accessNoticeDetails(nup) {
        Notice.searchNoticeByNup(nup);
        Notice.findNoticeByNup(nup);

        cy.url().should('match', /\/editais\/\d+\/projetos$/);
    }

    fillRequiredNoticeIdentificationData(notice) {
        Notice.openIdentificationDataForm();

        Notice.fillRequiredIdentificationDataFields({
            noticeNup: notice.noticeNup,
            instrumentType: notice.instrumentType,
            totalAmount: notice.totalAmount,
            quotaNumber: notice.quotaNumber,
        });
    }

    fillNoticeIdentificationData(notice) {
        this.gotToNoticePage();

        Notice.searchNoticeByTitle(notice.title);
        Notice.openIdentificationDataForm();

        Notice.fillIdentificationDataForm({
            noticeNup: notice.noticeNup,
            instrumentType: notice.instrumentType,
            totalAmount: notice.totalAmount,
            accompanimentManager: notice.accompanimentManager,
            managerEmail: notice.managerEmail,
            quotaNumber: notice.quotaNumber,
        });
    }

    updateNoticeData(currentNotice, newNotice) {
        this.accessNoticeDetails(currentNotice.noticeNup);

        Notice.clickShowAllInformationButton();
        Notice.verifyDetailViewElements();

        Notice.updateDataAboutProcess(newNotice.noticeInstrumentType, newNotice.noticeManagerEmail);

        Notice.verifySuccessMessageUpdateNoticeData();

        Notice.verifyUpdatedDataAboutProcess(newNotice.noticeInstrumentType, newNotice.noticeManagerEmail);
    }

    uploadPaymentsReportFile() {
        this.gotToNoticePage();

        Notice.clickUploadPaymentsReportButton();

        Notice.uploadPaymentsReport();
    }

    getInitialNoticeTotal() {
        return Notice.getTotalNotices();
    }

    openIdentificationDataForm() {
        Notice.openIdentificationDataForm();
    }

    validatePaymentReportUpload() {
        Notice.displaySuccessMessagePaymentReportUploaded();
    }

    validateIdentificationDataFormIsVisible() {
        Notice.verifyIdentificationDataFormIsVisible();
    }

    validateAllNoticesAreDisplayed(notice) {
        Notice.getTotalNotices().then((totalNotices) => {
            Notice.searchNoticeByNup(notice.noticeNup);
            Notice.clearNoticeSearch();

            Notice.validateAllNoticesAreDisplayed(totalNotices);
        });
    }

    validateNoticeDetailsPageUrl() {
        cy.url().should('match', /\/editais\/\d+\/projetos$/);
    }
}

export default new NoticeWorkflow();
