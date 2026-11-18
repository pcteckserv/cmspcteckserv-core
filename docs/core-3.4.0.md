# Core 3.4.0 — Aviso de autenticação de dois fatores

O Dashboard apresenta um aviso aos utilizadores com role `core.admin` e ao superadmin enquanto a autenticação de dois fatores não estiver confirmada. A ação abre `/admin/profile#two-factor-title`, diretamente na configuração existente.

Uma configuração iniciada mas ainda não confirmada mantém o aviso. A ativação confirmada remove-o. O aviso não apresenta segredos, códigos QR ou códigos de recuperação e não altera o processo de autenticação, as permissões ou as contas dos utilizadores. As restantes roles não recebem o aviso de administrador.

Sem novas dependências ou migrations. O Core continua a fornecer a identidade e segurança dos funcionários; os Starters reutilizam esta implementação.
