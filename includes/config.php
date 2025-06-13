<?php
$host = "localhost";
$username = "root";
$password = "";
$database = "alhijrah_trust";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$DEADLINES = [
    'dg_khan' => '2023-12-15',
    'ziarat' => '2023-12-20'
];

?>