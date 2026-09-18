# Versões dos plugins

Os plugins do repositório partilhado definem a versão individual em `cms-plugin.json`:

```json
{
    "package": "pcteckserv/cms-contact-forms",
    "version": "1.0.2"
}
```

Incrementar `version` em cada publicação com alterações ao plugin. O CMS obtém
o código da branch configurada no GitHub e lê essa versão, sem consultar tags Git.
Depois de concluir a instalação ou atualização, guarda a versão nos metadados
do plugin e apresenta-a na gestão de plugins e na página de atualizações.
Falhas não devem avançar a versão registada.

O Composer continua a instalar a package através de `path`, podendo mostrar
`dev-main` na linha de comandos. A versão apresentada pelo CMS corresponde aos
metadados publicados; não representa uma release Composer nem um checkout de tag.
As versões do Core continuam distribuídas através das respetivas tags Git.
