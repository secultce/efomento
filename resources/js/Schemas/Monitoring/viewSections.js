import { addDaysTo, getDate } from '@/Utils/dateHelpers';

export const viewSections = [
    {
        title: 'Prazos e datas',
        fields: [
            {
                label: 'Data de solicitação do relatório de monitoramento',
                compute: addDaysTo('sent_timestamp', 120),
                format: 'datetime',
            },
            {
                label: 'Data prevista para envio do relatório de monitoramento',
                compute: addDaysTo('sent_timestamp', 365),
                format: 'datetime',
            },
            {
                label: 'Prazo final para análise e emissão do parecer do monitoramento',
                compute: addDaysTo('sent_timestamp', 30),
                format: 'datetime',
            },
            {
                label: 'Data de pagamento',
                key: 'monitoring.deadline_for_analysis_and_issuance_of_the_opinion',
            },

            {
                label: 'Data de início de vigência do instrumento',
                compute: getDate('formalization.validity_start_at'),
            },
            {
                label: 'Data de término de vigência do instrumento',
                compute: getDate('formalization.validity_end_at'),
            },
            {
                label: 'Quantidade de dias da vigência inicial',
                key: 'monitoring.date_of_notification_to_the_agent',
            },
            {
                label: 'Período entre publica entre publicação e pagamento',
                key: 'monitoring.date_of_processing_of_the_opinion_via_suite',
            },
            {
                label: 'Data da publicação do DOE',
                compute: getDate('formalization.official_gazette_published_at'),
            },
        ],
    },
    {
        title: 'Dados do agente',
        fields: [
            { label: 'CPF / CNPJ', key: 'agent.cpf' },
            { label: 'Área / linguagem / eixo', key: 'notice.name' },
            { label: 'Categoria de inscrição', key: 'category.name' },
            { label: 'Título do projeto', key: 'title_project' },
            { label: 'Telefone', key: 'agent.director_phone' },
            { label: 'E-mail', key: 'agent.latest_snapshot.email' },
        ],
    },
    {
        title: 'Dados do fiscal',
        fields: [
            { label: 'Fiscal titular', key: 'opening.supervisors.0.user.name' },
            { label: 'Matrícula titular', key: 'opening.supervisors.0.user.registration_number' },
            { label: 'Fiscal suplente', key: 'opening.supervisors.1.user.name' },
            { label: 'Matrícula suplente', key: 'opening.supervisors.1.user.registration_number' },
        ],
    },
];
