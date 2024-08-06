<?php
include 'connection.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/litepicker/dist/css/litepicker.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/feather-icons/4.29.0/feather.min.js"
    crossorigin="anonymous"></script>
  <title>Laporan</title>
</head>

<body>

  <div class="container-xl px-4 mt-n10">
    <div class="card mb-4">
      <div class="card-header d-flex flex-wrap align-items-center justify-content-between mb-1">Laporan
        <form action="laporan.php" class="d-inline-flex gap-2 mb-sm-0 mb-1" method="GET">
          <input class="form-control ps-0 mb-1" name="date_range" id="datepicker" placeholder="Select date range..."
            data-bs-toggle="tooltip" data-bs-title="Pilih rentang tanggal untuk laporan" />
          <button type="submit" class="btn btn-primary">Filter</button>
        </form>
      </div>
    </div>
  </div>

  <table border="1">
    <tr>
      <th>Tanggal</th>
      <th>Judul</th>
      <th>Isi Laporan</th>
    </tr>
    <?php
    if (isset($_GET['date_range'])) {
      $date_range = explode(' - ', $_GET['date_range']);
      $start_date = $date_range[0];
      $end_date = $date_range[1];
      $sql = "SELECT * FROM laporan WHERE tanggal BETWEEN '$start_date' AND '$end_date'";
    } else {
      $sql = "SELECT * FROM laporan";
    }

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
      echo "<tr><td colspan='3'>Tidak ada laporan!</td></tr>";
    }
    ?>
  </table>

  <script src="https://cdn.jsdelivr.net/npm/litepicker/dist/bundle.js"></script>
  <script>
    const litepickerRangePluginReport = document.getElementById('datepicker');
    if (litepickerRangePluginReport) {
      new Litepicker({
        element: litepickerRangePluginReport,
        singleMode: false,
        numberOfMonths: 2,
        numberOfColumns: 2,
        format: 'YYYY-MM-DD',
        plugins: ['ranges'],
        setup: (picker) => {
          picker.on('selected', (date1, date2) => {
            document.dispatchEvent(new CustomEvent('dateRangeChanged'))
          })
        }
      });
    }
  </script>

</body>

</html>