# Perfis profissionais partilhados

O meu perfil inclui fotografia (JPG/PNG/WebP, até 2 MB) e apresentação em texto
(até 2000 caracteres). Estes dados podem ser públicos na Agenda, conforme
informação apresentada no formulário. A alteração continua a exigir a
palavra-passe atual; apenas o utilizador autenticado é atualizado.

O Core guarda os dados em `cms_user_profiles`, identificados pelo ID do utilizador
CRM, sem exigir colunas específicas no modelo de utilizadores de cada site.
Outros plugins podem consultar `Pcteckserv\CmsCore\Models\UserProfile` por
`user_id` e usar `avatarUrl()` e `biography`. O ficheiro é guardado no disco
`public`, em `staff-avatars`, com nome aleatório. É necessário o habitual
`php artisan storage:link`. Remover/substituir a fotografia remove o ficheiro
anterior pertencente a esta área. SVG não é aceite.

A Agenda 0.4.0 usa este perfil nos cartões de escolha do profissional e filtra
quem realiza o serviço escolhido. As apresentações existentes são preservadas
pela migration da Agenda, sem substituir perfis já preenchidos. Agendas sem
utilizador mantêm uma apresentação própria da empresa.

Executar primeiro as migrations do Core (requer 2.13) e depois as da Agenda.
Não executar rollback do Core enquanto existirem plugins que utilizam os perfis.

Testes: UserProfilesTest valida a palavra-passe, armazenamento, remoção e rejeição
de SVG. AgendaTest valida a migração idempotente, reutilização do perfil e
exclusão de contas/agendas privadas. O navegador valida a filtragem por serviço,
a seleção do profissional e a disposição em mobile.