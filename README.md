# Portal MMI

Portal corporativo da MMI Incorporações, desenvolvido em Django.

## Implantação para a equipe de TI

### Requisitos

- Python 3.11 ou superior
- SQL Server em produção ou SQLite para desenvolvimento
- ODBC Driver 17 (ou superior) para SQL Server no servidor da aplicação
- Acesso ao servidor web que publicará o portal
- Um endereço DNS e certificado HTTPS em produção

### Instalação no Windows

```powershell
python -m venv venv
.\venv\Scripts\Activate.ps1
python -m pip install -r requirements.txt
Copy-Item .env.example .env
```

Edite o `.env` antes de iniciar o sistema. Nunca use os valores de exemplo em produção.

### SQL Server em produção

Ambiente SQL Server de produção/homologação:

| Item | Valor |
|---|---|
| Servidor | `automate.leal.local` |
| Banco | `portalmmi` |
| Porta | `1433` |
| Driver | `ODBC Driver 17 for SQL Server` |
| Autenticação da aplicação | Login SQL configurado pela equipe de TI |
| Migração inicial | Schema e dados do SQLite transferidos para o SQL Server |

O banco foi preparado com as migrações Django e contém as estruturas de
usuários, empresas, funcionários, setores, links BI, redes permitidas,
auditoria, sessões e tabelas auxiliares do Django. A carga inicial transferiu
os dados existentes do SQLite, incluindo os hashes de senha dos usuários e os
registros de auditoria. Senhas em texto puro não são armazenadas pelo sistema.

Configure o banco usando variáveis separadas, sem colocar a senha no código:

```env
DATABASE_BACKEND=mssql
DATABASE_NAME=portalmmi
DATABASE_USER=pbi
DATABASE_PASSWORD=senha-real-do-banco
DATABASE_HOST=automate.leal.local
DATABASE_PORT=1433
DATABASE_DRIVER=ODBC Driver 17 for SQL Server
DATABASE_EXTRA_PARAMS=TrustServerCertificate=yes;
```

O login da aplicação precisa ter `db_datareader` e `db_datawriter`. A aplicação
não precisa de permissão para criar tabelas em produção: a equipe de TI deve
executar as migrações com uma conta administrativa ou solicitar que o DBA as
execute:

```powershell
python manage.py migrate
```

Para autenticação integrada do Windows durante a manutenção, use uma conta com
permissão de DDL e configure temporariamente:

```env
DATABASE_USER=
DATABASE_PASSWORD=
DATABASE_EXTRA_PARAMS=Trusted_Connection=yes;TrustServerCertificate=yes;
```

Depois da migração, mantenha o login SQL restrito ao uso da aplicação e não
compartilhe credenciais em repositórios, scripts ou chamados.

#### Checklist de validação do banco

1. Confirmar que o servidor da aplicação enxerga `automate.leal.local:1433`.
2. Confirmar que o banco `portalmmi` está acessível.
3. Instalar o `ODBC Driver 17 for SQL Server` no servidor web.
4. Configurar o `.env` com o login SQL fornecido pelo DBA.
5. Executar `python manage.py check --deploy`.
6. Executar `python manage.py migrate --plan` e confirmar que não há migrações pendentes.
7. Executar `python manage.py collectstatic --noinput`.
8. Testar login, CRUD de empresas/setores/usuários/links e consulta da auditoria.

O login usado pela aplicação deve possuir `db_datareader` e `db_datawriter`.
Permissões de criação e alteração de tabelas devem ficar restritas ao DBA ou à
conta administrativa usada durante as migrações.

```env
SECRET_KEY=uma-chave-longa-e-aleatoria
DEBUG=False
ALLOWED_HOSTS=portal.exemplo.com,10.0.0.10
DATABASE_URL=mysql://USUARIO:SENHA@SERVIDOR:3306/NOME_DO_BANCO
CONN_MAX_AGE=60
```

### Banco de dados e arquivos estáticos

Execute as migrations e crie o primeiro administrador:

```powershell
python manage.py migrate
python manage.py createsuperuser
python manage.py collectstatic --noinput
```

O administrador criado deve receber a role **Administrador**. Depois, acesse o portal e abra o menu **Redes permitidas**.

Como o comando `createsuperuser` também mantém a role de negócio no valor padrão, promova o primeiro usuário explicitamente:

```powershell
python manage.py shell -c "from accounts.models import Usuario; u=Usuario.objects.get(username='admin'); u.role=Usuario.Role.ADMIN; u.save()"
```

Troque `admin` pelo nome de usuário escolhido durante o `createsuperuser`.

## Controle de acesso por rede

A regra de acesso é:

- **Administrador**: pode acessar de qualquer rede e gerenciar as redes permitidas.
- **Diretor**: pode acessar de qualquer rede.
- **Usuário Especial**: pode acessar de qualquer rede, mas somente os links do seu setor.
- **Usuário Normal**: só pode acessar quando o IP da requisição pertence a uma rede ativa cadastrada.
- Sem nenhuma rede ativa cadastrada, usuários normais ficam bloqueados por padrão.

A tela administrativa aceita IP individual ou rede em CIDR:

| Necessidade | Valor de exemplo |
|---|---|
| Um único IPv4 | `200.10.20.30/32` |
| Rede IPv4 | `192.168.10.0/24` |
| Um único IPv6 | `2001:db8::10/128` |
| Rede IPv6 | `2001:db8:1234::/48` |

Para liberar o escritório, informe o IP público de saída da empresa ou a faixa pública utilizada pelo firewall. Não cadastre apenas o IP privado do computador, como `192.168.x.x`, quando o acesso passar pela internet: o servidor normalmente verá o IP público do roteador/firewall.

## Proxy reverso, firewall e HTTPS

O middleware usa `REMOTE_ADDR`, que deve conter o endereço real do cliente visto pelo servidor Django. Na infraestrutura, configure o proxy reverso para preservar esse endereço de forma confiável.

Não encaminhe e não confie cegamente em `X-Forwarded-For` vindo diretamente da internet. O proxy deve remover o cabeçalho recebido do cliente, inserir o valor correto e impedir acesso direto ao Django fora do proxy.

Checklist recomendado:

- Publicar o portal somente com HTTPS.
- Liberar no firewall apenas as portas necessárias do proxy web.
- Bloquear acesso público direto à porta do processo Django.
- Configurar o proxy para encaminhar o IP real ao upstream de forma controlada.
- Validar o IP exibido no ambiente de homologação antes de cadastrar redes de produção.
- Cadastrar a rede corporativa em **Redes permitidas** e testar com um usuário normal.
- Testar também um acesso externo com usuário normal, que deve receber HTTP 403.
- Confirmar que Administrador e Diretor continuam acessando externamente.

Se a empresa possuir mais de uma unidade, cadastre uma entrada ativa para cada IP ou faixa autorizada.

## Login

O campo de login aceita nome de usuário ou e-mail, além da senha. O e-mail é comparado sem diferenciar letras maiúsculas e minúsculas.

## Operação da aplicação

Com o ambiente configurado, o processo de aplicação deve ser executado por um servidor WSGI/ASGI adequado à infraestrutura. Não use o servidor de desenvolvimento do Django em produção.

Antes de uma atualização:

```powershell
python manage.py check --deploy
python manage.py migrate
python manage.py collectstatic --noinput
```

Depois, reinicie o processo da aplicação e valide login, acesso por rede e abertura dos relatórios.

## Dados e auditoria

O sistema mantém os seguintes dados principais:

- **Usuários**: contas Django, papéis, e-mail, último acesso e hash seguro de senha.
- **Empresas e funcionários**: vínculo do usuário com empresa e setor.
- **Setores e links BI**: links ativos, setor responsável e permissões individuais.
- **Redes permitidas**: redes CIDR autorizadas para usuários normais.
- **Auditoria**: usuário, link acessado, endereço IP, navegador e data/hora do acesso.

O CRUD é realizado pelo portal usando o ORM do Django. A equipe de TI deve
aplicar novas migrações antes de atualizar o código da aplicação.

## Estrutura relacionada ao controle de rede

- `accounts/models.py`: modelo `RedePermitida`.
- `accounts/forms.py`: validação e normalização CIDR.
- `accounts/views.py`: CRUD exclusivo do Administrador.
- `accounts/middleware.py`: bloqueio de usuários normais fora das redes ativas.
- `accounts/migrations/0004_redepermitida.py`: criação da tabela de redes permitidas.
- `templates/accounts/`: telas administrativas e tela de bloqueio.
