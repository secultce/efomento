export const formSections = [
     {
    title: 'Dados da Abertura',
    fields: [
        { 
            label: 'Número do processo*',
            key: 'opening_nup',
            inputType: 'text',
            saveRequired: true,
            tramitRequired: true,
            permissionRequired: ['edit_opening'],
            mask: '####.######/####-##',
            placeholder: 'Exemplo: 12345.678901/2023-01',
            isEditable: true,    
        },
        { label: 'Data de abertura do processo', key: 'legal_type', inputType: 'date', saveRequired: true },
        { label: 'Selecione o status do agente cultural', key: 'cpf_cnpj', inputType: 'select', placeholder: 'Selecione...', saveRequired: true },
        { label: 'Responsável por abrir o processo', key: 'area', inputType: 'text', saveRequired: true, placeholder: 'Exemplo: 12345.678901/2023-01', },
        { label: 'Categoria de inscrição', key: 'category.name', inputType: 'select', selectOptions: [], saveRequired: true },
        { label: 'Título do projeto', key: 'project_title', inputType: 'text', saveRequired: true },
    ],
  },
]