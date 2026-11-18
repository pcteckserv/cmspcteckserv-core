# Core 3.5.1 — ligações Composer e inventário de plugins

Os diagnósticos de 18/11/2026 identificaram duas situações distintas:

- MaxiHome, Core 3.3.0: os pacotes Clientes e Imobiliário usam ligações de `vendor/pcteckserv` para os seus diretórios em `packages/runtime/plugins`, dentro da aplicação.
- CG Limpezas, Core 3.5.0: Agenda continuava no inventário administrativo como instalada, embora estivesse ausente do manifesto, lock, metadados e autoload Composer. Os restantes plugins desativados indicados pelo operador constam do Composer.

## Preparação de ficheiros

A transação reconhece apenas aliases de diretórios de pacotes declarados no Composer, dentro da mesma aplicação e com identidade confirmada no `composer.json` do destino. Materializa esses aliases em cópias independentes na preparação. Não publica ligações nem escreve nas origens dos plugins.

Ligações externas, identidades diferentes e ligações de diretórios internas não declaradas continuam recusadas. A mensagem identifica apenas o caminho relativo bloqueado. O tratamento de junctions no Windows usa o caminho canónico, porque `is_link` pode devolver false para uma junction. A instalação anterior é preservada no rollback.

## Inventário e remoção

O inventário desativado é reconciliado também quando o provider deixou de estar carregado. Uma ausência só é confirmada se os metadados instalados, o lock, os requisitos do projeto e o autoload concordarem. Inventários inválidos ou divergentes não autorizam limpeza. Restos de ficheiros são conservados.

Uma eliminação pedida para um registo confirmado como antigo limpa apenas o registo administrativo. Não executa Composer, migrations nem elimina tabelas, documentos ou ficheiros. Plugins ativos continuam protegidos.

Para pacotes instalados, a remoção preparada mantém as verificações de dependências e as versões dos outros pacotes. Desativar um plugin não elimina as suas dependências Composer; remover primeiro os dependentes indicados na mensagem.

## Instalações bloqueadas

MaxiHome necessita de uma nova passagem FTP para 3.5.1, com o exportador da transação corrigida. O pacote 3.5.0 anterior conserva a recusa das ligações e não deve ser reutilizado.

CG Limpezas pode atualizar o Core 3.5.0 para 3.5.1 pelo painel. A ponte temporária do seu Starter deve delegar no instalador central quando esta reconciliação estiver disponível, conservando o comportamento antigo na base local 3.0.1.

Os diagnósticos privados não são publicados no Git e podem ser apagados do alojamento depois de recolher o JSON. Nenhum endereço privado, chave ou dados pessoais são incluídos nesta documentação.
