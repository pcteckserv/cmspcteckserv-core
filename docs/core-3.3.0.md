# Core 3.3.0 — Ligação simplificada ao Google Drive

Em Backups, escolher Google Drive e carregar em **Ligar com Google** guarda a configuração e abre a escolha da conta e o consentimento no mesmo passo. A configuração técnica fica recolhida quando já existe uma aplicação válida. FTP e restantes destinos conservam os seus formulários.

O administrador prepara uma aplicação OAuth Web no Google Cloud uma única vez: ativa a Drive API, configura o consentimento e regista exatamente o endereço de retorno apresentado no painel. Pode guardar as credenciais no destino ou configurar `CMS_BACKUPS_GOOGLE_CLIENT_ID` e `CMS_BACKUPS_GOOGLE_CLIENT_SECRET` no alojamento. As credenciais explícitas têm prioridade; pares incompletos nunca são misturados. O segredo não é apresentado na página nem copiado para o destino quando provém do alojamento.

Depois, cada ligação pede a conta Google e a autorização. Não é necessário copiar códigos ou tokens manualmente. O CMS mantém estado, PKCE S256, expiração, identidade do operador, permissão `backups.configure`, CSRF e tokens cifrados. Usa o âmbito `drive.file`, limitado aos ficheiros da aplicação. A configuração inicial é um requisito do Google, não pode ser dispensada pelo CMS. Para backups contínuos, rever o estado de publicação da aplicação e as regras Google de expiração de tokens em aplicações em teste.

Ao voltar a ligar, confirma o acesso à pasta anterior antes de a reutilizar. Se essa pasta já não estiver acessível, cria uma nova pasta privada. Só grava a nova autorização depois de validar tokens e pasta, voltando a confirmar que as credenciais não mudaram durante o pedido.

Sem novas dependências, migrations ou alteração da linha de manutenção dos sites. A CG Limpezas original mantém a base atual. A atualização pode ser feita pelo mecanismo normal do Core 3, preservando Laravel e plugins.

## Verificações locais

Runtime `fb252e9`, instalado por Composer real em seis hosts novos a partir do arquivo Git canónico. PHP 8.3.36, 8.4.27 e 8.5.12 com SQLite e MariaDB 11.4.8: **323 testes e 1630 asserções em cada combinação**, sem falhas, avisos ou depreciações. Oito testes JavaScript aprovados. Lint dos ficheiros PHP alterados, incluindo templates, aprovado.

Os testes cobrem ligação numa ação com credenciais do alojamento, ausência de segredos na página, prioridade de credenciais, troca de conta com pasta inacessível e alteração de credenciais durante a autorização. As integrações externas usam respostas simuladas; não foi autorizada uma conta Google de produção.

Referências: [OAuth Web Server](https://developers.google.com/identity/protocols/oauth2/web-server) e [âmbitos Drive](https://developers.google.com/workspace/drive/api/guides/api-specific-auth).
