<?php
/**
 * db.php
 * Returns a single shared PDO instance. Always used with prepared
 * statements / bound parameters elsewhere in the app to prevent SQL
 * injection — never build queries by string concatenation.
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';

function get_db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_NAME);

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false, // use real prepared statements
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Never leak DB details to the client.
            error_log('DB connection failed: ' . $e->getMessage());
            http_response_code(500);
            exit('Service temporarily unavailable.');
        }
    }

    return $pdo;
}
