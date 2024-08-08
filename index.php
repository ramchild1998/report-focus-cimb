<?php
include 'connection.php';
require 'vendor/autoload.php';

use Carbon\Carbon;
use Carbon\CarbonPeriod;
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/feather-icons/4.29.0/feather.min.js"
    crossorigin="anonymous"></script>
  <title>REPORT FOCUS CIMB NIAGA</title>
</head>

<body>

  <div class="container-xl px-4 mt-4">
    <div class="card mb-4">
      <div class="card-header d-flex flex-wrap align-items-center justify-content-between mb-1"><b>REPORT FOCUS</b>
        <form action="index.php" class="d-inline-flex gap-2 mb-sm-0 mb-1" method="GET">
          <select class="form-control ps-0 mb-1" name="month" id="monthpicker" data-bs-toggle="tooltip" data-bs-title="Pilih bulan untuk laporan">
            <?php
            $months = [
              '01' => 'Januari',
              '02' => 'Februari',
              '03' => 'Maret',
              '04' => 'April',
              '05' => 'Mei',
              '06' => 'Juni',
              '07' => 'Juli',
              '08' => 'Agustus',
              '09' => 'September',
              '10' => 'Oktober',
              '11' => 'November',
              '12' => 'Desember'
            ];
            $selectedMonth = isset($_GET['month']) ? $_GET['month'] : Carbon::now()->format('m');
            foreach ($months as $num => $name) {
              $selected = ($num == $selectedMonth) ? 'selected' : '';
              echo "<option value='$num' $selected>$name</option>";
            }
            ?>
          </select>
          <select class="form-control ps-0 mb-1" name="year" id="yearpicker" data-bs-toggle="tooltip" data-bs-title="Pilih tahun untuk laporan">
            <?php
            $currentYear = Carbon::now()->year;
            $selectedYear = isset($_GET['year']) ? $_GET['year'] : $currentYear;
            for ($year = $currentYear; $year >= $currentYear - 2; $year--) {
              $selected = ($year == $selectedYear) ? 'selected' : '';
              echo "<option value='$year' $selected>$year</option>";
            }
            ?>
          </select>
          <button type="submit" class="btn btn-primary">Filter</button>
        </form>
        <form action="exports.php" method="POST">
          <input type="hidden" name="month" id="exportMonth" value="<?php echo isset($selectedMonth) ? $selectedMonth : ''; ?>">
          <input type="hidden" name="year" id="exportYear" value="<?php echo isset($selectedYear) ? $selectedYear : ''; ?>">
          <button type="submit" class="btn btn-success">Export to Excel</button>
        </form>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table id="laporanTable" class="table table-striped table-bordered" style="width:100%">
            <thead>
              <tr>
                <th>No</th>
                <th>Vendor</th>
                <th>UserName</th>
                <th>ATM ID</th>
                <th>Location</th>
                <th>Start Date</th>
                <th>ATM Monthly Visit</th>
                <?php
                $selectedMonth = isset($_GET['month']) ? $_GET['month'] : Carbon::now()->format('m');
                $selectedYear = isset($_GET['year']) ? $_GET['year'] : Carbon::now()->year;
                $startOfMonth = Carbon::createFromDate($selectedYear, $selectedMonth, 1)->startOfMonth();
                $endOfMonth = Carbon::createFromDate($selectedYear, $selectedMonth, 1)->endOfMonth();
                $period = CarbonPeriod::create($startOfMonth, $endOfMonth);

                foreach ($period as $date) {
                  echo "<th>" . $date->format('l j') . "</th>";
                }
                ?>
                <th>Type Visit</th>
              </tr>
            </thead>
            <tbody>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/litepicker/dist/bundle.js"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <script>
    $(document).ready(function() {
      $('#laporanTable').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
          "url": "server_processing.php",
          "type": "POST",
          "data": function(d) {
            d.month = $('#monthpicker').val();
            d.year = $('#yearpicker').val();
          }
        },
        "order": [[ $('th').length - 1, "asc" ]]
      });
    });
  </script>

</body>

</html>
