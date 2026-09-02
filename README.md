# StockPro

Sistema de gestão de estoque desenvolvido em PHP puro, com armazenamento de dados em arquivos JSON (sem necessidade de banco de dados relacional para rodar). Interface com tema escuro, dashboards com indicadores e controle de entradas, saídas e fornecedores.

## Funcionalidades

- Autenticação de usuários com três perfis de acesso (Administrador, Gerente, Operacional)
- Dashboard com indicadores de estoque em tempo real
- Controle de entradas e saídas de produtos
- Cadastro e gestão de fornecedores
- Relatórios de movimentação
- Persistência de dados em JSON com escrita segura (`flock`) para evitar corrupção em acessos concorrentes

## Tecnologias

- PHP (sem framework)
- HTML5 / CSS3
- Armazenamento de dados: JSON

## Como rodar localmente

1. Tenha o PHP instalado (versão 8+) ou use Laragon/XAMPP.
2. Clone o repositório e entre na pasta do projeto.
3. Inicie um servidor local:
   ```bash
   php -S localhost:8000
   ```
4. Acesse `http://localhost:8000` no navegador.

## Credenciais de teste (ambiente de demonstração)

> ⚠️ Este é um projeto de portfólio/estudo. As credenciais abaixo são fictícias e servem apenas para demonstração — nenhuma delas deve ser reaproveitada em produção.

| Perfil | E-mail | Senha |
|---|---|---|
| Administrador | admin@estoque.com | admin123 |
| Gerente | gerente@estoque.com | gerente123 |
| Operacional | operacional@estoque.com | operacional123 |

## Segurança

- Senhas armazenadas com `password_hash()` (bcrypt) em `db_usuarios.json` e verificadas com `password_verify()` — nada de credencial em texto puro no código-fonte
- Proteção CSRF em todos os formulários que alteram dados (login, produtos, entradas, saídas, fornecedores — inclusive os de exclusão), com token validado via `hash_equals()`

## Próximos passos (roadmap)

- Migrar armazenamento de JSON para um banco de dados relacional (PostgreSQL)
- Adicionar testes automatizados

## Autor

Desenvolvido por **Arthur Sousa da Costa** como projeto de portfólio.
