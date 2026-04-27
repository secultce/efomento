export const  viewSections = [
  {
    title: 'Dados do agente e do projeto',
    fields: [
      { label: 'Nome social / Nome fantasia', key: 'name' },
      { label: 'Personalidade jurídica', key: 'legal_type' },
      { label: 'CPF / CNPJ do agente cultural', key: 'cpf_cnpj' },
      { label: 'Área / linguagem / ciclo', key: 'area' },
      { label: 'Categoria de inscrição', key: 'category.name' },
      { label: 'Título do projeto', key: 'project_title' },
    ],
  },
  {
    title: 'Dados de identificação',
    fields: [
      { label: 'N° da inscrição', key: 'registration_id' },
      { label: 'Título do projeto', key: 'project_title' },
      { label: 'Nome social / Nome fantasia', key: 'name' },
      { label: 'CPF / CNPJ', key: 'cpf_cnpj' },
      { label: 'Categoria de inscrição', key: 'category.name' },
    ],
  },
  {
    title: 'Endereço',
    fields: [
      { label: 'Endereço completo', key: 'address' },
      { label: 'Macrorregião', key: 'region' },
      { label: 'CEP', key: 'cep' },
      { label: 'Município', key: 'city' },
    ],
  },
  {
    title: 'Campos adicionais',
    fields: [
      { label: 'Nome completo do dirigente', key: 'manager_name' },
      { label: 'Cargo do dirigente', key: 'manager_role' },
      { label: 'Telefone do dirigente', key: 'manager_phone' },
      { label: 'CPF do dirigente', key: 'manager_cpf' },
      { label: 'E-mail do dirigente', key: 'manager_email' },
      { label: 'E-mail do proponente', key: 'email' },
      { label: 'E-mail secundário', key: 'secondary_email' },
      { label: 'Telefone secundário', key: 'secondary_phone' },
    ],
  },
  {
    title: 'Perfil socioeconômico',
    fields: [
      { label: 'Data de nascimento', key: 'birthdate' },
      { label: 'Escolaridade', key: 'education' },
      { label: 'Raça / cor', key: 'race' },
      { label: 'Orientação sexual', key: 'sexual_orientation' },
      { label: 'Possui deficiência?', key: 'has_disability' },
      { label: 'Gênero', key: 'gender' },
    ],
  },
  {
    title: 'Arquivos e documentos',
    fields: [
      { label: 'Comprovante bancário', key: 'bank_file' },
      { label: 'Parecer individual', key: 'review_file' },
      { label: 'Ficha de inscrição', key: 'registration_file' },
      { label: 'Autodeclaração', key: 'self_declaration' },
      { label: 'Plano de ação', key: 'action_plan' },
      { label: 'Documento de identificação', key: 'document' },
      { label: 'Publicação da comissão', key: 'committee_publication' },
      { label: 'Resultado após recurso', key: 'post_appeal_result' },
      { label: 'Recurso', key: 'appeal' },
      { label: 'Resultado final', key: 'final_result' },
      { label: 'Publicação no DOE', key: 'official_gazette' },
    ],
  },
]