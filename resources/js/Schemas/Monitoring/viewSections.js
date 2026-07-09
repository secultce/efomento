import { useDate } from '@/Composables/useDate';

const { addDaysTo, getDate, daysBetween, formatDate } = useDate();

export const viewSections = [
    {
        title: 'Prazos e datas',
        fields: [
            {
                label: 'Data de solicitação do relatório de monitoramento',
                compute: (project) => {
                    const installments = project.budgets?.installments ?? [];
                    const currentInstallment = installments.find(
                        (installment) =>
                            Number(installment.installment_number) === Number(project.current_installment_cycle ?? 1)
                    );
                    const paymentDate = currentInstallment?.payment_date;
                    const signedAt = project.formalizations?.signed_by_office_at;

                    if (!paymentDate || !signedAt) return null;

                    const daysBetweenSignedAndPayment = Math.round(
                        (new Date(paymentDate) - new Date(signedAt)) / (1000 * 60 * 60 * 24)
                    );

                    const date = new Date(paymentDate);
                    date.setDate(date.getDate() + 120 + daysBetweenSignedAndPayment);

                    return date;
                },
                format: 'datetime',
            },
            {
                label: 'Data prevista para envio do relatório de monitoramento',
                compute: addDaysTo('formalizations.validity_end_at', 365),
                format: 'datetime',
            },
            {
                label: 'Prazo final para análise e emissão do parecer do monitoramento',
                compute: addDaysTo('sent_timestamp', 30),
                format: 'datetime',
            },

            {
                label: 'Data do pagamento',
                compute: (project) => {
                    const installments = project.budgets?.installments ?? [];
                    const currentInstallment = installments.find(
                        (installment) =>
                            Number(installment.installment_number) === Number(project.current_installment_cycle ?? 1)
                    );
                    return formatDate(currentInstallment?.payment_date);
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
                compute: daysBetween('formalizations.validity_start_at', 'formalizations.validity_end_at'),
            },
            {
                label: 'Período entre publica entre publicação e pagamento',
                key: 'monitoring.date_of_processing_of_the_opinion_via_suite',
            },
            {
                label: 'Data da publicação do DOE',
                compute: getDate('formalizations.official_gazette_published_at'),
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
