<?php
// functions.php
//
// ler_json()/salvar_json() liam e escreviam arquivos JSON em disco.
// A aplicação inteira foi escrita contra essa interface, então —
// em vez de reescrever cada página de uma vez — estas duas funções
// viraram um adaptador: por baixo, tudo é PostgreSQL via PDO com
// prepared statements; por cima, o resto do código continua
// enxergando os mesmos arrays de sempre ({'produtos' => [...],
// 'next_id' => N}).

use App\Config\Database;

require_once __DIR__ . '/app/Config/Database.php';

function ler_json($arquivo) {
    $pdo = Database::getConnection();

    switch ($arquivo) {
        case 'db_produtos.json':
            $itens = $pdo->query('SELECT * FROM produtos ORDER BY id')->fetchAll();
            $proximo = (int) $pdo->query("SELECT COALESCE(MAX(id), 0) + 1 FROM produtos")->fetchColumn();
            return ['produtos' => $itens, 'next_id' => $proximo];

        case 'db_fornecedores.json':
            $itens = $pdo->query('SELECT * FROM fornecedores ORDER BY id')->fetchAll();
            $proximo = (int) $pdo->query("SELECT COALESCE(MAX(id), 0) + 1 FROM fornecedores")->fetchColumn();
            return ['fornecedores' => $itens, 'next_id' => $proximo];

        case 'db_entradas.json':
            $itens = $pdo->query(
                "SELECT e.id, e.nota_fiscal, to_char(e.data,'YYYY-MM-DD') AS data,
                        e.fornecedor_id, f.nome AS fornecedor_nome,
                        e.produto_id, p.nome AS produto_nome,
                        e.quantidade, e.preco_unitario, e.total
                 FROM entradas e
                 JOIN fornecedores f ON f.id = e.fornecedor_id
                 JOIN produtos p     ON p.id = e.produto_id
                 ORDER BY e.data DESC, e.id DESC"
            )->fetchAll();
            $proximo = (int) $pdo->query("SELECT COALESCE(MAX(id), 0) + 1 FROM entradas")->fetchColumn();
            return ['entradas' => $itens, 'next_id' => $proximo];

        case 'db_saidas.json':
            $itens = $pdo->query(
                "SELECT s.id, s.nota_fiscal, to_char(s.data,'YYYY-MM-DD') AS data,
                        s.cliente, s.produto_id, p.nome AS produto_nome,
                        s.quantidade, s.preco_unitario, s.total
                 FROM saidas s
                 JOIN produtos p ON p.id = s.produto_id
                 ORDER BY s.data DESC, s.id DESC"
            )->fetchAll();
            $proximo = (int) $pdo->query("SELECT COALESCE(MAX(id), 0) + 1 FROM saidas")->fetchColumn();
            return ['saidas' => $itens, 'next_id' => $proximo];

        default:
            return [];
    }
}

function salvar_json($arquivo, $dados) {
    $pdo = Database::getConnection();

    try {
        $pdo->beginTransaction();

        switch ($arquivo) {
            case 'db_produtos.json':
                $vindos = $dados['produtos'] ?? [];
                $idsAtuais = $pdo->query('SELECT id FROM produtos')->fetchAll(\PDO::FETCH_COLUMN);
                $idsNovos  = array_column($vindos, 'id');

                $upsert = $pdo->prepare(
                    'INSERT INTO produtos (id, codigo, nome, categoria, preco_custo, preco_venda, estoque, estoque_minimo)
                     VALUES (:id, :codigo, :nome, :categoria, :custo, :venda, :estoque, :minimo)
                     ON CONFLICT (id) DO UPDATE SET
                        codigo=EXCLUDED.codigo, nome=EXCLUDED.nome, categoria=EXCLUDED.categoria,
                        preco_custo=EXCLUDED.preco_custo, preco_venda=EXCLUDED.preco_venda,
                        estoque=EXCLUDED.estoque, estoque_minimo=EXCLUDED.estoque_minimo'
                );
                foreach ($vindos as $p) {
                    $upsert->execute([
                        ':id' => $p['id'], ':codigo' => $p['codigo'], ':nome' => $p['nome'],
                        ':categoria' => $p['categoria'] ?? null, ':custo' => $p['preco_custo'],
                        ':venda' => $p['preco_venda'], ':estoque' => $p['estoque'], ':minimo' => $p['estoque_minimo'],
                    ]);
                }
                $remover = array_diff($idsAtuais, $idsNovos);
                if ($remover) {
                    $in = implode(',', array_map('intval', $remover));
                    $pdo->exec("DELETE FROM produtos WHERE id IN ($in)");
                }
                break;

            case 'db_fornecedores.json':
                $vindos = $dados['fornecedores'] ?? [];
                $idsAtuais = $pdo->query('SELECT id FROM fornecedores')->fetchAll(\PDO::FETCH_COLUMN);
                $idsNovos  = array_column($vindos, 'id');

                $upsert = $pdo->prepare(
                    'INSERT INTO fornecedores (id, nome, email, telefone, cnpj)
                     VALUES (:id, :nome, :email, :telefone, :cnpj)
                     ON CONFLICT (id) DO UPDATE SET
                        nome=EXCLUDED.nome, email=EXCLUDED.email, telefone=EXCLUDED.telefone, cnpj=EXCLUDED.cnpj'
                );
                foreach ($vindos as $f) {
                    $upsert->execute([
                        ':id' => $f['id'], ':nome' => $f['nome'], ':email' => $f['email'] ?? null,
                        ':telefone' => $f['telefone'] ?? null, ':cnpj' => $f['cnpj'],
                    ]);
                }
                $remover = array_diff($idsAtuais, $idsNovos);
                if ($remover) {
                    $in = implode(',', array_map('intval', $remover));
                    $pdo->exec("DELETE FROM fornecedores WHERE id IN ($in)");
                }
                break;

            case 'db_entradas.json':
                $vindos = $dados['entradas'] ?? [];
                $idsAtuais = $pdo->query('SELECT id FROM entradas')->fetchAll(\PDO::FETCH_COLUMN);
                $idsNovos  = array_column($vindos, 'id');
                $novasLinhas = array_filter($vindos, fn($e) => !in_array($e['id'], $idsAtuais));

                $insere = $pdo->prepare(
                    'INSERT INTO entradas (id, nota_fiscal, data, fornecedor_id, produto_id, quantidade, preco_unitario, total)
                     VALUES (:id, :nf, :data, :forn, :prod, :qtd, :preco, :total)'
                );
                foreach ($novasLinhas as $e) {
                    $insere->execute([
                        ':id' => $e['id'], ':nf' => $e['nota_fiscal'], ':data' => $e['data'],
                        ':forn' => $e['fornecedor_id'], ':prod' => $e['produto_id'],
                        ':qtd' => $e['quantidade'], ':preco' => $e['preco_unitario'], ':total' => $e['total'],
                    ]);
                }
                break;

            case 'db_saidas.json':
                $vindos = $dados['saidas'] ?? [];
                $idsAtuais = $pdo->query('SELECT id FROM saidas')->fetchAll(\PDO::FETCH_COLUMN);
                $novasLinhas = array_filter($vindos, fn($s) => !in_array($s['id'], $idsAtuais));

                $insere = $pdo->prepare(
                    'INSERT INTO saidas (id, nota_fiscal, data, cliente, produto_id, quantidade, preco_unitario, total)
                     VALUES (:id, :nf, :data, :cliente, :prod, :qtd, :preco, :total)'
                );
                foreach ($novasLinhas as $s) {
                    $insere->execute([
                        ':id' => $s['id'], ':nf' => $s['nota_fiscal'], ':data' => $s['data'],
                        ':cliente' => $s['cliente'], ':prod' => $s['produto_id'],
                        ':qtd' => $s['quantidade'], ':preco' => $s['preco_unitario'], ':total' => $s['total'],
                    ]);
                }
                break;
        }

        $pdo->commit();
        return true;
    } catch (\Throwable $e) {
        $pdo->rollBack();
        error_log('salvar_json falhou (' . $arquivo . '): ' . $e->getMessage());
        return false;
    }
}

function gerar_id($arquivo, $chave_next = 'next_id') {
    // Mantido por compatibilidade: algumas telas ainda pedem o
    // "próximo id" antes de montar o array pra salvar_json(). Com
    // SERIAL no banco, o id real é definido no INSERT — isso aqui
    // só evita colisão entre o id previsto e o efetivamente salvo.
    $dados = ler_json($arquivo);
    return (int) ($dados[$chave_next] ?? 1);
}

function verificar_sessao() {
    if (empty($_SESSION['logado'])) {
        header('Location: login.php');
        exit;
    }
}

function verificar_permissao($perfis_permitidos) {
    if (!in_array($_SESSION['perfil'] ?? '', $perfis_permitidos)) {
        return false;
    }
    return true;
}

function pode_editar() {
    return in_array($_SESSION['perfil'] ?? '', ['admin', 'gerente']);
}

function exigir_permissao($perfis_permitidos) {
    if (!in_array($_SESSION['perfil'] ?? '', $perfis_permitidos)) {
        http_response_code(403);
        echo '<div style="font-family:sans-serif;padding:2rem;text-align:center;"><h2>⛔ Acesso Negado</h2><p>Você não tem permissão para acessar esta página.</p><a href="index.php">Voltar ao início</a></div>';
        exit;
    }
}

function formatar_moeda($valor) {
    return 'R$ ' . number_format((float)$valor, 2, ',', '.');
}

function formatar_data($data_iso) {
    if (empty($data_iso)) return '-';
    $partes = explode('-', $data_iso);
    if (count($partes) !== 3) return $data_iso;
    return $partes[2] . '/' . $partes[1] . '/' . $partes[0];
}

function validar_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function get_usuario_logado() {
    return [
        'nome'  => $_SESSION['nome']  ?? 'Usuário',
        'email' => $_SESSION['email'] ?? '',
        'perfil'=> $_SESSION['perfil']?? 'operacional',
    ];
}

function perfil_label($perfil) {
    $labels = ['admin' => 'Administrador', 'gerente' => 'Gerente', 'operacional' => 'Operacional'];
    return $labels[$perfil] ?? ucfirst($perfil);
}

/**
 * Autentica contra a tabela usuarios (senha em hash bcrypt), com
 * bloqueio de conta após tentativas incorretas seguidas.
 */
function autenticar($email, $senha) {
    $pdo = Database::getConnection();
    $email = strtolower(trim($email));

    $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE email = :email');
    $stmt->execute([':email' => $email]);
    $u = $stmt->fetch();

    if (!$u) {
        return false;
    }

    if ($u['bloqueado_ate'] !== null && strtotime($u['bloqueado_ate']) > time()) {
        return false;
    }

    if (!password_verify($senha, $u['senha_hash'])) {
        $tentativas = (int) $u['tentativas_login'] + 1;
        $bloqueio = $tentativas >= 5 ? "now() + interval '15 minutes'" : 'NULL';
        $pdo->prepare("UPDATE usuarios SET tentativas_login = :t, bloqueado_ate = {$bloqueio} WHERE id = :id")
            ->execute([':t' => $tentativas, ':id' => $u['id']]);
        return false;
    }

    $pdo->prepare('UPDATE usuarios SET tentativas_login = 0, bloqueado_ate = NULL WHERE id = :id')
        ->execute([':id' => $u['id']]);

    $u['senha'] = $u['senha_hash']; // compatibilidade com o restante do código
    return $u;
}

/**
 * Token CSRF por sessão.
 */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_campo() {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function csrf_valido($token) {
    return !empty($_SESSION['csrf_token']) && !empty($token) && hash_equals($_SESSION['csrf_token'], $token);
}
?>