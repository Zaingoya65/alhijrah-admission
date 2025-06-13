<?php
$host = "localhost";
$username = "u438663390_Alhijrahtrust1";
$password = "Alhijrahtrust1";
$database = "u438663390_Alhijrahtrust1";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$DEADLINES = [
    'dg_khan' => '2023-12-15',
    'ziarat' => '2023-12-20'
];

?>