# Core 3.5.0 — atualizações, auditoria e ligação Google

## Passagem de instalações existentes

Um Core instalado a partir de uma pasta do Starter pode passar para uma versão publicada, dentro da mesma versão principal. O atualizador prepara Composer numa pasta privada, congela os outros pacotes instalados, valida a distribuição e troca os ficheiros. Não altera apenas a versão declarada. A candidata tem de incluir um manifesto de integridade. Mantêm-se as recusas de incompatibilidade, ficheiros alterados e mudanças de linha principal.

O Starter valida o requisito Composer de Core, conservando o Composer, o lock e o vendor do site. O lock da distribuição identifica a linha principal; não impõe a sua versão exata como mínimo. Uma referência histórica exata é interpretada como mínimo da mesma linha principal para esta cópia de ficheiros, sem reescrever o requisito instalado. Novas bibliotecas, mudanças de autoload ou requisitos incompatíveis continuam a exigir publicação preparada.

Os atualizadores anteriores não conseguem instalar esta correção quando já estão bloqueados. `tools/build-snapshot-core-bootstrap.php <novo.zip> <maxihome|cglimpezas> <composer.phar> 3.5.0` gera uma passagem privada por site, com chave aleatória e validade de 48 horas. O envio FTP não substitui ficheiros do Core. O operador abre o endereço completo incluído no ZIP e confirma a execução da transação genérica. Não publicar ZIPs, chaves ou URLs privadas no Git.

Se uma distribuição antiga tiver ficheiros diferentes do seu manifesto, o pacote pode repor primeiro a distribuição canónica dessa mesma versão, a partir dos arquivos Git incluídos e verificados. A pasta anterior é preservada. Versões não reconhecidas são recusadas. A transação seguinte conserva as versões do Laravel e dos plugins. Migrations que já tenham sido executadas não são revertidas automaticamente. Conservar os backups até confirmar o funcionamento.

A CG Limpezas mantém a base local Core 3.0.1. A sua ponte temporária exporta código genérico e usa a implementação central assim que instalada; não migra a instalação original durante o desenvolvimento. MaxiHome e o Starter inicial passam a exigir Core 3.5 para as distribuições novas.

## Administração comum

`admin/updates` apresenta três secções: Core, Starter e plugins ativos instalados. O ecrã central tem uma view própria para permitir substituir overrides históricos sem copiar a implementação para novos sites. O backport da CG é uma exportação exata, apenas enquanto a base anterior não tiver esta view.

Os logs apresentam, por defeito, o período entre a mesma data do mês anterior e hoje, segundo o fuso administrativo. Os filtros, CSV e paginação conservam esse período; datas explícitas permitem consultar períodos anteriores. A paginação usa Bootstrap. A retenção padrão passa a 90 dias, com `cms-core:activity-logs:prune` diário às 03:45, no fuso administrativo. A configuração explícita existente continua a prevalecer; null/vazio desativa a limpeza. A operação elimina apenas entradas de auditoria anteriores ao limite, em lotes de 1000.

O alojamento tem de executar `php artisan schedule:run` a cada minuto. O código não instala o cron do servidor. A limpeza de auditoria não elimina documentos, imagens, contas nem backups.

## Google Drive

Depois da configuração inicial da aplicação OAuth, a operação normal é escolher Google Drive, guardar o destino e carregar em Ligar ao Google. Os campos técnicos ficam em opções avançadas. O fluxo usa estado ligado ao operador e à sessão, validade de dez minutos, PKCE e validação das credenciais/destino antes de guardar a ligação cifrada. Os retornos antigos continuam válidos para autorizações iniciadas antes da atualização.

Registar uma vez, como URIs de redirecionamento autorizados da aplicação Google existente:

- `https://maxihome.pt/admin/backups/google/callback`
- `https://cglimpezas.pt/admin/backups/google/callback`

Configurar Client ID e Client Secret no painel avançado ou na configuração do alojamento. Nunca enviar segredos por chat nem colocá-los em repositórios. As permissões Google e a configuração OAuth inicial não podem ser dispensadas pelo CMS. Documentação oficial: <https://developers.google.com/identity/protocols/oauth2/web-server>.

## Validação

Os relatórios da candidata identificam os testes de Composer real, integridade, rollback, FTP e matriz PHP/base de dados. A publicação exige os checks definidos no AGENTS.md; ensaios locais não certificam credenciais Google, cron do alojamento ou instalação online.
