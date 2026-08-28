# Tarefas em segundo plano

O painel `Admin > Tarefas` monitoriza a queue Laravel usada pelo CMS Core e pelos módulos.

## Objetivo

Dar feedback operacional sobre jobs pendentes, em execução, falhados e batches, sem depender de uma solução específica de alojamento.

O painel é compatível com:

- workers manuais em localhost;
- workers mantidos por Supervisor em produção;
- queue `database`, usada por defeito no starter;
- outros drivers Laravel, com monitorização limitada quando não existe tabela `jobs`.

## Localhost

Durante desenvolvimento, deixa um terminal aberto com:

```bash
php artisan queue:work --queue=default --tries=3 --timeout=120
```

Se não quiseres manter um worker aberto, podes usar no painel:

- `Processar agora`: executa um ciclo da queue no pedido atual;
- `Reiniciar workers`: envia sinal para workers ativos reiniciarem depois do job atual.

## Produção com Supervisor

Em produção, o worker deve ser mantido por Supervisor. Exemplo de comando:

```bash
php /caminho/do/site/artisan queue:work --sleep=3 --tries=3 --timeout=120
```

Exemplo conceptual de configuração:

```ini
[program:cms-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /caminho/do/site/artisan queue:work --sleep=3 --tries=3 --timeout=120
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/caminho/do/site/storage/logs/worker.log
stopwaitsecs=3600
```

Depois de deploy:

```bash
php artisan queue:restart
```

## Permissões

O módulo regista:

- `queues.view`: ver o painel;
- `queues.manage`: executar ações operacionais.

## Jobs falhados

O painel permite:

- reprocessar um job falhado;
- reprocessar todos os falhados;
- remover um falhado da lista.

Estas ações usam os comandos Laravel nativos `queue:retry` e `queue:forget`.

## Limitações

Com drivers como Redis, SQS ou Beanstalkd, a contagem de pendentes pode não estar disponível através da base de dados. Nesses casos, o painel continua útil para falhas registadas, batches e comandos operacionais.
