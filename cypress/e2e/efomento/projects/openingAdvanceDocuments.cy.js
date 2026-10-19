import OpeningWorkflow from '../../../support/workflows/OpeningWorkflow.js';

const CI = 'Comunicação interna';
const TC = 'Termo de execução cultural';

describe('Opening - tramitação exige CI e TC', () => {
    before(() => {
        cy.resetCypressData();
    });

    beforeEach(() => {
        cy.fixture('notices').then((notices) => {
            cy.wrap(notices.notice).as('notice');
        });
        cy.fixture('openingTramit').as('projects');
    });

    it('should block tramit without CI and TC', function () {
        OpeningWorkflow.tramitWithMissingDocuments({
            role: 'fomentation',
            notice: this.notice,
            project: this.projects.withoutDocuments,
            missingDocuments: [CI, TC],
        });
    });

    it('should block tramit with only CI', function () {
        OpeningWorkflow.tramitWithMissingDocuments({
            role: 'fomentation',
            notice: this.notice,
            project: this.projects.onlyCI,
            missingDocuments: [TC],
            presentDocuments: [CI],
        });
    });

    it('should block tramit with only TC', function () {
        OpeningWorkflow.tramitWithMissingDocuments({
            role: 'fomentation',
            notice: this.notice,
            project: this.projects.onlyTC,
            missingDocuments: [CI],
            presentDocuments: [TC],
        });
    });

    it('should tramit with CI and TC', function () {
        OpeningWorkflow.tramitWithRequiredDocuments({
            role: 'fomentation',
            notice: this.notice,
            project: this.projects.withDocuments,
        });
    });
});
