<?php
// Testes rodam contra um banco de testes dedicado — nunca contra o
// banco de desenvolvimento/produção.
//
//   DB_NAME=stockpro_test vendor/bin/phpunit

if (!str_ends_with(getenv('DB_NAME') ?: '', '_test')) {
    fwrite(STDERR, "\nDB_NAME precisa apontar para um banco de testes (ex.: stockpro_test).\n");
    fwrite(STDERR, "Rode: DB_NAME=stockpro_test vendor/bin/phpunit\n\n");
    exit(1);
}

require_once __DIR__ . '/../functions.php';
