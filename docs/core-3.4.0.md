# Core 3.4.0 — Aviso de autenticação de dois fatores

O Dashboard apresenta um aviso aos utilizadores com role `core.admin` e ao superadmin enquanto a autenticação de dois fatores não estiver confirmada. A ação abre `/admin/profile#two-factor-title`, diretamente na configuração existente.

Uma configuração iniciada mas ainda não confirmada mantém o aviso. A ativação confirmada remove-o. O aviso não apresenta segredos, códigos QR ou códigos de recuperação e não altera o processo de autenticação, as permissões ou as contas dos utilizadores. As restantes roles não recebem o aviso de administrador.

Sem novas dependências ou migrations. O Core continua a fornecer a identidade e segurança dos funcionários; os Starters reutilizam esta implementação.
## Validação

Runtime `f7c449b`, instalado com Composer real em seis hosts novos a partir do arquivo Git canónico. PHP 8.3.36, 8.4.27 e 8.5.12 com SQLite e MariaDB 11.4.8: **327 testes e 1643 asserções por combinação**, sem falhas, avisos ou depreciações. Oito testes JavaScript aprovados. Integridade válida no diretório, no arquivo Git canónico LF e na exportação normal do Windows.

Os quatro testes específicos cobrem administradores e superadmin, configuração iniciada sem confirmação, confirmação com sessão verificada e utilizadores de outras roles. Nenhuma conta real foi alterada.

MaxiHome: suite PHP completa com 549 testes e 5746 asserções; 18 testes JavaScript e 368 templates compilados sem erros. CG Limpezas: 143 testes, com 131 aprovados, 12 ignorados e 738 asserções. Os originais da CG Limpezas conservaram HEAD, ficheiros Composer e Core instalado. As cópias passaram página pública, login, CSRF, redirecionamento do visitante, Atualizações, Plugins, Backups, rotas dos plugins e assets.

O navegador confirmou as ações novas e o aviso de 2FA em 1440 e 390 px. O ensaio geral de navegador não constitui uma aprovação integral: foram reproduzidas na MaxiHome publicada `289c851` falhas anteriores nos testes de agenda, contactos, documentos e cabeçalho de leads. A alteração de nome acessível do ícone de cliente foi acompanhada no teste respetivo e validada em dispositivo móvel. Os testes PHP completos e a matriz Core não dependem dessas falhas de referência de navegador.
