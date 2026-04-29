export const formSections = [
    {
        title: 'Dados da Abertura',
        fields: [
            { 
                label: 'Número do processo*',
                key: 'opening.opening_nup',
                inputType: 'text',
                saveRequired: true,
                tramitRequired: true,
                permissionRequired: ['edit_opening'],
                mask: '####.######/####-##',
                placeholder: 'Insira aqui o número do processo',
                isEditable: true,    
            },
            { 
                label: 'Data de abertura do processo', 
                key: 'opening.opening_date', 
                inputType: 'date',
                saveRequired: true },
            { 
                label: 'Selecione o status do agente cultural',
                key: 'opening.agent_status',
                inputType: 'select',
                placeholder: 'Selecione...',
                saveRequired: true
            },
            { 
                label: 'Responsável por abrir o processo',
                key: 'opening.opened_by',
                inputType: 'text',
                saveRequired: true,
                placeholder: 'Exemplo: 12345.678901/2023-01',
            },
        ],
    },
    {
        title: 'Parcela',
        fields: [
            { 
                label: 'Valor de repasse (Parcela única)',
                key: 'budget.installments[0].amount',
                inputType: 'money',
                saveRequired: true,
                tramitRequired: true,
                permissionRequired: ['edit_opening'],
                mask: '####.######/####-##',
                placeholder: 'Insira aqui o número do processo',
                isEditable: true,    
            },
            { 
                label: 'Valor por extenso', 
                key: 'budget.installments[0].amount',
                inputType: 'money_extenso',
                saveRequired: true 
            },
        ],
        options: {
            addButtonLabel: 'Adicionar parcela',
             // Define a função para adicionar uma nova parcela
            onAdd: (fields) => {
                const newParcela = {
                    opening_nup: '',
                    legal_type: '',
                };
                fields.push(newParcela);
            }
        }
    },
    {
        title: 'Certidão e-parcerias',
        fields: [
            { 
                label: 'Informe regularidade e inadimplência',
                key: 'formalization.report_status',
                inputType: 'text',
                saveRequired: true,
                tramitRequired: true,
                permissionRequired: ['edit_opening'],
                mask: '####.######/####-##',
                placeholder: 'Insira aqui o número do processo',
                isEditable: true,    
            },
            { 
                label: 'Data da certidão', 
                key: 'formalization.eparcerias_certificate_date', 
                inputType: 'date',
                saveRequired: true 
            }
        ],
    },
    {
        title: 'Dados bancários',
        fields: [
            { 
                label: 'Banco',
                key: 'opening.bank',
                inputType: 'text',
                saveRequired: true,
                tramitRequired: true,
                permissionRequired: ['edit_opening'],
                mask: '####.######/####-##',
                placeholder: 'Insira aqui o número do processo',
                isEditable: true,    
            },
            { 
                label: 'Tipo de conta', 
                key: 'opening.account_type', 
                inputType: 'text',
                saveRequired: true },
            { 
                label: 'Agência',
                key: 'opening.branch',
                inputType: 'text',
                placeholder: 'Selecione...',
                saveRequired: true
            },
            { 
                label: 'Conta',
                key: 'opening.account',
                inputType: 'text',
                saveRequired: true,
                placeholder: 'Exemplo: 12345.678901/2023-01',
            },
        ],
    },
    {
        title: 'Dados do fiscal',
        fields: [
            { 
                label: 'fiscal titular',
                key: 'opening.supervisors[0].name',
                inputType: 'text',
                saveRequired: true,
                tramitRequired: true,
                permissionRequired: ['edit_opening'],
                mask: '####.######/####-##',
                placeholder: 'Insira aqui o número do processo',
                isEditable: true,    
            },
            { 
                label: 'Matricula do fiscal titular',
                key: 'opening.supervisors[0].nup',
                inputType: 'text',
                saveRequired: true,
                tramitRequired: true,
                permissionRequired: ['edit_opening'],
                mask: '####.######/####-##',
                placeholder: 'Insira aqui o número do processo',
                isEditable: true,    
            },
            { 
                label: 'Fiscal suplente',
                key: 'opening.supervisors[1].name',
                inputType: 'text',
                saveRequired: true,
                tramitRequired: true,
                permissionRequired: ['edit_opening'],
                mask: '####.######/####-##',
                placeholder: 'Insira aqui o número do processo',
                isEditable: true,    
            },
            { 
                label: 'Matricula do fiscal suplente',
                key: 'opening.supervisors[1].nup',
                inputType: 'text',
                saveRequired: true,
                tramitRequired: true,
                permissionRequired: ['edit_opening'],
                mask: '####.######/####-##',
                placeholder: 'Insira aqui o número do processo',
                isEditable: true,    
            },
        ],
    },
]