# Manutenção Core 2.9.2

Esta linha mantém o contrato do Core 2.9 usado pelo StudioFranco. Não instala
funcionalidades Core 3 nem altera as dependências declaradas do pacote.

A seleção de atualizações aceita apenas tags estáveis 2.9.x e ignora uma
candidata Core 3 anteriormente guardada. O repositório histórico conhecido é
mapeado para o repositório oficial. Os endereços personalizados são preservados.

A atualização Core passa pela transação genérica preparada fora da instalação:
congela as versões efetivamente instaladas do Laravel e dos plugins, recupera
plugins distribuídos em `packages`, reinstala apenas os pacotes alterados na
preparação e valida a integridade antes de trocar o vendor. Uma instalação Git
sem `.git`, comum em FTP, deixa de ser tratada como um checkout atualizável.
No Windows, prepara-se uma cópia adicional dos ficheiros de execução sem
metadados Git; essa cópia é validada antes da troca. Downloads temporários
`vendor/composer/tmp-<hash>` são excluídos da preparação, preservando o original.

## Instalações antigas

Core 2.9.0 não recebe esta proteção apenas porque existe uma tag nova. Exportar
a passagem FTP com:

```text
php tools/build-legacy-updater-hotfix.php /destino/studio.zip https://studiofranco.pt
```

Enviar os grupos pela ordem indicada no ZIP. O finalizador exige Core 2.9.0,
compara os ficheiros base e verifica se os ficheiros Laravel correspondem aos
metadados instalados. Valida também todos os ficheiros enviados, invalida as
caches necessárias e elimina-se. Não executa Composer, não altera a versão
declarada e não escreve `.env`, manifests Composer ou dados. O acesso privado
expira em 48 horas e fica apenas dentro do ZIP.

Só depois concluir a atualização 2.9.2 no painel. Uma tentativa anterior com
`--with-all-dependencies` pode ter deixado alterações parciais; esta passagem
não as reverte nem deve ser aplicada se a validação recusar o estado encontrado.

## Verificação

Os testes do atualizador usam Composer real, distribuições ZIP e Git locais,
Core 2.9.0 para 2.9.2, uma segunda atualização de manutenção, Laravel congelado,
plugins em `packages`, conflitos, remoção e reposição de ficheiros. Há testes
de seleção de tags, cache incompatível e passagem FTP com autoload antigo.
O caminho completo de um Composer executável é resolvido diretamente, sem o
procurar como nome em PATH; a validação de leitura e execução é preservada.
O caso foi reproduzido antes da correção e os seis testes de comandos passaram.

A suite histórica executada no host StudioFranco tem cinco falhas de referência:
três expectativas de formulários em `AdminAccessRulesTest`, uma versão instalada
do host em `InstalledPluginUpdatesTest` e um fallback em `SeoModuleTest`. Estas
falhas devem ser comparadas com a origem, não contadas como verificações aprovadas.
Falhas novas e verificações obrigatórias incompletas bloqueiam a publicação.

Os sites originais não são usados como instalações de teste. A reposição de
ficheiros não reverte migrações já executadas; manter backups de ficheiros e dados.
