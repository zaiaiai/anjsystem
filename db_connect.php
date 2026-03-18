<?php
$host     = 'localhost';
$dbname   = 'dental_clinic';
$username = 'root';
$password = 'password'; // Leave blank for default WAMP — change if you set a root password

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}
?>