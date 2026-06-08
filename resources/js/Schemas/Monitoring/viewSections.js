export const viewSections = [
    {
        title: 'Prazos e datas',
        fields: [
            { label: 'Início de vigência do instrumento', key: 'monitoring.effective_date_of_the_instrument' },
            { label: 'Término de vigência do instrumento', key: 'monitoring.expiration_date_of_the_instrument' },
            { label: 'Prazo para preenchimento do relatório', key: 'monitoring.deadline_for_completing_report' },
            {
                label: 'Prazo para análise e emissão do parecer',
                key: 'monitoring.deadline_for_analysis_and_issuance_of_the_opinion',
            },
            { label: 'Tramitação do parecer via Suite', key: 'monitoring.date_of_processing_of_the_opinion_via_suite' },
            { label: 'Notificação ao agente', key: 'monitoring.date_of_notification_to_the_agent' },
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
