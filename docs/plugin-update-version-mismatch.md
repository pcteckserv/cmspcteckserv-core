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
- Permanecem as restantes verificações obrigatórias de publicação: matriz de
  runtimes/bases de dados, integridade da futura versão e cópias dos sites.
  Não criar versão, tag ou push antes de as concluir.
- Esta alteração ainda não constitui uma versão publicada.

Formulários de contacto 2.0.0 exige Core ^3.1.0. A disponibilidade depende ainda
de o plugin estar instalado e ativo e de a origem configurada estar acessível.
