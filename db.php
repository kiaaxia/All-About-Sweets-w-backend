<?php
$conn = new mysqli("localhost", "root", "", "sweetsdb");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>