<?php
// Centralized database configuration for the project.
// Values prefer environment variables when available to avoid hardcoding in code.

$DB_HOST = getenv('DB_HOST') ?: 'localhost';
$DB_USER = getenv('DB_USER') ?: 'root';
$DB_PASS = getenv('DB_PASS') ?: '';
$DB_NAME = getenv('DB_NAME') ?: 'rent_house';

if (!isset($conn) || $conn === null) {
    try {
        $conn = new PDO("mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8", $DB_USER, $DB_PASS);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        // Rethrow so callers can handle/report as appropriate.
        throw $e;
    }
}

function get_db_connection() {
    global $conn;
    return $conn;
}
