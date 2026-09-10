-- ============================================================
-- StockPro — Schema PostgreSQL
-- Substitui o armazenamento em arquivos JSON por um banco
-- relacional de verdade.
-- ============================================================

CREATE TYPE perfil_usuario AS ENUM ('admin', 'gerente', 'operacional');

-- ------------------------------------------------------------
-- usuarios
-- ------------------------------------------------------------
CREATE TABLE usuarios (
    id               SERIAL PRIMARY KEY,
    nome             VARCHAR(120) NOT NULL,
    email            VARCHAR(150) NOT NULL UNIQUE,
    senha_hash       VARCHAR(255) NOT NULL,
    perfil           perfil_usuario NOT NULL,
    tentativas_login SMALLINT NOT NULL DEFAULT 0,
    bloqueado_ate    TIMESTAMP NULL,
    criado_em        TIMESTAMP NOT NULL DEFAULT now()
);

-- ------------------------------------------------------------
-- fornecedores
-- ------------------------------------------------------------
CREATE TABLE fornecedores (
    id        SERIAL PRIMARY KEY,
    nome      VARCHAR(150) NOT NULL,
    email     VARCHAR(150),
    telefone  VARCHAR(20),
    cnpj      VARCHAR(20) NOT NULL UNIQUE,
    criado_em TIMESTAMP NOT NULL DEFAULT now()
);

-- ------------------------------------------------------------
-- produtos
-- ------------------------------------------------------------
CREATE TABLE produtos (
    id              SERIAL PRIMARY KEY,
    codigo          VARCHAR(30) NOT NULL UNIQUE,
    nome            VARCHAR(150) NOT NULL,
    categoria       VARCHAR(80),
    preco_custo     NUMERIC(10,2) NOT NULL DEFAULT 0,
    preco_venda     NUMERIC(10,2) NOT NULL DEFAULT 0,
    estoque         INT NOT NULL DEFAULT 0 CHECK (estoque >= 0),
    estoque_minimo  INT NOT NULL DEFAULT 0,
    criado_em       TIMESTAMP NOT NULL DEFAULT now()
);

-- ------------------------------------------------------------
-- entradas — movimentação de compra (fornecedor -> estoque)
-- ------------------------------------------------------------
CREATE TABLE entradas (
    id              SERIAL PRIMARY KEY,
    nota_fiscal     VARCHAR(30) NOT NULL,
    data            DATE NOT NULL,
    fornecedor_id   INT NOT NULL REFERENCES fornecedores(id) ON DELETE RESTRICT,
    produto_id      INT NOT NULL REFERENCES produtos(id) ON DELETE RESTRICT,
    quantidade      INT NOT NULL CHECK (quantidade > 0),
    preco_unitario  NUMERIC(10,2) NOT NULL,
    total           NUMERIC(12,2) NOT NULL,
    criado_em       TIMESTAMP NOT NULL DEFAULT now()
);

-- ------------------------------------------------------------
-- saidas — movimentação de venda (estoque -> cliente)
-- ------------------------------------------------------------
CREATE TABLE saidas (
    id              SERIAL PRIMARY KEY,
    nota_fiscal     VARCHAR(30) NOT NULL,
    data            DATE NOT NULL,
    cliente         VARCHAR(150) NOT NULL,
    produto_id      INT NOT NULL REFERENCES produtos(id) ON DELETE RESTRICT,
    quantidade      INT NOT NULL CHECK (quantidade > 0),
    preco_unitario  NUMERIC(10,2) NOT NULL,
    total           NUMERIC(12,2) NOT NULL,
    criado_em       TIMESTAMP NOT NULL DEFAULT now()
);

CREATE INDEX idx_entradas_produto ON entradas(produto_id);
CREATE INDEX idx_saidas_produto   ON saidas(produto_id);
CREATE INDEX idx_produtos_nome    ON produtos(nome);
