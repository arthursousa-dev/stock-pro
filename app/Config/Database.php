<?php

namespace App\Config;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instancia = null;

    public static function getConnection(): PDO
    {
        if (self::$instancia === null) {
            self::carregarEnv(dirname(__DIR__, 2) . '/.env');
            $host = getenv('DB_HOST') ?: 'localhost';
            $port = getenv('DB_PORT') ?: '5432';
            $nome = getenv('DB_NAME') ?: 'stockpro';
            $user = getenv('DB_USER') ?: 'postgres';
            $pass = getenv('DB_PASS') ?: '';

            $dsn = "pgsql:host={$host};port={$port};dbname={$nome}";

            try {
                self::$instancia = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                http_response_code(500);
                die('Não foi possível conectar ao banco de dados.');
            }
        }

        return self::$instancia;
    }

    /** Lê CHAVE=valor do .env sem sobrescrever variáveis já definidas no ambiente (ex.: DB_NAME dos testes). */
    private static function carregarEnv(string $arquivo): void
    {
        if (!is_readable($arquivo)) {
            return;
        }
        foreach (file($arquivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linha) {
            if (str_starts_with(ltrim($linha), '#') || !str_contains($linha, '=')) {
                continue;
            }
            [$chave, $valor] = array_map('trim', explode('=', $linha, 2));
            if (getenv($chave) === false) {
                putenv("{$chave}={$valor}");
            }
        }
    }
}
