import { DOCUMENTS } from '../../../support/constants/documents.js';
import { PHASES } from '../../../support/constants/phases.js';
import OpeningWorkflow from '../../../support/workflows/OpeningWorkflow.js';

describe('Opening', () => {
    beforeEach(() => {
        cy.fixture('notices').as('notice');
        cy.fixture('projects').as('project');
    });

    describe('Document Generate', () => {
        it('should create CI for one project', function () {
            OpeningWorkflow.createCI({
                role: 'fomentation',
                notice: this.notice,
                project: this.project,
                phase: PHASES.OPENING,
                documentType: DOCUMENTS.ci,
            });
        });
    });
});
