<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "focus_cimb";

// Membuat koneksi
$conn = new mysqli($servername, $username, $password, $dbname);

// Memeriksa koneksi
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "<span style='color:green;'>&#9679;</span> Database Connected";
?>