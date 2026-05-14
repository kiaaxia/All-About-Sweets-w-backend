<?php
$host = "localhost";
$username = "root";
$password = "";
$database = "sweetsdb";

mysqli_report(MYSQLI_REPORT_OFF);
$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("Database connection failed. Please check db.php and phpMyAdmin database name: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");

function tableExists($conn, $tableName) {
    $tableName = mysqli_real_escape_string($conn, $tableName);
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$tableName'");
    return $result && mysqli_num_rows($result) > 0;
}

function columnExists($conn, $tableNames, $columnName) {
    $tableName = mysqli_real_escape_string($conn, $tableName);
    $columnName = mysqli_real_escape_string($conn, $columnName);
    $result = mysqli_query($conn, "SHOW COLUMNS FROM `$tableName` LIKE '$columnName'");
    return $result && mysqli_num_rows($result) > 0;
}

function safeText($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
