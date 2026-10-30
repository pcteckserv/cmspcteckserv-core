# Avisos extensíveis no dashboard · 2.12.0

A view `cms-core::admin.dashboard` aceita `dashboardNotices`, uma lista de nomes de
views registadas no servidor. Cada view é incluída antes do conteúdo do dashboard.
Por omissão, a lista está vazia e o painel conserva o comportamento anterior.

Um módulo pode acrescentar avisos através de um view composer. Deve preservar os
avisos já existentes e verificar a autorização do operador antes de acrescentar
conteúdo ou ligações administrativas. Não aceitar nomes de views do request.

O Core não conhece os módulos que utilizam este ponto de extensão. O conteúdo e a
condição de apresentação pertencem a cada módulo. Templates publicados que
substituam o dashboard devem preservar este ponto de extensão.

Esta alteração prepara a versão minor 2.12.0. A distribuição da Loja 0.21.0 deve
acompanhar a publicação desta versão do Core pelo procedimento normal de tags Git.
