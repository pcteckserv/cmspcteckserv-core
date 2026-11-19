# Core 3.6.1 — escaping de dados estruturados

O componente SEO preserva Unicode/emojis, URLs e o objeto JSON-LD, escapando os caracteres HTML sensíveis. Texto com uma sequência de fecho de script deixa de conseguir terminar o elemento application/ld+json e introduzir HTML na página.

A falha foi reproduzida no Core 3.6.0 antes da correção. O teste de regressão verifica a ausência do fecho injetado e a recuperação exata do objeto por json_decode, incluindo emojis, aspas e ampersand. A correção é comum ao componente, sem alteração de API, base de dados, dependências ou regras de SEO. Não é uma alteração de versões Laravel/Symfony.

Atualização pelo painel, conservando ambiente, dados e conteúdos. Não há migrations novas do Core. O Imobiliário 0.51.0 requer este patch para a publicação segura dos textos. As matrizes e resultados efetivamente executados constam também no relatório de distribuição MaxiHome 1.47.0; uma publicação Git não confirma a instalação em produção.
