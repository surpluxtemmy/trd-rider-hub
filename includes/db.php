<?php
declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $host = defined('DB_HOST') ? DB_HOST : 'localhost';
    $name = defined('DB_NAME') ? DB_NAME : '';
    $user = defined('DB_USER') ? DB_USER : '';
    $pass = defined('DB_PASS') ? DB_PASS : '';
    $charset = defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4';
    $dsn = 'mysql:host=' . $host . ';dbname=' . $name . ';charset=' . $charset;
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}

function db_connect_raw(string $host, string $name, string $user, string $pass): PDO
{
    $dsn = 'mysql:host=' . $host . ';dbname=' . $name . ';charset=utf8mb4';
    return new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

function q(string $sql, array $args = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute($args);
    return $st;
}

function qone(string $sql, array $args = []): ?array
{
    $row = q($sql, $args)->fetch();
    return $row === false ? null : $row;
}

function qall(string $sql, array $args = []): array
{
    return q($sql, $args)->fetchAll();
}

function qcol(string $sql, array $args = []): array
{
    return q($sql, $args)->fetchAll(PDO::FETCH_COLUMN);
}
