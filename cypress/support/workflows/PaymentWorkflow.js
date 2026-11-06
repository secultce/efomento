import '../commands.js';
import ProjectWorkflow from '../workflows/ProjectWorkflow.js';
import PaymentTab from '../../pages/project/paymentTab/PaymentTab.js';
import DocumentWorkflow from './DocumentWorkflow.js';
import { DOCUMENTS } from '../constants/documents.js';

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
}

export default new PaymentWorkflow();
