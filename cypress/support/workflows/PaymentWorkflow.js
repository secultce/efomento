import '../commands.js';
import ProjectWorkflow from '../workflows/ProjectWorkflow.js';
import PaymentTab from '../../pages/project/paymentTab/PaymentTab.js';
import Tramit from '../../pages/project/processTramit/ProcessTramit.js';
import DocumentWorkflow from './DocumentWorkflow.js';
import { DOCUMENTS } from '../constants/documents.js';
import NoticeWorkflow from './NoticeWorkflow.js';

class PaymentWorkflow {
    accessPaymentTab({ notice, project }) {
        ProjectWorkflow.accessProjectDetails({
            notice,
            project,
        });

        PaymentTab.goToPaymentTab();

        PaymentTab.validatePage();
    }

    createPaymentOrder({ notice, project, phase }) {
        DocumentWorkflow.create({
            notice,
            project,
            phase,
            documentType: DOCUMENTS.paymentOrder,
        });
    }

    processProjectToMonitoring({ notice, project }) {
        NoticeWorkflow.uploadPaymentsReportFile();
        NoticeWorkflow.validatePaymentReportUpload();
        this.accessPaymentTab({ notice, project });
        Tramit.tramit();
    }
}

export default new PaymentWorkflow();
