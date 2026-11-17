# Validação de backups 3.2.0 — 17/11/2026

Alteração pedida: destino FTP/Google com campos condicionais, conteúdo completo,
imagens ou base de dados e retenção automática. Implementação na linha Core 3.
StudioFranco e Turkesa foram retirados desta matriz por indicação expressa do
utilizador; usam outro Core. Não houve instalação em produção.

## Referência e correções

O commit de referência é 39b6cc7 (3.1.12). Dois testes reproduziram a eliminação
das quatro cópias em vez de manter duas quando só existe limite de contagem e
a ausência de uma base SQLite restaurável no ZIP. O segundo ensaio usa retenção
de 30 dias para isolar a exportação da primeira falha. Ambos falham na referência
e passam na candidata. As verificações MariaDB também detetaram e corrigiram
a duração negativa de execuções, incompatível com a coluna unsigned.

## Matriz PHP

Hosts novos, dependências instaladas separadamente e bases descartáveis em
loopback. Composer real nos testes de instalação/atualização. MySQL foi ensaiado
com MariaDB 11.4.8, exclusivamente em bases `cms_core_test_*`. PHP 8.3/8.5 obtidos
das distribuições oficiais; PHP 8.4 local. Nenhum vendor original foi alterado.

| PHP | SQLite | MariaDB |
| --- | --- | --- |
| 8.3.36 | 314 testes completos + 22 testes finais afetados | 316 testes completos finais |
| 8.4.27 | 316 testes completos finais | 314 testes completos + 22 testes finais afetados |
| 8.5.12 | 314 testes completos + 22 testes finais afetados | 314 testes completos + 22 testes finais afetados |

Todas estas execuções passaram com `--fail-on-warning --fail-on-notice
--fail-on-deprecation`, sem ignorados. Os dois novos testes de eliminação HTTP
e desligar Google foram acrescentados após a primeira matriz; os 22 testes
afetados foram repetidos nos quatro perfis que tinham terminado a suite anterior.
Os perfis SQLite/8.4 e MariaDB/8.3 correram novamente a suite completa final.

Passaram os oito testes JavaScript existentes. O conteúdo SQLite e o SQL MariaDB
foram restaurados em bases descartáveis e consultados. Os cenários incluem
conteúdo distinto de imagens/completo, exclusão de segredos, falha remota sem
eliminar a cópia anterior, retenção por contagem, proteção e falha na eliminação.

OAuth ensaiado com respostas HTTP controladas: permissões, sessão, estado
inválido/expirado/repetido, PKCE, cancelamento, alteração de credenciais, tokens
encriptados, renovação, envio em blocos e recusa de URLs/ficheiros alheios.
Não foi usada uma conta Google de produção.

Um servidor FTP descartável confirmou criação de pastas, envio binário, tamanho,
download com SHA256 igual, eliminação e recusa de palavra-passe inválida. FTPS
não foi certificado contra um servidor TLS externo.

## Cópias dos sites

Foram exportados os ficheiros Git da MaxiHome 1.39.0 e CG Limpezas 0.0.11 para
pastas privadas, com bases SQLite vazias, administradores sintéticos, email em
memória e sessões separadas. O Core candidato foi instalado e os seus ficheiros
confirmados com o manifesto. As migrations e os builds de ambos os sites passaram.

As versões Laravel e dos plugins declaradas nos locks mantiveram-se. Só mudou o
Core nas cópias; na CG acrescentou-se composer/semver, exigido pelo Core atual.
Não foram lidos ambientes nem copiados dados de clientes das instalações originais.

Na CG, os fontes dos plugins foram reconstruídos a partir do histórico das
versões declaradas. Formulários e Portfólio têm `dev-main` e referências path
que não identificam commits recuperáveis; o ensaio usa explicitamente os fontes
1.3.4 e 1.14.1. Isto não certifica igualdade byte a byte com o vendor ou o site
online da CG. Os originais mantiveram Composer, lock, vendor, dados e HEAD.

Passaram 27 verificações GET de administração/plugins, além de página pública,
login, recusa de acesso sem sessão, CSRF 419, CSS/JS locais e seleção de destino
nas duas resoluções (1440 e 390 px). Os botões para guardar continuam visíveis
e não há deslocamento horizontal da página. As tabelas usam scroll próprio.

## Integridade e publicação

Lint dos PHP alterados, Composer validate e git diff --check aprovados.
Manifesto 3.2.0 regenerado e verificado, incluindo os ficheiros instalados nas
cópias. Verificar também os arquivos Git normalizado e habitual antes da tag.
O CI remoto deve ser identificado pelo seu resultado observado, separadamente
dos ensaios locais. Os registos detalhados permanecem no workspace privado em
`output/core-backups-*`, sem credenciais de produção.
