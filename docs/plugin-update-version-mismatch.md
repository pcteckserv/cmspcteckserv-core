# Divergência entre versão Composer e manifesto instalado

## Caso reportado

Em 16/11/2026, o painel de CGLimpezas online apresentou Formulários de contacto
1.3.4 instalado e 2.0.0 disponível, mas a ação devolveu «Não existe uma atualização
disponível para este plugin.». O Core indicado na captura era 3.1.9.

Não foram consultados os dados ou ficheiros da instalação online. A causa exata
nessa instalação ainda depende de comparar o manifesto instalado, os metadados
Composer e os metadados do plugin.

## Defeito reproduzido no Core 3.1.9

O registo de atualizações usa `InstalledPluginVersionReader` nos plugins path,
mas `PackageUpdater` usava a versão Composer para decidir se havia atualização.
Um snapshot antigo com metadados Composer adiantados apresentava uma atualização
no painel e recusava a mesma ação antes de executar Composer.

O teste de regressão falhou na origem com a mensagem reportada. A correção usa
o manifesto instalado também na decisão do atualizador, mantendo o recurso à
versão Composer quando o manifesto não pode ser lido. A origem, compatibilidade
e confirmação dos ficheiros após instalação continuam a ser verificadas.

Um plugin que já tem os ficheiros da versão disponível não é reinstalado apenas
por ter uma versão Composer de desenvolvimento antiga.

## Validação e publicação

- Correção em cópia isolada; sites originais e instalação online preservados.
- 18 testes específicos aprovados, com 122 asserções, em PHP 8.4/SQLite.
- 8 testes JavaScript aprovados; lint dos PHP alterados e diff sem erros.
- Suite completa aprovada em PHP 8.4.27/SQLite: 294 testes, 1464 asserções,
  incluindo transações com Composer real; relatório em
  `output/contact-updater-tests.xml` no workspace.
- Integridade do manifesto 3.1.10 e do ZIP Git canónico LF aprovada.
- Permanecem por validar a restante matriz de runtimes/bases de dados e as
  cópias dos sites. Estes resultados não certificam PHP 8.3/8.5 ou MySQL.
- Após ser informado de que faltavam validações, o utilizador pediu
  expressamente «tagar e enviar para testar online». A publicação 3.1.10
  avança nesse âmbito de teste por instrução do utilizador, com estes limites
  documentados; não é apresentada como aprovação da matriz completa.
- Atualizar primeiro o Core pelo painel e depois voltar a verificar e atualizar
  o plugin. Não se alteraram os ficheiros nem os dados do site online.

Formulários de contacto 2.0.0 exige Core ^3.1.0. A disponibilidade depende ainda
de o plugin estar instalado e ativo e de a origem configurada estar acessível.

## Seguimento: destino antigo em cache após reinstalação

Após instalar Core 3.1.10, foi reportada a mensagem «A reinstalação terminou,
mas os ficheiros instalados não correspondem à versão disponível (instalada:
1.3.4; disponível: 2.0.0).».

Um novo teste com Composer real reproduziu exatamente esta mensagem quando a
instalação inicial é uma ligação simbólica/junction. A reinstalação altera o
destino físico, mas o processo PHP do pedido conserva o destino antigo na cache
realpath. Ler novamente o JSON sem limpar essa cache consulta ainda a origem
1.3.4, embora Composer já tenha trocado a ligação.

A correção limpa a cache de stat e realpath antes de consultar o manifesto
instalado. Não substitui a versão lida por metadados da origem e não suprime a
verificação de ficheiros. Mantém a recusa quando a versão física continua antiga.

Os novos testes de integração cobrem instalação por cópia, snapshot ZIP preservado
pelo Core e ligação simbólica. Todos usam Composer real em pastas descartáveis,
confirmam o ficheiro marcador 2.0.0 instalado e preservam o marcador 1.3.4 da cópia
antiga. Nenhum dado ou ficheiro do site online foi consultado ou alterado.

### Core 3.1.11 — resultados

- Antes da correção, o teste de ligação simbólica falhou com a mensagem reportada
  (1.3.4 instalada, 2.0.0 disponível); os dois cenários sem ligação passaram.
- Após a correção: 23 testes específicos e 143 asserções aprovados.
- Suite completa: 297 testes e 1482 asserções aprovados em PHP 8.4.27/SQLite,
  incluindo os três novos percursos com Composer real. Relatório no workspace:
  `output/contact-updater-3.1.11-tests.xml`.
- Lint dos PHP alterados, diff e integridade 3.1.11 aprovados, tanto na cópia de
  trabalho como no arquivo Git canónico exportado.
- Publicação para o seguimento do teste online já solicitado pelo utilizador;
  mantêm-se os limites da matriz ainda não validada em PHP 8.3/8.5, MySQL e
  cópias de todos os sites. Não se apresenta como aprovação desses ambientes.
- Atualizar o Core para 3.1.11 e voltar a atualizar o plugin pelo painel.

## Diagnóstico efetivo do alojamento e Core 3.1.12

O relatório fornecido pelo utilizador após Core 3.1.11 confirmou outro estado:
Composer instalado declara 2.0.0 num ZIP preservado; o lock declara 2.0.0 por
path; ambos têm a mesma referência. Os manifestos em vendor e packages contêm
efetivamente 1.3.4, enquanto a origem em cache contém 2.0.0. Portanto, neste
alojamento a divergência é física, não apenas cache realpath.

O novo teste de regressão reproduz esta combinação com Composer real: instala
um ZIP com manifesto 1.3.4 e metadados 2.0.0, prepara um lock para path sem
instalar e iguala a referência dos metadados à do lock. Antes da correção,
a atualização e a reinstalação terminam com a mesma mensagem 1.3.4/2.0.0.

A configuração da origem path passa a usar `reference: config` e `symlink:
false`. A referência é calculada pelo Composer a partir do manifesto/configuração
da origem, distinguindo-a do ZIP preservado com referência coincidente. Os
ficheiros são instalados por cópia e a verificação física posterior continua
obrigatória. Não se altera apenas a versão declarada nem se elimina a cópia
antiga em packages.

O teste confirma explicitamente os tipos ZIP/path e a referência coincidente
antes da atualização; depois verifica o marcador e versão física 2.0.0, a origem
path instalada, ausência de ligação simbólica e preservação do marcador 1.3.4
na cópia antiga. Também são mantidos os testes de cópia normal, ZIP e junction.

A publicação de seguimento mantém o âmbito autorizado de teste online e os
limites da matriz de compatibilidade documentados acima. O site online não é
alterado diretamente por este trabalho.

Validação final 3.1.12: suite completa aprovada em PHP 8.4.27/SQLite, com
298 testes e 1501 asserções, incluindo o novo caso com versão/referência iguais
e origens ZIP/path divergentes. Relatório: `output/contact-updater-3.1.12-tests.xml`.
Lint, diff e integridade da distribuição e do ZIP Git canónico aprovados.
Mantêm-se os limites de ambientes não testados referidos acima.
