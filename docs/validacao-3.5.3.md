# Core 3.5.3 — Idiomas nas atualizações do Starter

A política central passa a reconhecer `pcteckserv/cms-languages` como plugin opcional. A adição ou remoção dessa declaração num Starter não substitui o Composer, o lock ou os módulos já instalados. Bibliotecas não autorizadas, alterações de autoload e linhas Core incompatíveis continuam a ser recusadas antes da cópia.

O erro reportado foi reproduzido na origem: a declaração de Idiomas provocava a mensagem «fora dos plugins opcionais». A regressão passou depois da correção e verifica adição, remoção e preservação dos ficheiros de dependências. A alteração funcional no Core limita-se à lista de plugins opcionais.

## Validação

- Matriz completa: PHP 8.3.36, 8.4.27 e 8.5.12, cada um com SQLite e MariaDB 11.4.8. **350 testes e 1804 asserções por combinação**, zero falhas, erros ou omitidos nas execuções finais. Host Laravel 13.43.0 novo e privado, Composer real e flags estritos de PHPUnit.
- JavaScript Core: oito testes aprovados. Lint dos PHP alterados e `git diff --check` aprovados. Manifesto regenerado e integridade confirmada no diretório de trabalho e no arquivo Git canónico.
- Os testes transacionais com Composer real cobrem atualizações sucessivas, snapshots, Git sem `.git`, ZIP, caminhos ausentes, aliases, conflitos, integridade inválida, remoção, modos dev/no-dev, falhas posteriores e reposição.
- MaxiHome/Laravel 13.36.0: instalação limpa, build Vite e suite completa aprovados, **563 testes e 6005 asserções**. Numa cópia da tag 1.43.2, o painel reproduziu a recusa, atualizou Core 3.5.1 → 3.5.3 e depois Starter 1.44.1, preservando ambiente, credenciais, clientes, opções, ficheiros e versões dos outros pacotes. O Starter conservou o Composer e não instalou Idiomas implicitamente.
- CG Limpezas/Laravel 13.36.0: cópia privada na linha Core 3, dependências restantes preservadas; público, login, convidado, CSRF, atualizações, catálogo, formulários, portefólios e assets aprovados. O ambiente, vendor e dados dos originais não foram utilizados nem alterados.

Comandos e relatórios locais em `output/core353-validation`: `run-matrix.cjs`, `retry-matrix.cjs`, XML `core{83,84,85}{sqlite,mysql}.xml`, `max-full-final.xml`, `panel-preserve-*.log` e `cg-browser-result.json`. As execuções paralelas iniciais sofreram pressão de memória e foram interrompidas; as combinações afetadas foram repetidas integralmente com concorrência reduzida. O primeiro catálogo sintético continha apenas Idiomas; foi substituído pelo catálogo completo e a suite MaxiHome foi repetida sem falhas.

O ensaio HTTP Windows usa um PHP privado com temporários graváveis e ambiente herdado, além de um router fora de `vendor`. O router de desenvolvimento dentro de `vendor` impedia a renomeação dessa pasta; a recusa preservou a instalação. Estas configurações pertencem exclusivamente aos ensaios e não são distribuídas para os sites.

## Distribuição e limites

Ficheiros Core alterados: `src/Updates/StarterDependencyPolicy.php`, `tests/Feature/StarterDependencyPolicyTest.php`, `core-release.json` e este relatório. Não há novas migrations nem mudanças à transação Composer. A correção do catálogo e a importação tardia pertencem à MaxiHome 1.44.1; o plugin Idiomas conserva a versão 0.1.1.

Atualizar primeiro o Core e depois o Starter MaxiHome. Instalar/ativar Idiomas separadamente no painel. Publicação Git não comprova instalação em produção. O CI remoto é observado separadamente; os ensaios não certificam outros alojamentos, versões principais ou todas as combinações futuras. A tradução automática continua a exigir configuração oficial e agendador no alojamento.
