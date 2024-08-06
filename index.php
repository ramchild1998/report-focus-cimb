<?php
include 'connection.php';
?>

<h1>Form Laporan</h1>

<form action="simpan_laporan.php" method="post">
  <label>Tanggal:</label>
  <input type="date" name="tanggal"><br><br>
  <label>Judul:</label>
  <input type="text" name="judul"><br><br>
  <label>Isi Laporan:</label>
  <textarea name="isi"></textarea><br><br>
  <input type="submit" value="Simpan">
</form>

<script src="js/jquery-ui.js"></script>
<script>
  $(document).ready(function() {
    $("#tanggal").datepicker({
      dateFormat: "yy-mm-dd"
    });
  });
</script>