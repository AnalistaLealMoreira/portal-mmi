# Portal MMI

Portal corporativo da MMI Incorporações, desenvolvido em Django.

## Implantação para a equipe de TI

### Requisitos

- Python 3.11 ou superior
- MySQL em produção ou SQLite para desenvolvimento
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

## Estrutura relacionada ao controle de rede

- `accounts/models.py`: modelo `RedePermitida`.
- `accounts/forms.py`: validação e normalização CIDR.
- `accounts/views.py`: CRUD exclusivo do Administrador.
- `accounts/middleware.py`: bloqueio de usuários normais fora das redes ativas.
- `accounts/migrations/0004_redepermitida.py`: criação da tabela de redes permitidas.
- `templates/accounts/`: telas administrativas e tela de bloqueio.
