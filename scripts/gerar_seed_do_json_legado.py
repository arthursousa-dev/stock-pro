"""
Ferramenta de migração usada UMA VEZ para converter os JSONs legados
(database/dados_legado/*.json) em INSERT para o PostgreSQL
(database/seed.sql). Mantido no repositório como documentação do
processo de migração.
"""
import json, os

RAIZ = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
BASE = os.path.join(RAIZ, "database", "dados_legado")

def load(name):
    with open(os.path.join(BASE, name), encoding="utf-8") as f:
        return json.load(f)

def esc(v):
    if v is None:
        return "NULL"
    if isinstance(v, bool):
        return "true" if v else "false"
    if isinstance(v, (int, float)):
        return str(v)
    return "'" + str(v).replace("'", "''") + "'"

out = []
out.append("-- ============================================================")
out.append("-- StockPro — Dados de demonstração (migrados do JSON legado)")
out.append("-- ============================================================\n")

# usuarios
usuarios = load("db_usuarios.json")["usuarios"]
out.append("-- usuarios")
out.append("INSERT INTO usuarios (id, nome, email, senha_hash, perfil) VALUES")
linhas = [f"({u['id']}, {esc(u['nome'])}, {esc(u['email'])}, {esc(u['senha'])}, {esc(u['perfil'])})" for u in usuarios]
out.append(",\n".join(linhas) + ";")
out.append(f"SELECT setval('usuarios_id_seq', {max(u['id'] for u in usuarios)});\n")

# fornecedores
fornecedores = load("db_fornecedores.json")["fornecedores"]
out.append("-- fornecedores")
out.append("INSERT INTO fornecedores (id, nome, email, telefone, cnpj) VALUES")
linhas = [f"({f['id']}, {esc(f['nome'])}, {esc(f.get('email'))}, {esc(f.get('telefone'))}, {esc(f['cnpj'])})" for f in fornecedores]
out.append(",\n".join(linhas) + ";")
out.append(f"SELECT setval('fornecedores_id_seq', {max(f['id'] for f in fornecedores)});\n")

# produtos
produtos = load("db_produtos.json")["produtos"]
out.append("-- produtos")
out.append("INSERT INTO produtos (id, codigo, nome, categoria, preco_custo, preco_venda, estoque, estoque_minimo) VALUES")
linhas = [f"({p['id']}, {esc(p['codigo'])}, {esc(p['nome'])}, {esc(p.get('categoria'))}, {p['preco_custo']}, {p['preco_venda']}, {p['estoque']}, {p['estoque_minimo']})" for p in produtos]
out.append(",\n".join(linhas) + ";")
out.append(f"SELECT setval('produtos_id_seq', {max(p['id'] for p in produtos)});\n")

# entradas
entradas = load("db_entradas.json")["entradas"]
out.append("-- entradas")
out.append("INSERT INTO entradas (id, nota_fiscal, data, fornecedor_id, produto_id, quantidade, preco_unitario, total) VALUES")
linhas = [f"({e['id']}, {esc(e['nota_fiscal'])}, {esc(e['data'])}, {e['fornecedor_id']}, {e['produto_id']}, {e['quantidade']}, {e['preco_unitario']}, {e['total']})" for e in entradas]
out.append(",\n".join(linhas) + ";")
out.append(f"SELECT setval('entradas_id_seq', {max(e['id'] for e in entradas)});\n")

# saidas
saidas = load("db_saidas.json")["saidas"]
out.append("-- saidas")
out.append("INSERT INTO saidas (id, nota_fiscal, data, cliente, produto_id, quantidade, preco_unitario, total) VALUES")
linhas = [f"({s['id']}, {esc(s['nota_fiscal'])}, {esc(s['data'])}, {esc(s['cliente'])}, {s['produto_id']}, {s['quantidade']}, {s['preco_unitario']}, {s['total']})" for s in saidas]
out.append(",\n".join(linhas) + ";")
out.append(f"SELECT setval('saidas_id_seq', {max(s['id'] for s in saidas)});\n")

with open(os.path.join(RAIZ, "database", "seed.sql"), "w", encoding="utf-8") as f:
    f.write("\n".join(out))

print("seed.sql gerado com sucesso")
