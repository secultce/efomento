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
      { label: 'Endereço completo', key: 'agent.latest_snapshot.address' },
      { label: 'Macrorregião', key: 'agent.latest_snapshot.macrorregion' },
      { label: 'CEP', key: 'agent.latest_snapshot.postal_code' },
      { label: 'Município', key: 'agent.latest_snapshot.city' },
    ],
  },
  {
    title: 'Campos adicionais',
    fields: [
      { label: 'Nome completo do proponente', key: 'agent.name' },
      { label: 'Cargo do proponente', key: 'agent.director_position' },
      { label: 'Telefone do proponente', key: 'agent.director_phone' },
      { label: 'CPF do proponente', key: 'agent.cpf' },
      { label: 'E-mail do proponente', key: 'agent.latest_snapshot.email' },
      { label: 'E-mail secundário', key: 'agent.latest_snapshot.secondary_email' },
      { label: 'Telefone secundário', key: 'agent.latest_snapshot.secondary_phone' },
    ],
  },
  {
    title: 'Perfil socioeconômico',
    fields: [
      { label: 'Data de nascimento', key: 'agent.latest_snapshot.birth_date' },
      { label: 'Escolaridade', key: 'agent.latest_snapshot.education' },
      { label: 'Raça / cor', key: 'agent.latest_snapshot.race' },
      { label: 'Orientação sexual', key: 'agent.latest_snapshot.sexual_orientation' },
      { label: 'Possui deficiência?', key: 'agent.latest_snapshot.has_disability' },
      { label: 'Gênero', key: 'agent.latest_snapshot.gender' },
    ],
  },
  {
    title: 'Campos Extras e Documentos',
    fields: [
      { label: 'Campos extras', key: 'extra_fields' },
    ],
  },
]