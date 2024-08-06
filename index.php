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
            if (isset($_GET['date_range']) && !empty($_GET['date_range'])) {
              $date_range = explode(' - ', $_GET['date_range']);
              $start_date = Carbon::createFromFormat('Y-m-d', $date_range[0]);
              $end_date = Carbon::createFromFormat('Y-m-d', $date_range[1]);
              $sql = "SELECT atm.wsid, vendor.name as vendor_name, user.name as user_name, location.name as location_name, 
                      agent_schedule.effective_date, location.atm_monthly_visit, schedule.assigned_date, schedule.day, schedule.status 
                      FROM atm 
                      JOIN vendor ON atm.vendor_id = vendor.id 
                      JOIN schedule ON schedule.location_id = atm.location_id 
                      JOIN agent_schedule ON schedule.agent_schedule_id = agent_schedule.id 
                      JOIN user ON agent_schedule.agent_id = user.id
                      JOIN location ON atm.location_id = location.id 
                      WHERE schedule.assigned_date BETWEEN '" . $start_date->format('Y-m-d') . "' AND '" . $end_date->format('Y-m-d') . "'";
            } else {
              $sql = "SELECT atm.wsid, vendor.name as vendor_name, user.name as user_name, location.name as location_name, 
                      agent_schedule.effective_date, location.atm_monthly_visit, schedule.assigned_date, schedule.day, schedule.status 
                      FROM atm 
                      JOIN vendor ON atm.vendor_id = vendor.id 
                      JOIN schedule ON schedule.location_id = atm.location_id 
                      JOIN agent_schedule ON schedule.agent_schedule_id = agent_schedule.id 
                      JOIN user ON agent_schedule.agent_id = user.id 
                      JOIN location ON atm.location_id = location.id";
            }

            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
              $no = 1;
              while($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $no++ . "</td>";
                echo "<td>" . $row['vendor_name'] . "</td>";
                echo "<td>" . $row['user_name'] . "</td>";
                echo "<td>" . $row['wsid'] . "</td>";
                echo "<td>" . $row['location_name'] . "</td>";
                echo "<td>" . Carbon::parse($row['effective_date'])->format('Y-m-d H:i:s') . "</td>";
                echo "<td>" . $row['atm_monthly_visit'] . "</td>";
                foreach ($period as $date) {
                  $status = ($row['status'] == 0) ? 0 : 1;
                  echo "<td>" . $status . "</td>";
                }
                echo "</tr>";
              }
            } else {
              echo "<tr><td colspan='37'>Tidak ada laporan!</td></tr>";
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