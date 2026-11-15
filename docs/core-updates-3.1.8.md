# Core 3.1.8 — preservar plugins distribuídos em packages

Corrige a preparação das atualizações e remoções em distribuições que executam
plugins a partir de `app/packages`, com `install-path` antigo ainda a apontar
para `app/vendor`. A falha foi reportada no CGLimpezas: o Core permaneceu antigo
e as duas operações paravam com «Não foi possível encontrar os ficheiros
instalados de pcteckserv/cms-contact-forms». O MaxiHome atualizou para 3.1.7 com
sucesso, segundo confirmação do utilizador.

## Causa e correção

O helper anterior só aceitava ficheiros dentro de `vendor/<package>`. O helper
genérico agora preserva o `install-path` antes de o remover dos metadados de
transporte e resolve-o relativamente a `vendor/composer`, conforme o formato
Composer. Se esse caminho já não existir ou estiver vazio, verifica as
localizações de distribuição `vendor/<package>` e `packages/<package>`.

Só são aceites pastas dentro da aplicação, com `composer.json` que identifica
exatamente o package escolhido. Uma localização Composer válida tem prioridade;
sem essa localização, duas cópias distintas são recusadas. Não há pesquisa pelo
disco nem recuperação a partir de `dist.url`, que pode apontar para código de
desenvolvimento diferente do instalado. Continuam a ser recusadas ligações
simbólicas dentro do conteúdo arquivado.

Mantêm-se versões congeladas dos restantes pacotes, preparação isolada,
verificação do Core preparado e tentativa de reposição dos ficheiros. A cópia
em `packages` não é apagada. A base de dados não é revertida automaticamente.

## Instalações que ainda executam a ponte antiga

Uma tag nova não substitui o atualizador já instalado. No CGLimpezas com
Core 3.0.1/3.1.5 e Starter 0.0.8, `SiteComposerTransaction` carrega a ponte 3.1.6
em `bootstrap/updates` enquanto o Core não disponibiliza essa classe.

O pacote FTP exportado por `tools/build-updater-bridge.php` contém **um único
ficheiro PHP**, cópia exata do novo `InstalledComposerSnapshot`, para o caminho:

```text
app/bootstrap/updates/InstalledComposerSnapshot.php
```

Guardar a cópia anterior, substituir apenas este ficheiro, verificar atualizações
no painel e atualizar o Core para **3.1.8**. Depois repetir a remoção do plugin
desativado. Não renomear versões nem substituir `composer.json`, `composer.lock`,
`.env` ou o vendor manualmente.

O Formulários 2.0.0 instalado no CGLimpezas exige Core `^3.1.0`; por isso, tentar
remover outro plugin mantendo Core 3.0.1 pode continuar a falhar na validação das
dependências. Atualizar o Core primeiro resolve essa incompatibilidade declarada.

A ponte não deve ser aplicada indiscriminadamente: se a classe já vier do Core
instalado, o ficheiro de bootstrap pode não ser utilizado. Essa situação exige
confirmar a origem carregada antes de preparar uma passagem própria.

## Verificação

Os testes reproduzem a ausência do plugin em vendor, o seu conteúdo em packages
e o install-path obsoleto. Composer real confirma atualização do Core e remoção
de outro plugin preservando conteúdo e restantes versões. Um teste separado usa
a implementação 3.1.6 da transação com o helper novo, cobrindo a ponte FTP.
Há também testes de localização Composer válida, vendor normal, pasta antiga
vazia, cópias ambíguas, identidade errada e origem fora da aplicação.

As suites completas em PHP 8.3, 8.4 e 8.5 com SQLite aprovaram **279 testes e
1382 asserções por execução**, tal como a execução PHP 8.4 com MariaDB 11.4.
Passaram os oito testes JavaScript, lint PHP,
validação Composer, integridade da distribuição e identidade do ficheiro exportado.
As execuções simultâneas no Windows reproduziram recusas de renomeação durante a
publicação, com reposição dos ficheiros; as execuções sequenciais passaram.
Não foi alterado o mecanismo de troca de diretórios nesta correção.

Não foram alterados os repositórios dos sites, os plugins ou o Core 2.9. A
correção só será instalada no servidor quando o utilizador enviar a ponte e
executar a atualização pelo painel. A cópia de um vendor completo no alojamento
não é substituída pelos ensaios Composer isolados.

Para gerar novamente o pacote a partir de uma distribuição íntegra:

```sh
php tools/build-updater-bridge.php /pasta-existente/ponte-core-3.1.8-ftp.zip
```
