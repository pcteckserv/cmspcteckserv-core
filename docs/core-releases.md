# Publicação de versões do Core

A versão Composer do Core é obtida pela tag Git (`vMAJOR.MINOR.PATCH`).
Não adicionar o campo `version` ao `composer.json`: uma versão diferente da
tag faz com que o Composer ignore essa release, embora o painel de atualizações
consiga detetar a tag.

Antes de publicar uma nova tag, validar o manifesto com
`composer validate --strict --no-check-publish` e executar os testes do Core,
incluindo `tests/Unit/CorePackageManifestTest.php`, através da aplicação de testes.

Se uma tag já publicada contiver um manifesto inválido, publicar a correção numa
nova versão PATCH. Não mover nem substituir tags existentes. A tag `v2.6.7`
contém `version: 2.6.6`; a correção deve ser distribuída numa versão posterior.

Após a publicação, voltar a verificar as versões disponíveis no painel antes de
atualizar o site. Alterar apenas a branch principal não corrige o manifesto
guardado numa tag anterior.
