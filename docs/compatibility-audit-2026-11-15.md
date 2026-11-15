# Auditoria de compatibilidade — 15/11/2026

## Âmbito e decisão

As alterações desta entrega pertencem ao Core. Não foram alterados os projetos
CGLimpezas, MaxiHome, StudioFranco (`studioranco`) e Turkesa, os seus vendors,
ficheiros `.env`, configurações ou bases de dados. Os ensaios utilizaram cópias
privadas, credenciais sintéticas e bases de dados descartáveis.

Existem duas linhas: **Core 3.1.7** para projetos Core 3 e **Core 2.9.1**, no ramo
`maintenance/2.9`, para a manutenção do StudioFranco. Uma tag Core 3 não é uma
migração de Core 2. Não mover nem reutilizar tags anteriores.

## Correções do Core 3

- A conversão de origens Composer `path` para ZIP elimina `transport-options`
  próprios de pastas locais. O Composer não os deve interpretar como opções HTTP.
- Instalar um plugin usa dependências limitadas e alterações mínimas. Testes com
  Composer real confirmam que Core e Laravel existentes não são atualizados;
  um conflito de versão é recusado preservando a instalação.
- Um catálogo sem repositório configurado prepara uma pasta vazia, evitando erro
  500 numa instalação nova.
- A inserção dos avisos de cookies, administração e manutenção preserva a view
  original da resposta Laravel.
- A reversão da migration de hashes SEO remove os índices antes das colunas.
  A alteração não executa uma nova migration nos sites já instalados.
- As distribuições novas incluem `core-release.json`. O atualizador valida os
  ficheiros preparados antes de trocar o vendor. Ficheiros em falta, alterados
  ou de outra versão interrompem a operação com a instalação anterior preservada.

O manifesto deteta distribuições incompletas ou misturadas; não é uma assinatura
criptográfica do autor. Os hashes de texto normalizam CRLF para LF, mantendo a
verificação entre Windows e Linux; ficheiros binários usam SHA256 exato.

## Matriz dos projetos

| Projeto | Origem local auditada | Decisão |
| --- | --- | --- |
| CGLimpezas | Core 3.0.1; Laravel 13.36; plugins com diferenças entre ficheiros e metadados | Core 3.1.7 ensaiado em cópia; preservar versões realmente instaladas, não repor cegamente o lock |
| MaxiHome | Core snapshot 3.1.2; Laravel 13.36 | Compatível nos ensaios; atualização por simples renomeação do snapshot continua bloqueada |
| StudioFranco | Core 2.9.0; Formulários 1.3.3 e Portfólio 1.13.5 exigem Core 2 | Manter linha 2.9; Core 3 exige migração do Starter e plugins |
| Turkesa | Lock Core 3.1.1; Laravel 13.42; sem vendor local | Ensaios com artefactos históricos reconstruídos; não equivalem à confirmação dos ficheiros online |

No CGLimpezas, sete plugins contêm código com requisito Core `^3.1`, apesar de a
instalação declarar Core 3.0.1. Há também versões de código diferentes das
declaradas. A aprovação das rotas e dos módulos ativos não certifica todos os
módulos inativos dessa distribuição.

O repositório antigo declarado pelo StudioFranco não esteve acessível nesta
sessão. A tag 2.9.1 é publicada no repositório atual do Core; isso não corrige
automaticamente a associação antiga do projeto. A linha 2.9 deve ser selecionada
explicitamente: a restrição genérica `2.*` também aceita outras versões 2.x.

## Ensaios e limites

A suite independente do Core tem uma aplicação Laravel mínima em `tests/Host`;
não depende de MaxiHome, Clientes, Agenda ou de outro plugin privado. Foram
executados testes completos em PHP 8.3, 8.4 e 8.5 com SQLite, e em PHP 8.4 com
MariaDB 11.4: **268 testes aprovados e 1349 asserções em cada uma das quatro
execuções**, com falhas, warnings, notices e deprecações tratados como erros.

Também foram executados os oito testes JavaScript, lint PHP, validação de
integridade, migração incremental de Core 3.0.1 para 3.1.7 com preservação de um
utilizador e segunda execução idempotente, e ensaios Composer reais de instalação,
atualização, recusa, remoção e reposição de ficheiros.

Nas cópias dos quatro projetos, passaram os testes comuns de página inicial,
login e acesso autenticado a dashboard, atualizações, plugins, utilizadores,
perfis e opções gerais. A suite do CGLimpezas aprovou 129 testes, com 12 exclusões
que exigem a configuração MySQL específica do projeto. A suite StudioFranco
aprovou 98 testes após a correção Core 2.9 e a reconstrução das origens locais
necessárias aos testes de deploy.

No MaxiHome, 500 de 501 testes passaram tanto antes como depois: o teste restante
fixa versões antigas do catálogo, incluindo Core 3.1.2. Não foram alterados os
testes do projeto para ocultar essa divergência.

No Turkesa, a suite completa **não está integralmente aprovada**. A reconstrução
recuperou Formulários 2.0.0 e Clientes 1.4.1 de commits locais históricos. Para
Loja 0.48.0 só foi encontrado código com requisito Core `^3.0.0`, enquanto o lock
declara `^3.1.0`: não é possível certificar que seja o mesmo artefacto de produção.
Há testes de formulários/conta/email dependentes desses plugins, mocks do catálogo
que não fornecem `composer.json` nem admitem a criação da cache, e expectativas sobre caminhos de desenvolvimento.
As rotas comuns passaram; o teste de API de geografia da Loja também é verificado.
Na comparação com assets compilados, o Core 3.1.1 aprovou 535/550 testes
(3 falhas, 1 erro, 11 exclusões). O candidato aprovou 532/550
(3 falhas, 4 erros, 11 exclusões): os três erros adicionais correspondem aos
mocks antigos do catálogo. Não foram modificados os testes do site para os
adaptar às novas leituras de compatibilidade. Estes limites impedem declarar
uma certificação integral desse site.

A troca de um vendor completo no alojamento Linux não foi executada nesta
auditoria. Os testes de transação usam árvores Composer isoladas; a limitação
Windows descrita na entrega 3.1.6 mantém-se. Não foram feitas atualizações online,
testes de pagamento/faturação reais ou chamadas com credenciais de clientes.

## Publicação e instalações antigas

Publicar uma tag não substitui o atualizador que já corre num alojamento. O
CGLimpezas 0.0.8 em Core 3.0.1 ainda usa a ponte 3.1.6 de `bootstrap/updates`;
a nova conversão ZIP não chega a essa ponte apenas por existir uma tag nova.
Essa passagem requer uma entrega controlada do atualizador, validada no ambiente
de destino. Não misturar ficheiros de versões diferentes nem editar apenas os
metadados para anunciar uma atualização que não foi instalada.

Core 2, snapshots, restrições incompatíveis, distribuições incompletas e origens
indisponíveis exigem tratamento próprio. Nenhuma alteração nos projetos foi feita
para contornar estas proteções.

## Verificação para novos sites e próximas releases

1. Confirmar Core, PHP, Laravel, plugins e origem da distribuição efetiva.
2. Executar a suite independente e a suite do novo Starter numa cópia descartável.
3. Verificar atualização incremental com dados de teste e origens de deploy,
   incluindo ausência das pastas de desenvolvimento.
4. Gerar o manifesto após a última alteração: `php tools/build-release-manifest.php X.Y.Z`.
5. Executar `php tools/verify-release.php . X.Y.Z`, rever o diff e publicar uma tag nova.
6. Validar a distribuição no alojamento de teste antes de atualizar produção.

O workflow `.github/workflows/compatibility.yml` repete a suite independente em
PHP 8.3/8.4/8.5, SQLite e MySQL 8.4, além de JavaScript, lint e integridade.
O resultado local em MariaDB não substitui a execução dos jobs MySQL no GitHub.
A existência do workflow não configura, por si só, proteção de branches ou tags.

Diagnóstico só de leitura, sem carregar `.env`, Laravel ou bases de dados:

```sh
php vendor/pcteckserv/cms-core/tools/audit-installation.php /caminho/app 3.1.7
```

Este diagnóstico confirma restrições e ficheiros disponíveis. A propriedade
`automatic_update_allowed` indica elegibilidade técnica nessas verificações,
não uma garantia de deploy nem uma auditoria de todos os serviços externos.
