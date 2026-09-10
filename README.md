# StockPro

Sistema de gestão de estoque desenvolvido em PHP puro. Interface com tema escuro, dashboards com indicadores e controle de entradas, saídas e fornecedores.

## Funcionalidades

- Autenticação de usuários com três perfis de acesso (Administrador, Gerente, Operacional)
- Dashboard com indicadores de estoque em tempo real
- Controle de entradas e saídas de produtos
- Cadastro e gestão de fornecedores
- Relatórios de movimentação

## Tecnologias

- PHP 8 (sem framework)
- **PostgreSQL** via PDO, com prepared statements em toda consulta
- HTML5 / CSS3

## Arquitetura de dados

A aplicação foi escrita originalmente contra arquivos JSON (`ler_json()`/`salvar_json()`). A migração pro PostgreSQL manteve essa mesma interface: essas duas funções, em `functions.php`, viraram um adaptador — por baixo fazem tudo em SQL (com transação e upsert), por cima devolvem exatamente os arrays que `produtos.php`, `entradas.php`, `saidas.php` e `fornecedores.php` já esperavam.

```
database/
├── schema.sql          # DDL completo (5 tabelas)
├── seed.sql             # dados de demonstração, migrados do JSON legado
└── dados_legado/        # JSONs originais, mantidos só como referência histórica
```

## Segurança

- Senhas com `password_hash()`/`password_verify()` (bcrypt)
- **Bloqueio de conta por força bruta**: 5 tentativas de login incorretas seguidas bloqueiam a conta por 15 minutos
- Proteção CSRF em todos os formulários que alteram dado (login, produtos, entradas, saídas, fornecedores), token validado com `hash_equals()`
- Cookies de sessão com `httponly`, `samesite=Lax` e `secure` (quando em HTTPS)
- `.htaccess` bloqueando acesso direto a `.env`/`.sql`/`.log` e às pastas `app/`, `database/`
- Cabeçalhos HTTP: `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`

## Como rodar localmente

```bash
createdb stockpro
psql stockpro < database/schema.sql
psql stockpro < database/seed.sql

cp .env.example .env   # ajuste as credenciais do seu Postgres local

php -S localhost:8000
```

Acesse `http://localhost:8000`.

### Credenciais de demonstração

| Perfil | E-mail | Senha |
|---|---|---|
| Administrador | admin@estoque.com | admin123 |
| Gerente | gerente@estoque.com | gerente123 |
| Operacional | operacional@estoque.com | operacional123 |

> Dados fictícios, gerados só para demonstração do sistema.

## Roadmap

- [ ] Trocar o cálculo de "próximo id" feito em PHP por depender só do `SERIAL` do banco (hoje `salvar_json()` ainda recebe o id pronto vindo da página, por compatibilidade com o código existente)
- [ ] Adicionar testes automatizados
- [ ] Paginação nas listagens de entradas/saídas

## Autor

Desenvolvido por **Arthur Sousa da Costa** — [LinkedIn](https://www.linkedin.com/in/arthur-sousa-ads/) · [GitHub](https://github.com/arthursousa-dev)
