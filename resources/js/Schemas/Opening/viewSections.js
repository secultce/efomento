export const  viewSections = [
  {
    title: 'Dados do agente e do projeto',
    fields: [
      { label: 'Nome social / Nome fantasia', key: 'agent.name' },
      { label: 'Personalidade jurídica', key: 'agent.legal_type' },
      { label: 'CPF / CNPJ do agente cultural', key: 'agent.cpf' },
      { label: 'Área / linguagem / ciclo', key: 'notice.name' },
      { label: 'Categoria de inscrição', key: 'category.name' },
      { label: 'Título do projeto', key: 'title_project' },
      { label: 'N° da inscrição', key: 'registration_id' },
    ],
  },
  {
    title: 'Endereço',
    fields: [
      { label: 'Endereço completo', key: 'latestSnapshot.address' },
      { label: 'Macrorregião', key: 'latestSnapshot.macrorregion' },
      { label: 'CEP', key: 'latestSnapshot.postal_code' },
      { label: 'Município', key: 'latestSnapshot.city' },
    ],
  },
  {
    title: 'Campos adicionais',
    fields: [
      { label: 'Nome completo do proponente', key: 'agent.name' },
      { label: 'Cargo do proponente', key: 'agent.director_position' },
      { label: 'Telefone do proponente', key: 'agent.director_phone' },
      { label: 'CPF do proponente', key: 'agent.cpf' },
      { label: 'E-mail do proponente', key: 'latestSnapshot.email' },
      { label: 'E-mail secundário', key: 'latestSnapshot.phone' },
      { label: 'Telefone secundário', key: 'latestSnapshot.secondary_phone' },
    ],
  },
  {
    title: 'Perfil socioeconômico',
    fields: [
      { label: 'Data de nascimento', key: 'latestSnapshot.birth_date' },
      { label: 'Escolaridade', key: 'latestSnapshot.education' },
      { label: 'Raça / cor', key: 'latestSnapshot.race' },
      { label: 'Orientação sexual', key: 'latestSnapshot.sexual_orientation' },
      { label: 'Possui deficiência?', key: 'latestSnapshot.has_disability' },
      { label: 'Gênero', key: 'latestSnapshot.gender' },
    ],
  },
  {
    title: 'Campos Extras e Documentos',
    fields: [
      { label: 'Campos extras', key: 'extra_fields' },
    ],
  },
]