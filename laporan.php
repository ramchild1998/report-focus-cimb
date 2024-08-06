<?php
include 'connection.php';
?>

<h1>Laporan</h1>

<table border="1">
  <tr>
    <th>Tanggal</th>
    <th>Judul</th>
    <th>Isi Laporan</th>
  </tr>
  <?php
  $sql = "SELECT * FROM laporan";
  $result = $conn->query($sql);
  if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
      echo "<tr>";
      echo "<td>" . $row['tanggal'] . "</td>";
      echo "<td>" . $row['judul'] . "</td>";
      echo "<td>" . $row['isi'] . "</td>";
      echo "</tr>";
    }
  } else {
    echo "Tidak ada laporan!";
  }
  ?>
</table>