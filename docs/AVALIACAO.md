# Avaliação do Projeto - Portal MMI

## 1. Resumo executivo

O Portal MMI apresenta uma base funcional consistente para publicação corporativa: possui autenticação, controle de acesso por papel, escopo por empresa/setor, links de BI em iframe, auditoria, dashboard e suporte configurável a SQL Server.

A versão avaliada corresponde à branch `versao02` e ao estado publicado no último commit do repositório.

## 2. Nota por dimensão

| Dimensão | Nota | Avaliação |
|---|---:|---|
| Funcionalidade | 8/10 | Fluxos principais de usuários, empresas, setores, links, auditoria e dashboard estão implementados. |
| Segurança de acesso | 8/10 | Há escopo por perfil, empresa, setor e rede, além de validações contra IDOR. Depende de configuração correta do proxy/IP. |
| Auditoria | 8/10 | Acesso registra usuário, link, IP, user-agent e data/hora; existe camada visual para perfis operacionais. |
| Qualidade de código | 7/10 | Estrutura Django organizada, mixins/querysets reutilizados e testes focados. Há oportunidades de padronização e documentação interna. |
| Testabilidade | 8/10 | Existem testes de contas, links, setores, usuários, dashboard e regras de escopo. Recomenda-se execução integral no pipeline. |
| Prontidão para nuvem | 7/10 | SQL Server, ODBC, migrações e checklist estão documentados; falta fechar infraestrutura de processo, proxy, observabilidade e segredos. |
| Operação | 7/10 | CRUD e telas administrativas estão presentes. É necessário definir rotina de backup, logs, alertas e suporte. |

**Avaliação geral: 7,6/10 - apto para homologação e preparação de produção, condicionado ao checklist de infraestrutura e segurança.**

## 3. Pontos fortes

- Separação clara entre aplicações Django por domínio.
- `AUTH_USER_MODEL` customizado com papéis de negócio.
- Querysets de visibilidade centralizam regras de acesso a links.
- Escopo de empresa protegido por mixins e querysets.
- Auditoria append-only para acessos a relatórios.
- Suporte a Usuário Especial com compartilhamento entre setores.
- Marca d'água de auditoria no iframe para perfis operacionais.
- Migrações versionadas e suporte a SQL Server.
- Testes de segurança cobrindo acesso por empresa, setor, perfil e rede.

## 4. Riscos e pendências

### Alta prioridade

1. **Segredos de produção**: manter `SECRET_KEY`, senha SQL e credenciais de proxy em cofre de segredos; nunca em Git ou arquivos enviados por e-mail.
2. **HTTPS e proxy**: habilitar redirect HTTPS, cookies seguros, HSTS após validação e encaminhamento confiável de `REMOTE_ADDR`.
3. **Banco**: manter DDL restrito ao DBA; a conta da aplicação deve ter somente leitura/escrita necessárias.
4. **Backup**: definir backup, restauração testada, retenção e RPO/RTO do SQL Server.
5. **Observabilidade**: centralizar logs do processo web, erros 5xx, latência e falhas de conexão com banco.

### Média prioridade

1. Definir servidor WSGI/ASGI e serviço de execução para reinício automático.
2. Configurar política de rotação de logs e monitoramento de disco.
3. Adicionar pipeline CI com `manage.py check`, testes e verificação de migrações.
4. Revisar o conjunto completo de testes em cada release.
5. Validar políticas de `X-Frame-Options` e CSP conforme os domínios dos relatórios de BI.

### Baixa prioridade

1. Criar documentação operacional de atendimento e matriz de responsáveis.
2. Padronizar formatação/encoding dos arquivos Python mais antigos.
3. Avaliar compactação/cache de assets estáticos no proxy.

## 5. Segurança residual

- A marca d'água sobre um iframe externo é uma camada visual; ela não impede fotografias da tela nem altera o documento hospedado no domínio do relatório.
- A confiança em IP depende da configuração correta do proxy reverso. Não confiar em `X-Forwarded-For` recebido diretamente do cliente.
- O sistema não oferece recuperação pública de senha na versão atual; o procedimento de suporte deve ser definido pela MMI.
- Links externos devem ser revisados quanto a `X-Frame-Options`, CSP e expiração de sessão no sistema de origem.

## 6. Recomendação de aceite

**Recomendação: aprovar para homologação.** Para produção, condicionar a entrada aos itens de alta prioridade, ao teste de restauração do banco, à configuração HTTPS e à validação com usuários reais de todos os perfis.

## 7. Evidências técnicas

- Backend configurável por `DATABASE_BACKEND=mssql`.
- Banco documentado: servidor `automate.leal.local`, database `portalmmi`, porta `1433`.
- Dependências SQL Server: `mssql-django`, `pyodbc` e ODBC Driver 17+.
- Migrações Django versionadas até a versão atual do projeto.
- `manage.py check` validado durante a preparação desta documentação.
