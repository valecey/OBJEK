<?php
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'objek';

// paiseh this db.local.php is for cj, your version should just ignore
$localConfigPath = __DIR__ . '/db.local.php';
if (is_file($localConfigPath)) {
    $localConfig = require $localConfigPath;
    $DB_HOST = $localConfig['host'] ?? $DB_HOST;
    $DB_USER = $localConfig['user'] ?? $DB_USER;
    $DB_PASS = $localConfig['password'] ?? $DB_PASS;
    $DB_NAME = $localConfig['database'] ?? $DB_NAME;
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    exit('Could not connect to the database. Check that MySQL is running and that objek.sql has been imported.');
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
