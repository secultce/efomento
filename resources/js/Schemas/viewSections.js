import { useDate } from '@/Composables/useDate';
import { useMask } from '@/Composables/useMask';
import {
    getCurrentInstallment,
    getInstallmentJustification,
    parseRegistrationFieldValue,
    getLegalType,
    getBudgetAllocation,
    getFullAddress,
} from './schemaUtils';

const { addDaysTo, getDate, daysBetween, daysBetweenDates, formatDate } = useDate();
const { getCpfCnpj } = useMask();

export const viewSections = [
    {
        key: 'monitoringDates',
        tabs: ['monitoring'],
        title: 'Prazos e datas',
        fields: [
            {
                label: 'Data de solicitação do relatório de monitoramento',
                compute: (project) => {
                    const paymentDate = getCurrentInstallment(project)?.payment_date;
                    const signedAt = project.formalizations?.signed_by_office_at;

                    if (!paymentDate || !signedAt) return null;

                    const daysBetweenSignedAndPayment = daysBetweenDates(signedAt, paymentDate);
                    const requestDeadlineDays = Number(project.notice?.monitoring_report_request_deadline_days);

                    const date = new Date(paymentDate);
                    date.setDate(date.getDate() + requestDeadlineDays + daysBetweenSignedAndPayment);

                    return formatDate(date);
                },
            },
            {
                label: 'Data prevista para envio do relatório de monitoramento',
                compute: (project) => formatDate(addDaysTo('formalizations.validity_end_at', 365)(project)),
            },
            {
                label: 'Prazo final para análise e emissão do parecer do monitoramento',
                compute: addDaysTo('sent_timestamp', 30),
                format: 'datetime',
            },

            {
                label: 'Data do pagamento',
                compute: (project) => formatDate(getCurrentInstallment(project)?.payment_date),
            },
            {
                label: 'Período entre publicação e pagamento',
                compute: (project) => {
                    const paymentDate = getCurrentInstallment(project)?.payment_date;
                    const publishedAt = project.formalizations?.official_gazette_published_at;

                    const days = daysBetweenDates(publishedAt, paymentDate);

                    return days === null ? null : `${days} dia(s)`;
                },
            },
            {
                label: 'Data de início de vigência do instrumento',
                compute: getDate('formalizations.validity_start_at'),
            },
            {
                label: 'Data de término de vigência do instrumento',
                compute: getDate('formalizations.validity_end_at'),
            },
            {
                label: 'Quantidade de dias da vigência inicial',
                compute: (project) => {
                    const days = daysBetween(
                        'formalizations.validity_start_at',
                        'formalizations.validity_end_at'
                    )(project);

                    return days === null ? null : `${days} dia(s)`;
                },
            },
            {
                label: 'Data da publicação do DOE',
                compute: getDate('formalizations.official_gazette_published_at'),
            },
            {
                label: 'Quantidade de dias da vigência inicial prorrogadas por atraso no pagamento',
                compute: (project) => {
                    const paymentDate = getCurrentInstallment(project)?.payment_date;
                    const signedAt = project.formalizations?.signed_by_office_at;

                    const days = daysBetweenDates(signedAt, paymentDate);

                    return days === null ? null : `${days} dias`;
                },
            },
        ],
    },
    {
        key: 'monitoringAgent',
        tabs: ['monitoring'],
        title: 'Dados do agente',
        fields: [
            { label: 'CPF / CNPJ', key: 'agent.latest_snapshot.cpf_cnpj' },
            { label: 'Área / linguagem / eixo', key: 'notice.name' },
            { label: 'Categoria de inscrição', key: 'category.name' },
            { label: 'Título do projeto', key: 'title_project' },
            { label: 'Telefone', key: 'agent.director_phone' },
            { label: 'E-mail', key: 'agent.latest_snapshot.email' },
        ],
    },
    {
        key: 'agentProject',
        tabs: ['opening', 'formalization', 'legalAnalysis'],
        title: 'Dados do agente e do projeto',
        fields: [
            { label: 'Nome social / Nome fantasia', key: 'agent.name' },
            { label: 'Personalidade jurídica', compute: getLegalType },
            { label: 'CPF / CNPJ do agente cultural', compute: getCpfCnpj('agent.latest_snapshot.cpf_cnpj') },
            { label: 'Área / linguagem / ciclo', key: 'notice.name' },
            { label: 'Categoria de inscrição', key: 'category.name' },
            { label: 'Título do projeto', key: 'title_project' },
            { label: 'N° da inscrição', key: 'number' },
        ],
    },
    {
        key: 'address',
        tabs: ['opening', 'formalization', 'legalAnalysis'],
        title: 'Endereço',
        fields: [
            {
                label: 'Endereço completo',
                compute: getFullAddress,
            },
            { label: 'Macrorregião', key: 'agent.latest_snapshot.macroregion' },
            { label: 'CEP', key: 'agent.latest_snapshot.postal_code' },
            { label: 'Município', key: 'agent.latest_snapshot.city' },
        ],
    },
    {
        key: 'additionalFields',
        tabs: ['opening', 'formalization', 'legalAnalysis'],
        title: 'Campos adicionais',
        fields: [
            { label: 'Nome completo do proponente', key: 'agent.name' },
            { label: 'Cargo do proponente', key: 'agent.director_position' },
            { label: 'Telefone do proponente', key: 'agent.latest_snapshot.phone' },
            { label: 'CPF do proponente', compute: getCpfCnpj('agent.latest_snapshot.cpf_cnpj') },
            { label: 'E-mail do proponente', key: 'agent.latest_snapshot.email' },
            { label: 'E-mail secundário', key: 'agent.latest_snapshot.secondary_email' },
            { label: 'Telefone secundário', key: 'agent.latest_snapshot.secondary_phone' },
        ],
    },
    {
        key: 'socioeconomic',
        tabs: ['opening', 'formalization', 'legalAnalysis'],
        title: 'Perfil socioeconômico',
        fields: [
            { label: 'Data de nascimento', compute: getDate('agent.latest_snapshot.birth_date') },
            { label: 'Escolaridade', key: 'agent.latest_snapshot.education' },
            { label: 'Raça / cor', key: 'agent.latest_snapshot.race' },
            { label: 'Orientação sexual', key: 'agent.latest_snapshot.sexual_orientation' },
            { label: 'Possui deficiência?', key: 'agent.latest_snapshot.has_disability' },
            { label: 'Gênero', key: 'agent.latest_snapshot.gender' },
        ],
    },
    {
        key: 'supervisors',
        tabs: ['opening', 'formalization', 'legalAnalysis', 'monitoring'],
        title: 'Dados do fiscal',
        fields: [
            { label: 'Fiscal titular', key: 'opening.supervisors.0.user.name' },
            { label: 'Matrícula titular', key: 'opening.supervisors.0.user.registration_number' },
            { label: 'Fiscal suplente', key: 'opening.supervisors.1.user.name' },
            { label: 'Matrícula suplente', key: 'opening.supervisors.1.user.registration_number' },
        ],
    },
    {
        key: 'extraFields',
        tabs: ['opening', 'formalization', 'legalAnalysis'],
        title: 'Campos Extras e Documentos',
        fields: [{ label: 'Campos extras', key: 'extra_fields' }],
    },
    {
        key: 'registration',
        tabs: ['opening', 'formalization', 'legalAnalysis'],
        title: 'Ficha de Inscrição (Mapas Cultural)',
        fields: [
            {
                label: 'Campos da inscrição',
                fullWidth: true,
                items: (project) => {
                    const fields = project.opening?.registration_data?.fields ?? [];

                    return fields.map((f) => ({
                        label: f.titleField,
                        value: parseRegistrationFieldValue(f.valueField),
                    }));
                },
            },
        ],
    },
    {
        key: 'agentProjectWithAddress',
        tabs: ['budget', 'payment'],
        title: 'Dados do agente e do projeto',
        fields: [
            { label: 'Nome social / Nome fantasia', key: 'agent.name' },
            { label: 'Personalidade jurídica', compute: getLegalType },
            { label: 'CPF / CNPJ do agente cultural', compute: getCpfCnpj('agent.latest_snapshot.cpf_cnpj') },
            { label: 'Área / linguagem / ciclo', key: 'notice.name' },
            { label: 'Categoria de inscrição', key: 'category.name' },
            { label: 'Título do projeto', key: 'title_project' },
            { label: 'N° da inscrição', key: 'number' },
            {
                label: 'Endereço completo',
                compute: getFullAddress,
            },
        ],
    },
    {
        key: 'bank',
        tabs: ['formalization', 'budget', 'payment'],
        title: 'Dados Bancários',
        fields: [
            { label: 'Número do credor', key: 'opening.creditor_number' },
            { label: 'Banco', key: 'opening.bank.name' },
            { label: 'Conta', key: 'opening.account' },
            { label: 'Agência', key: 'opening.branch' },
            { label: 'Tipo de Conta', key: 'opening.account_type' },
        ],
    },
    {
        key: 'budgetAllocation',
        tabs: ['budget', 'payment'],
        title: 'Dotação orçamentária',
        fields: [
            { label: 'Código da Dotação', compute: (project) => getBudgetAllocation(project)?.allocation_code },
            { label: 'Dotação orçamentária', compute: (project) => getBudgetAllocation(project)?.allocation_number },
            { label: 'Cidade do agente', compute: (project) => project?.agent?.latest_snapshot?.city },
            { label: 'Macrorregião', compute: (project) => project?.agent?.latest_snapshot?.macroregion },
        ],
    },
    {
        key: 'installmentJustification',
        tabs: ['budget'],
        title: 'Justificativa da parcela',
        fields: [
            {
                label: 'Texto da justificativa',
                compute: (project) => getInstallmentJustification(project),
                fullWidth: true,
            },
        ],
    },
    {
        key: 'loa',
        tabs: ['budget', 'payment'],
        title: 'Informações da LOA (Lei Orçamentária Anual)',
        fields: [
            { label: 'Unidade orçamentária', compute: (project) => getBudgetAllocation(project)?.management_unit },
            { label: 'Programa de trabalho', compute: (project) => getBudgetAllocation(project)?.work_program },
            { label: 'Objetivo', compute: (project) => getBudgetAllocation(project)?.objective },
            { label: 'Entrega', compute: (project) => getBudgetAllocation(project)?.deliverable },
            { label: 'Função', compute: (project) => getBudgetAllocation(project)?.budget_function },
            { label: 'Subfunção', compute: (project) => getBudgetAllocation(project)?.budget_subfunction },
            { label: 'Projeto/atividade', compute: (project) => getBudgetAllocation(project)?.project_activity },
            { label: 'Elemento de despesa', compute: (project) => getBudgetAllocation(project)?.expense_element },
            { label: 'Fonte de recursos', compute: (project) => getBudgetAllocation(project)?.funding_source },
            { label: 'MAPP', compute: (project) => getBudgetAllocation(project)?.mapp },
            {
                label: 'Projeto finalistico - INTERIOR',
                compute: (project) => getBudgetAllocation(project)?.finalistic_project,
            },
        ],
    },
];

export function getViewSections(tab) {
    return viewSections.filter((section) => section.tabs.includes(tab));
}
