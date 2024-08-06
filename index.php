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
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/litepicker/dist/css/litepicker.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/feather-icons/4.29.0/feather.min.js"
    crossorigin="anonymous"></script>
  <title>Laporan</title>
</head>

<body>

  <div class="container-xl px-4 mt-4">
    <div class="card mb-4">
      <div class="card-header d-flex flex-wrap align-items-center justify-content-between mb-1">Laporan
        <form action="index.php" class="d-inline-flex gap-2 mb-sm-0 mb-1" method="GET">
          <input class="form-control ps-0 mb-1" name="date_range" id="datepicker" placeholder="Select date range..."
            data-bs-toggle="tooltip" data-bs-title="Pilih rentang tanggal untuk laporan" />
          <button type="submit" class="btn btn-primary">Filter</button>
        </form>
        <a href="export.php?<?php echo $_SERVER['QUERY_STRING']; ?>" class="btn btn-success">Export to Excel</a>
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
                $startDate = Carbon::now()->startOfMonth();
                $endDate = Carbon::now()->endOfMonth();
                $period = CarbonPeriod::create($startDate, $endDate);

                foreach ($period as $date) {
                  echo "<th>" . $date->format('l j') . "</th>";
                }
                ?>
              </tr>
            </thead>
            <tbody>
            <?php
            // Data dummy sementara
            $dummyData = [
              [
                'vendor_name' => 'Vendor A',
                'user_name' => 'User A',
                'wsid' => 'ATM001',
                'location_name' => 'Location A',
                'effective_date' => Carbon::now()->subDays(10)->format('Y-m-d H:i:s'),
                'atm_monthly_visit' => 5,
                'status' => 1
              ],
              [
                'vendor_name' => 'Vendor B',
                'user_name' => 'User B',
                'wsid' => 'ATM002',
                'location_name' => 'Location B',
                'effective_date' => Carbon::now()->subDays(5)->format('Y-m-d H:i:s'),
                'atm_monthly_visit' => 3,
                'status' => 0
              ]
            ];

            $no = 1;
            foreach ($dummyData as $row) {
              echo "<tr>";
              echo "<td>" . $no++ . "</td>";
              echo "<td>" . $row['vendor_name'] . "</td>";
              echo "<td>" . $row['user_name'] . "</td>";
              echo "<td>" . $row['wsid'] . "</td>";
              echo "<td>" . $row['location_name'] . "</td>";
              echo "<td>" . $row['effective_date'] . "</td>";
              echo "<td>" . $row['atm_monthly_visit'] . "</td>";
              $status = 0;
              foreach ($period as $date) {
                echo "<td>" . $status . "</td>";
                $status = ($status == 0) ? 1 : 0;
              }
              echo "</tr>";
            }
            ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/litepicker/dist/bundle.js"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
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

    $(document).ready(function() {
      $('#laporanTable').DataTable();
    });
  </script>

</body>

</html>