# Portal MMI (Laravel)

Portal corporativo da MMI Incorporações para acesso controlado a relatórios de BI.
Esta é a versão em **Laravel 13 / PHP 8.4** do portal original em Django
(`D:\PortalMMI`), com as mesmas telas, regras de acesso e o **mesmo banco de dados**.

## O que foi preservado da versão Django

- **Mesmo esquema de banco**: os models usam as tabelas existentes
  (`accounts_usuario`, `empresas_empresa`, `setores_setor`, `bi_links_linkbi`,
  `funcionarios_funcionario`, `funcionarios_funcionario_links_liberados`,
  `auditoria_acessolog`, `accounts_redepermitida`). Não há migração de dados.
- **Mesmas senhas**: o hasher `App\Support\DjangoPbkdf2Hasher` lê e grava no
  formato `pbkdf2_sha256` do Django. As duas aplicações podem usar o mesmo banco
  durante a transição.
- **Mesmas URLs** (sem a barra final): `/accounts/login`, `/dashboard`, `/links`,
  `/empresas/{id}/setores`, `/empresas/{id}/links`, `/usuarios`, `/auditoria`,
  `/accounts/redes`.
- **Mesmas regras**: papéis (Administrador, Admin da Empresa, Diretor, Usuário
  Especial, Usuário Normal), restrição por rede CIDR (IPv4/IPv6), escopo por
  empresa (proteção contra IDOR), exclusão em cascata, marca d'água LGPD e
  registro de auditoria.
- Datas gravadas em UTC (como o Django com `USE_TZ=True`) e exibidas em
  `America/Sao_Paulo`.

## Mapa Django → Laravel

| Django | Laravel |
|---|---|
| `accounts/models.py` (`Usuario`, `RedePermitida`) | `app/Models/Usuario.php`, `app/Models/RedePermitida.php` |
| `empresas`, `setores`, `funcionarios`, `bi_links`, `auditoria` (models) | `app/Models/*.php` |
| `LinkBI.objects.visible_to()` / `AcessoLog.objects.visible_to()` | scopes `visivelPara()` |
| `accounts/middleware.py` | `app/Http/Middleware/RestringirAcessoPorRede.php`, `AtualizarUltimoAcesso.php`, tratamento de `QueryException` em `bootstrap/app.php` |
| `accounts/mixins.py` | middlewares `papel:` e `empresa.escopo`, scope `Funcionario::gerenciaveisPor()` |
| `accounts/exclusao_cascata.py` | `app/Services/ExclusaoEmCascata.php` |
| `core/nav.py` + `core/context_processors.py` | `app/Support/NavegacaoPortal.php` (view composer) |
| views/forms de cada app | `app/Http/Controllers/*` (validação no controller) |
| `templates/` | `resources/views/` (Blade) |
| `static/` | `public/static/` |
| `createsuperuser` + promoção de role | `php artisan portal:criar-admin` |
| testes Django | `tests/Feature/*` (`php artisan test`) |

O admin nativo do Django (`/admin/`) não foi portado: todo o CRUD já é feito
pelas telas do próprio portal.

## Requisitos

- PHP 8.3 ou superior com as extensões `openssl`, `mbstring`, `intl`, `fileinfo`,
  `pdo_sqlite` (desenvolvimento) e `pdo_sqlsrv` (produção).
- Composer 2.
- Produção: Microsoft ODBC Driver 17 ou 18 for SQL Server e as extensões
  [`sqlsrv`/`pdo_sqlsrv`](https://learn.microsoft.com/sql/connect/php/download-drivers-php-sql-server)
  correspondentes à versão do PHP.

## Instalação (desenvolvimento)

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

Acesse `http://localhost:8000`. O arquivo `database/database.sqlite` é uma cópia
do `db.sqlite3` do portal Django; os usuários existentes entram com as mesmas senhas.

`php artisan migrate` apenas registra a migração do portal: ela só cria as
tabelas que ainda não existem, então não altera um banco vindo do Django.

Para um banco novo, crie o primeiro administrador:

```powershell
php artisan portal:criar-admin admin --email=admin@empresa.com
```

## Produção (SQL Server)

Configure o `.env` (nunca versione senhas):

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://portal.exemplo.com

DB_CONNECTION=sqlsrv
DB_HOST=automate.leal.local
DB_PORT=1433
DB_DATABASE=portalmmi
DB_USERNAME=pbi
DB_PASSWORD=senha-real-do-banco
DB_TRUST_SERVER_CERTIFICATE=true

PORTAL_FORCE_HTTPS=true
SESSION_SECURE_COOKIE=true
LOG_LEVEL=warning
```

O login da aplicação precisa apenas de `db_datareader` e `db_datawriter`: sessão
e cache ficam em arquivo (`storage/`), sem tabelas extras no banco. As tabelas
do portal já existem no banco `portalmmi`. Rodar `php artisan migrate` é
opcional e cria apenas a tabela `migrations`; se for rodar, use uma conta com
permissão de DDL.

Antes de cada atualização:

```powershell
composer install --no-dev --optimize-autoloader
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

O servidor web (IIS com FastCGI, Apache ou Nginx + PHP-FPM) deve apontar para a
pasta `public/`. O usuário do serviço precisa de escrita em `storage/` e
`bootstrap/cache/`. Não use `php artisan serve` em produção.

### Checklist de validação

1. Confirmar que o servidor da aplicação enxerga `automate.leal.local:1433`.
2. Instalar o ODBC Driver e as extensões `sqlsrv`/`pdo_sqlsrv` no PHP (`php -m`).
3. Configurar o `.env` com o login SQL fornecido pelo DBA.
4. Executar `php artisan about` e conferir ambiente, banco e cache.
5. Testar login (usuário e e-mail), CRUD de empresas/setores/usuários/links,
   abertura de relatório, auditoria e acesso por rede.

## Controle de acesso por rede

- **Administrador**: acessa de qualquer rede e gerencia as redes permitidas.
- **Diretor** e **Admin da Empresa**: acessam de qualquer rede.
- **Usuário Especial**: acessa de qualquer rede; vê os links do próprio setor e
  os liberados para ele.
- **Usuário Normal**: só acessa quando o IP pertence a uma rede ativa cadastrada
  e vê apenas os links liberados para ele. Sem nenhuma rede ativa, fica bloqueado.

A tela **Redes permitidas** aceita IP isolado ou CIDR (`200.10.20.30/32`,
`192.168.10.0/24`, `2001:db8::10/128`, `2001:db8:1234::/48`) e normaliza o valor.

O IP verificado é o `REMOTE_ADDR`. Atrás de proxy reverso, configure o proxy
para entregar o IP real ao upstream de forma controlada e, se necessário,
habilite `trustProxies` em `bootstrap/app.php` apenas para o IP do proxy. Nunca
confie em `X-Forwarded-For` vindo direto da internet.

## Login

O campo aceita nome de usuário ou e-mail (sem diferenciar maiúsculas). Sem
"Manter conectado", a sessão expira ao fechar o navegador; marcado, vale o
`SESSION_LIFETIME` (2 semanas, mesmo padrão do Django). O login aceita no máximo
10 tentativas por minuto por IP.

## Marca d'água nos relatórios

Para usuários Normais e Especiais, o relatório no iframe recebe uma camada
visual com usuário, e-mail, IP, data/hora, setor e o aviso de proibição de
compartilhamento conforme a LGPD. A camada não bloqueia cliques e não aparece
para Diretor nem Admin da Empresa. Todo acesso é registrado em `auditoria_acessolog`.

## Testes

```powershell
php artisan test
```

Os testes rodam em SQLite em memória e cobrem login (inclusive com hash gerado
pelo Django), restrição por rede, visibilidade de links por papel, escopo por
empresa, cadastros, exclusões em cascata, auditoria e dashboards.
