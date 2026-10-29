# Autenticação de dois fatores

Cada conta pode ativar o 2FA em **Os meus dados → Autenticação de dois fatores**.
A funcionalidade utiliza TOTP através de `pragmarx/google2fa` e gera o QR
localmente com `bacon/bacon-qr-code`, sem enviar a chave para serviços externos.
É compatível com Google Authenticator e outras aplicações TOTP.

## Configuração

1. Indicar a palavra-passe atual e selecionar **Configurar autenticação de dois fatores**.
2. Ler o QR na aplicação ou introduzir a chave manualmente.
3. Confirmar com a palavra-passe atual e o código de seis algarismos da aplicação.
4. Guardar os oito códigos de recuperação apresentados. Cada código é válido uma
   única vez e não pode voltar a ser consultado.

A configuração pendente expira em 10 minutos e pertence à sessão que a iniciou.
O 2FA só fica ativo depois de validar o primeiro código. Um código TOTP já
utilizado, incluindo o da ativação, não pode voltar a ser usado: aguardar o
código seguinte apresentado pela aplicação.

Depois da ativação, o login só termina após validar um código da aplicação ou
um código de recuperação. O desafio expira em cinco minutos. Sessões anteriores
e cookies de autenticação persistente também têm de validar o segundo fator
antes de acederem ao `/admin`.

Gerar novos códigos de recuperação ou desativar o 2FA exige a palavra-passe
atual e um código válido. Gerar novos códigos invalida todos os anteriores.
O acesso aplica-se apenas à própria conta, independentemente das permissões de
gestão de utilizadores.

## Instalação e atualização

Após atualizar a package Composer na aplicação que utiliza o Core, executar:

```sh
php artisan migrate --force
```

A migration cria `cms_two_factor_authentications`, sem alterar a tabela de
utilizadores. É necessária a extensão PHP `xmlwriter`, declarada no manifesto.
O relógio do servidor deve estar sincronizado; os códigos usam períodos de
30 segundos e uma tolerância de um período para cada lado.

As chaves são encriptadas com o mecanismo Laravel e os códigos de recuperação
são guardados apenas como hashes. Preservar a `APP_KEY` na estratégia existente
de backup e recuperação: as chaves encriptadas dependem dela. As páginas de
configuração e recuperação utilizam `Cache-Control: no-store, private`.

As rotas de configuração têm autenticação, CSRF e limite de cinco pedidos por
minuto por conta. O desafio admite cinco tentativas por minuto, com limites
independentes por conta e por IP. O login admite dez pedidos por minuto por IP.
A ativação, desativação e regeneração de códigos são auditadas sem registar
chaves, palavras-passe ou códigos. A eliminação definitiva da conta remove a
configuração 2FA na mesma transação.

## Validação

`tests/Feature/TwoFactorAuthenticationTest.php` cobre a configuração, expiração,
isolamento de contas e sessões, login, recuperação, reutilização de códigos,
cookies persistentes, CSRF, limitação de tentativas e credenciais inválidas.

Também executar os testes de perfil, auditoria e eliminação de utilizadores,
seguidos da suite completa do Core, através da aplicação de testes habitual.

Referências: [Google2FA](https://github.com/antonioribeiro/google2fa) e
[BaconQrCode](https://github.com/Bacon/BaconQrCode).
