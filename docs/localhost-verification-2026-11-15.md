# Verificação HTTP dos quatro sites em localhost

Data: 15/11/2026. Complementa a [auditoria de compatibilidade](compatibility-audit-2026-11-15.md).

Foram iniciadas quatro cópias isoladas, com as distribuições do Core verificadas
pelo respetivo manifesto. Os projetos originais e as instalações online não
foram modificados. Os 3020 ficheiros versionados dos quatro projetos conservaram
os hashes, os commits e o estado Git anteriores.

| Site | URL local | Core | Plugins ativos no ensaio |
| --- | --- | --- | --- |
| CGLimpezas | http://127.0.0.1:8311 | 3.1.7 | Formulários de contacto, Portfólio |
| MaxiHome | http://127.0.0.1:8312 | 3.1.7 | Clientes, Imobiliário, Agenda, Blog, Loja, AI, Easypay, Moloni, PayPal |
| StudioFranco | http://127.0.0.1:8313 | 2.9.1 | Formulários de contacto, Portfólio |
| Turkesa | http://127.0.0.1:8314 | 3.1.7 | Clientes, Loja, Formulários de contacto |

O StudioFranco permanece na linha Core 2. Não foi efetuada uma migração artificial
para Core 3. O código e as versões históricas dos plugins do Turkesa continuam
sujeitos aos limites de reconstrução descritos na auditoria anterior.

## Preparação

Cada processo PHP foi ligado apenas a `127.0.0.1`, com porta própria, base SQLite
descartável, pasta de sessões própria e nome de cookie distinto. Usar apenas
portas diferentes não isola os cookies no navegador: os cookies pertencem ao
host e ao caminho, não à porta.

Não foram lidos os `.env` dos projetos. As instâncias usam uma chave e um
administrador sintéticos, email em memória e nenhum serviço de pagamento,
faturação ou email de produção. O inventário inicial dos plugins foi preparado
na base descartável para reproduzir o estado que o instalador do painel criaria.
Os dados e conteúdos desta demonstração não são uma cópia de produção.

As cópias e ferramentas de preparação ficam no workspace privado em
`.tools/platform-sites` e `.tools/local-platform-browser`; não são distribuídas
como alterações dos Starters nem como ficheiros para copiar para produção.

## Resultados

Foram executados **149 pedidos HTTP**, todos com resultado esperado:

- 137 respostas 200 para páginas e áreas acessíveis.
- 8 respostas 302 para login bem-sucedido ou proteção de administração sem sessão.
- 4 respostas 419 para tentativas de login sem token CSRF.

Os testes fizeram login através do formulário normal, com cookies e token CSRF;
não foi desativada a autenticação, a proteção CSRF ou outro middleware. Incluíram
as páginas iniciais e de contactos, o catálogo de imóveis e análise de mercado
do MaxiHome, administração de utilizadores e perfis, opções gerais, media, SEO,
consentimentos, manutenção, SMTP, filas, atividade, backups, sitemap e robots.
Foram também abertas por HTTP as páginas principais dos plugins ativos e as
páginas públicas com sessão administrativa.

Passaram ainda **49 pedidos de assets locais** referidos nas páginas iniciais e
de login: imagens, estilos e scripts. Não foram consultados assets de terceiros.

Na exploração inicial, `/admin/realstate` e `/admin/profile` no Core 2 devolveram
404. A primeira não é a rota de imóveis do plugin (é
`/admin/realstate/properties`); a segunda não pertence à linha 2.9. O conjunto
final utiliza as rotas existentes, sem criar aliases para ocultar diferenças.

Resumo por site: [relatório JSON](verification/localhost-2026-11-15.json).

## Limites e publicação

O acesso direto ao navegador estava indisponível. A tentativa de controlo do
Chrome foi interrompida pela ferramenta por não conseguir confirmar o URL ativo
com segurança. Não houve mais ações automáticas no navegador nem validação
visual ou responsiva nesta passagem. Os testes HTTP não executam JavaScript e
não substituem essa revisão visual.

Não foram realizados pagamentos, emissão de documentos, atualizações Composer
online, remoções de plugins ou operações de backup/queue: foram verificadas as
páginas de administração dessas funcionalidades. Não se conclui que todos os
percursos dos sites ou dos plugins estejam certificados.

Não foi encontrada uma nova falha funcional do Core nos pedidos executados.
Por isso, mantêm-se as versões estáveis **v3.1.7** e **v2.9.1**, sem uma atualização
de produção motivada apenas por este relatório. O marco de testes é identificado
pela tag **audit-localhost-2026-11-15**, que não é uma versão Composer.
