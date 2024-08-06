<?php
include 'connection.php';

$tanggal = $_POST['tanggal'];
$judul = $_POST['judul'];
$isi = $_POST['isi'];

$sql = "INSERT INTO laporan (tanggal, judul, isi) VALUES ('$tanggal', '$judul', '$isi')";
if ($conn->query($sql) === TRUE) {
  echo "Laporan berhasil disimpan!";
} else {
  echo "Error: " . $sql . "<br>" . $conn->error;
}

$conn->close();
?>