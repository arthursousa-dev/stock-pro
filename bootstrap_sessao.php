<?php
// bootstrap_sessao.php
//
// Endurece os cookies de sessão antes de iniciar a sessão:
// httponly (JS não lê o cookie), samesite=Lax (mitiga CSRF de
// origem cruzada) e secure quando a conexão é HTTPS.

$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => $https,
    'httponly' => true,
    'samesite' => 'Lax',
]);

session_start();
