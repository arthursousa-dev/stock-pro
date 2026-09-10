-- ============================================================
-- StockPro — Dados de demonstração (migrados do JSON legado)
-- ============================================================

-- usuarios
INSERT INTO usuarios (id, nome, email, senha_hash, perfil) VALUES
(1, 'Administrador', 'admin@estoque.com', '$2b$10$9gGJSJ32oBuaKpHbjcgdIeSVVPtINfYfoFbXWCGh3BOLnxxfIh0vO', 'admin'),
(2, 'Gerente', 'gerente@estoque.com', '$2b$10$SZBw2DBtpg7ZB.V3w65CdOdVWgTqjBsSh9dPo.qMEjDHdXwNZyaIO', 'gerente'),
(3, 'Operacional', 'operacional@estoque.com', '$2b$10$jhBEhBF2SF.lNdp9eMCbFejOtdeDPt3pwVTXtN6vB4pzzG3gzY7Qq', 'operacional');
SELECT setval('usuarios_id_seq', 3);

-- fornecedores
INSERT INTO fornecedores (id, nome, email, telefone, cnpj) VALUES
(1, 'TechDistrib Ltda', 'compras@techdistrib.com.br', '(11) 3344-5500', '12.345.678/0001-90'),
(2, 'InfoSul Distribuidora', 'vendas@infosul.com.br', '(51) 2233-7744', '23.456.789/0001-01'),
(3, 'MegaStock Comércio', 'pedidos@megastock.com.br', '(21) 4455-8899', '34.567.890/0001-12'),
(4, 'ElectroMax Importados', 'contato@electromax.com.br', '(41) 9988-1122', '45.678.901/0001-23'),
(5, 'FurnitechMóveis S/A', 'faturamento@furnitech.com.br', '(31) 7766-3344', '56.789.012/0001-34');
SELECT setval('fornecedores_id_seq', 5);

-- produtos
INSERT INTO produtos (id, codigo, nome, categoria, preco_custo, preco_venda, estoque, estoque_minimo) VALUES
(1, 'INFO-001', 'Notebook Dell Inspiron 15', 'Informática', 2500.0, 3499.0, 12, 5),
(2, 'INFO-002', 'Mouse Logitech MX Master', 'Informática', 180.0, 320.0, 3, 10),
(3, 'INFO-003', 'Teclado Mecânico Redragon', 'Informática', 210.0, 380.0, 18, 5),
(4, 'INFO-004', 'Monitor LG 24" Full HD', 'Informática', 780.0, 1149.0, 7, 3),
(5, 'INFO-005', 'Headset HyperX Cloud II', 'Informática', 290.0, 499.0, 2, 5),
(6, 'MOV-001', 'Cadeira Gamer ThunderX3', 'Móveis', 650.0, 1099.0, 6, 2),
(7, 'MOV-002', 'Mesa de Escritório 1,40m', 'Móveis', 420.0, 699.0, 4, 2),
(8, 'ELET-001', 'Webcam Logitech C920', 'Eletrônicos', 320.0, 559.0, 9, 4),
(9, 'ELET-002', 'Caixa de Som JBL Flip 6', 'Eletrônicos', 440.0, 749.0, 11, 3),
(10, 'ELET-003', 'Carregador USB-C 65W Anker', 'Eletrônicos', 85.0, 149.0, 1, 8);
SELECT setval('produtos_id_seq', 10);

-- entradas
INSERT INTO entradas (id, nota_fiscal, data, fornecedor_id, produto_id, quantidade, preco_unitario, total) VALUES
(1, 'NF-0051', '2026-05-03', 1, 1, 8, 2500.0, 20000.0),
(2, 'NF-0052', '2026-05-05', 2, 3, 15, 210.0, 3150.0),
(3, 'NF-0053', '2026-05-08', 3, 8, 10, 320.0, 3200.0),
(4, 'NF-0054', '2026-05-12', 4, 9, 12, 440.0, 5280.0),
(5, 'NF-0055', '2026-05-15', 1, 4, 6, 780.0, 4680.0),
(6, 'NF-0056', '2026-05-19', 5, 6, 5, 650.0, 3250.0),
(7, 'NF-0057', '2026-05-22', 2, 2, 20, 180.0, 3600.0),
(8, 'NF-0058', '2026-05-26', 4, 10, 25, 85.0, 2125.0),
(9, 'NF-0059', '2026-05-29', 3, 5, 10, 290.0, 2900.0),
(10, 'NF-0060', '2026-05-30', 5, 7, 4, 420.0, 1680.0),
(11, 'NF-0061', '2026-06-02', 1, 1, 6, 2500.0, 15000.0),
(12, 'NF-0062', '2026-06-03', 2, 3, 10, 210.0, 2100.0),
(13, 'NF-0063', '2026-06-04', 4, 8, 8, 320.0, 2560.0),
(14, 'NF-0064', '2026-06-05', 3, 9, 7, 440.0, 3080.0),
(15, 'NF-0065', '2026-06-06', 5, 6, 3, 650.0, 1950.0);
SELECT setval('entradas_id_seq', 15);

-- saidas
INSERT INTO saidas (id, nota_fiscal, data, cliente, produto_id, quantidade, preco_unitario, total) VALUES
(1, 'NS-0101', '2026-05-04', 'Agência Pixel Criativo', 1, 2, 3499.0, 6998.0),
(2, 'NS-0102', '2026-05-07', 'Escritório Advocacia Neto', 4, 3, 1149.0, 3447.0),
(3, 'NS-0103', '2026-05-10', 'Clínica Saúde Total', 8, 4, 559.0, 2236.0),
(4, 'NS-0104', '2026-05-13', 'Escola Futuro Digital', 3, 8, 380.0, 3040.0),
(5, 'NS-0105', '2026-05-16', 'Construtora BrasBuild', 6, 3, 1099.0, 3297.0),
(6, 'NS-0106', '2026-05-18', 'Agência Pixel Criativo', 9, 5, 749.0, 3745.0),
(7, 'NS-0107', '2026-05-20', 'Escola Futuro Digital', 2, 6, 320.0, 1920.0),
(8, 'NS-0108', '2026-05-23', 'Clínica Saúde Total', 10, 10, 149.0, 1490.0),
(9, 'NS-0109', '2026-05-27', 'Escritório Advocacia Neto', 5, 4, 499.0, 1996.0),
(10, 'NS-0110', '2026-05-30', 'Construtora BrasBuild', 7, 3, 699.0, 2097.0),
(11, 'NS-0111', '2026-06-02', 'Agência Pixel Criativo', 1, 3, 3499.0, 10497.0),
(12, 'NS-0112', '2026-06-03', 'Escola Futuro Digital', 3, 5, 380.0, 1900.0),
(13, 'NS-0113', '2026-06-04', 'Clínica Saúde Total', 4, 2, 1149.0, 2298.0),
(14, 'NS-0114', '2026-06-05', 'Construtora BrasBuild', 9, 4, 749.0, 2996.0),
(15, 'NS-0115', '2026-06-06', 'Escritório Advocacia Neto', 8, 3, 559.0, 1677.0);
SELECT setval('saidas_id_seq', 15);
