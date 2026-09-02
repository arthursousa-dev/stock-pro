<?php
function ler_json($arquivo) {
    if (!file_exists($arquivo)) return [];
    $fp = fopen($arquivo, 'r');
    if (!$fp) return [];
    flock($fp, LOCK_SH);
    $conteudo = fread($fp, filesize($arquivo) ?: 1);
    flock($fp, LOCK_UN);
    fclose($fp);
    if (empty($conteudo)) return [];
    $dados = json_decode($conteudo, true);
    return $dados ?? [];
}

function salvar_json($arquivo, $dados) {
    $fp = fopen($arquivo, 'c+');
    if (!$fp) return false;
    flock($fp, LOCK_EX);
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    flock($fp, LOCK_UN);
    fclose($fp);
    return true;
}

function gerar_id($arquivo, $chave_next = 'next_id') {
    $dados = ler_json($arquivo);
    $id = isset($dados[$chave_next]) ? (int)$dados[$chave_next] : 1;
    $dados[$chave_next] = $id + 1;
    salvar_json($arquivo, $dados);
    return $id;
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
 * Autentica contra db_usuarios.json (onde a senha é armazenada como hash bcrypt).
 * Retorna o registro do usuário ou false.
 */
function autenticar($email, $senha) {
    $db = ler_json('db_usuarios.json');
    foreach (($db['usuarios'] ?? []) as $u) {
        if (strtolower($u['email']) === strtolower($email) && password_verify($senha, $u['senha'] ?? '')) {
            return $u;
        }
    }
    return false;
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