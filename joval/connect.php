<?php
$host = "localhost";
$username = "root";
$password = "mycokhan";
$dbname = "joval_microfinance";

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>