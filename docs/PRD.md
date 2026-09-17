# PRD - Portal MMI

## 1. Visão do produto

O Portal MMI é uma aplicação web corporativa para centralizar o acesso a relatórios de BI, organizar permissões por empresa/setor e registrar auditoria de acessos. O produto deve permitir que a equipe de negócio consulte seus relatórios com segurança, enquanto a equipe administrativa mantém empresas, setores, usuários, links e redes autorizadas.

## 2. Objetivo

Disponibilizar um portal único, seguro e auditável para:

- organizar empresas, setores, funcionários e links de BI;
- controlar o acesso por perfil, setor, compartilhamento e rede;
- abrir relatórios dentro do portal;
- registrar usuário, link, IP, navegador e data/hora de cada acesso;
- apresentar indicadores de uso e relatórios mais acessados;
- facilitar a operação da equipe de TI em ambiente cloud com SQL Server.

## 3. Usuários e permissões

| Perfil | Escopo |
|---|---|
| Administrador | Acesso global, administração de empresas, usuários, setores, links, redes e auditoria. |
| Admin da Empresa | Administra somente a própria empresa e visualiza seus links. Não recebe marca d'água de auditoria no relatório. |
| Diretor | Visualiza links da própria empresa e indicadores da empresa. Não recebe marca d'água de auditoria no relatório. |
| Usuário Especial | Acessa todos os links do próprio setor e links de outros setores explicitamente compartilhados. Acessa de qualquer rede. |
| Usuário Normal | Acessa links explicitamente liberados dentro do setor e somente a partir de rede autorizada. |

## 4. Funcionalidades

### 4.1 Autenticação

- Login por usuário ou e-mail.
- Sessão persistente opcional por meio de “Manter conectado”.
- Logout via POST.
- Não há recuperação de senha no login público; redefinições devem ser tratadas pela equipe responsável.

### 4.2 Gestão administrativa

- CRUD de empresas.
- CRUD de setores por empresa.
- Cadastro, edição e exclusão de funcionários/usuários.
- Login gerado automaticamente a partir do texto antes do `@` do e-mail, com iniciais maiúsculas e separadores convertidos em espaços.
- CRUD de links de BI por empresa/setor.
- Compartilhamento de links com funcionários; links fora do setor só podem ser compartilhados com Usuários Especiais.
- CRUD de redes permitidas, exclusivo do Administrador.

### 4.3 Acesso a relatórios

- Relatórios são exibidos em iframe dentro do portal.
- O acesso ao link é validado novamente no endpoint, evitando acesso direto por URL.
- O acesso permitido gera registro em `auditoria.AcessoLog`.
- Usuários Normais e Especiais recebem marca d'água visual no iframe com dados de auditoria.
- A marca d'água não bloqueia cliques e não é exibida para Diretor e Admin da Empresa.

### 4.4 Dashboard e indicadores

- Administrador: visão consolidada de empresas, usuários e links.
- Diretor/Admin da Empresa: indicadores da própria empresa.
- Normal/Especial: indicadores dos próprios acessos.
- Ranking de setores e relatórios mais acessados.
- Gráfico de acessos por período recente.
- Barra lateral com setores e links visíveis, incluindo setores de links compartilhados para Usuários Especiais.

## 5. Requisitos não funcionais

- Python 3.11 ou superior.
- Django 5.2.x.
- SQL Server em produção com `mssql-django`, `pyodbc` e ODBC Driver 17 ou superior.
- HTTPS obrigatório em produção.
- Segredos somente por variáveis de ambiente ou cofre de segredos.
- Login da aplicação com `db_datareader` e `db_datawriter`; DDL reservado ao DBA.
- IP real do cliente preservado pelo proxy reverso de forma confiável.
- Logs e auditoria protegidos contra edição comum.
- Backups e política de retenção definidos pela equipe de TI/DBA.

## 6. Critérios de aceite

- Um usuário Normal fora de uma rede autorizada recebe HTTP 403.
- Usuários Diretor, Administrador e Especial não dependem da rede permitida.
- Um Normal não acessa link de outro setor ou empresa.
- Um Especial acessa link de outro setor somente quando compartilhado.
- Admin da Empresa não consegue operar outra empresa alterando IDs na URL.
- O setor selecionado no cadastro de usuário é filtrado pela empresa escolhida.
- O acesso permitido cria registro de auditoria com IP e user-agent.
- A marca d'água mostra dados de auditoria para Normal/Especial e não aparece para Diretor/Admin da Empresa.
- `python manage.py check` passa e não existem migrações pendentes.
- O portal opera com SQL Server configurado por variáveis de ambiente.

## 7. Fora de escopo

- Recuperação automática de senha pelo portal público.
- Alteração de conteúdo dentro de iframes hospedados em domínio externo.
- Administração do servidor SQL Server pelo portal.
- Substituição do sistema de BI de origem.
- Armazenamento de senhas em texto puro.

## 8. Indicadores de sucesso

- 100% dos acessos a links permitidos registrados na auditoria.
- Zero acesso confirmado fora do escopo de empresa/setor/perfil.
- Deploy reproduzível por `.env`, migrações e `collectstatic`.
- Tempo de indisponibilidade de atualização reduzido por checklist operacional.
