# Backups — Core 3.2.0

## Configuração

Em Sistema → Backups, escolher primeiro FTP ou Google Drive. Só aparecem os
campos do destino selecionado. Os destinos local, FTPS, SFTP, S3 e R2 existentes
continuam disponíveis; guardar não muda automaticamente um destino antigo.

No FTP/FTPS, o CMS usa o servidor, porta, utilizador, palavra-passe e pasta do
formulário, através da extensão FTP do PHP. FTPS encripta o transporte, mas esta
extensão não valida certificados. Os destinos SFTP/S3/R2 continuam a usar o disco
Laravel configurado no alojamento. Testar a ligação depois de guardar.

No Google, criar um cliente OAuth Web no Google Cloud, ativar a Drive API e
configurar o URI de retorno indicado na página. Guardar ID e segredo e escolher
«Ligar com Google». A autorização pede apenas `drive.file`, acesso offline e
consentimento. O CMS cria uma pasta própria. Aplicações Google em modo de teste
podem ter autorizações com duração limitada; preparar a publicação do cliente
OAuth no Google Cloud para utilização contínua.

O retorno exige sessão administrativa, permissão `backups.configure`, estado de
utilização única ligado ao utilizador/destino e válido por dez minutos, e PKCE
S256. Cancelar o consentimento mantém a autorização anterior. Alterar as
credenciais exige nova ligação. Segredos e tokens são encriptados e não são
reapresentados no formulário nem guardados no old input em erros de validação.

Desligar a conta remove os tokens e suspende os planos remotos desse destino.
Os backups históricos não guardam cópias de tokens Google. Para voltar a
verificar/eliminar um backup Google, é necessário ligar uma conta autorizada
a aceder ao ficheiro original. A autorização também pode ser revogada nas
definições da conta Google.

## Conteúdo e restauro

- **Completo:** base de dados e ficheiros do projeto. Exclui `.env` (incluindo
  variantes), dependências, configuração Composer de autenticação, Git, cache,
  logs, backups anteriores e áreas de preparação de atualizações. O restauro
  exige repor a configuração secreta separadamente e reinstalar dependências
  a partir de composer.lock. Não é uma imagem do sistema operativo.
- **Apenas imagens:** extensões de imagem nas bibliotecas públicas/privadas e
  pastas de imagens configuradas; não inclui PDFs, outros documentos ou SQL.
- **Apenas base de dados:** SQLite consistente ou SQL de MySQL/MariaDB.
- Os tipos anteriores `files` e `media` continuam aceites para planos existentes.

`cms-backups.media_paths`, `full_included_paths` e os caminhos avançados permitem
adaptar bibliotecas locais. Não seguir symlinks nem sair da raiz do site.
Imagens que existam apenas por URL externa ou num serviço de media remoto não
são descarregadas automaticamente por este backup de ficheiros locais.

MySQL/MariaDB exige `mysqldump` ou `mariadb-dump` no servidor. Se necessário,
definir `CMS_BACKUPS_DATABASE_DUMP_BINARY`. As opções específicas do alojamento
ficam em `cms-backups.database_dump_options`; não são campos livres do painel.
As credenciais passam por um ficheiro temporário privado, eliminado após a
exportação, e não pela linha de comandos. A ausência/falha da ferramenta faz
falhar o backup: já não é criado um README em substituição da base de dados.

No modo remoto, a cópia local é removida após envio confirmado. Uma falha remota
conserva uma cópia local recuperável e não executa a retenção dos backups
anteriores. Verificar o histórico antes de eliminar cópias de recuperação.

## Retenção

Escolher quantas cópias concluídas conservar e, opcionalmente, um limite em dias.
Os limites aplicam-se por plano. O valor inicial dos novos planos é sete cópias;
os planos existentes mantêm os valores guardados até serem editados.

A limpeza corre após um backup bem-sucedido e através da manutenção agendada.
Mantém pelo menos a cópia concluída mais recente. Backups protegidos são
excluídos do limite; execuções falhadas com cópia de recuperação exigem revisão
e eliminação manual. Sem limites configurados, não elimina cópias.

A eliminação tenta primeiro o remoto e depois o local. Se falhar, mantém o
registo para nova tentativa. «Eliminar» no histórico elimina também os ficheiros,
não apenas a linha. As cópias FTP conservam o destino original num snapshot
encriptado, para não serem eliminadas noutro servidor após editar a ligação.
O Google usa o ID original do ficheiro e só elimina ficheiros marcados pelo CMS.

## Atualização e limites

A migration adiciona apenas colunas nullable de credenciais Google e snapshots;
não remove planos, destinos ou execuções. Mantêm-se as permissões existentes
`backups.view`, `backups.configure`, `backups.run`, `backups.verify` e
`backups.delete`, incluindo o acesso total do superadmin.

Agendamento e filas exigem o scheduler/worker já usados pelo CMS. Conservar a
APP_KEY em segurança para desencriptar configurações e snapshots após restauro.
Não foi ligada uma conta Google de produção: o protocolo foi ensaiado com HTTP
controlado, incluindo tokens, upload em blocos, cancelamento e falhas.

Referências oficiais: [OAuth Web](https://developers.google.com/identity/protocols/oauth2/web-server),
[envio de ficheiros](https://developers.google.com/workspace/drive/api/guides/manage-uploads).
