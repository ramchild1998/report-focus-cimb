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
        <form action="export.php" method="POST">
          <input type="hidden" name="start_date" value="<?php echo isset($start_date) ? $start_date : ''; ?>">
          <input type="hidden" name="end_date" value="<?php echo isset($end_date) ? $end_date : ''; ?>">
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
                $startDate = Carbon::now()->startOfMonth();
                $endDate = Carbon::now()->endOfMonth();
                $period = CarbonPeriod::create($startDate, $endDate);

                foreach ($period as $date) {
                  echo "<th>" . $date->format('l j') . "</th>";
                }
                ?>
                <th> Type Visit </th>
              </tr>
            </thead>
            <tbody>
            <?php
            if (isset($_GET['date_range']) && !empty($_GET['date_range'])) {
              $date_range = explode(' - ', $_GET['date_range']);
              $start_date = $date_range[0];
              $end_date = $date_range[1];
              $sql = "SELECT 
                        atm.wsid AS ATM_ID,
                        vendor.name AS Vendor,
                        location.name AS Location,
                        agent_schedule.effective_date AS effective_date,
                        user.name AS UserName,
                        COUNT(schedule.id) AS visit_count
                      FROM 
                        focus_cimb.atm
                      LEFT JOIN 
                        focus_cimb.vendor ON vendor.id = atm.vendor_id
                      LEFT JOIN 
                        focus_cimb.location ON location.id = atm.location_id
                      INNER JOIN 
                        focus_cimb.schedule ON schedule.location_id = atm.location_id
                      LEFT JOIN 
                        focus_cimb.agent_schedule ON agent_schedule.id = schedule.agent_schedule_id
                      LEFT JOIN 
                        focus_cimb.user ON user.id = agent_schedule.agent_id
                      WHERE 
                        location.is_active = 1 AND
                        schedule.status = 'completed' AND
                        agent_schedule.effective_date BETWEEN '$start_date' AND '$end_date'
                      GROUP BY 
                        atm.wsid, 
                        vendor.name, 
                        location.name, 
                        user.name,
                        agent_schedule.effective_date
                      ORDER BY 
                        atm.wsid ASC";
            } else {
              $sql = "SELECT 
                        atm.wsid AS ATM_ID,
                        vendor.name AS Vendor,
                        location.name AS Location,
                        user.name AS UserName,
                        agent_schedule.effective_date AS effective_date,
                        COUNT(schedule.id) AS visit_count
                      FROM 
                        focus_cimb.atm
                      LEFT JOIN 
                        focus_cimb.vendor ON vendor.id = atm.vendor_id
                      LEFT JOIN 
                        focus_cimb.location ON location.id = atm.location_id
                      INNER JOIN
                        focus_cimb.schedule ON schedule.location_id = atm.location_id
                      LEFT JOIN 
                        focus_cimb.agent_schedule ON agent_schedule.id = schedule.agent_schedule_id
                      LEFT JOIN 
                        focus_cimb.user ON user.id = agent_schedule.agent_id
                      WHERE 
                        location.is_active = 1 AND
                        schedule.status = 'completed'
                      GROUP BY 
                        atm.wsid, 
                        vendor.name, 
                        location.name, 
                        user.name,
                        agent_schedule.effective_date
                      ORDER BY 
                        atm.wsid ASC";
            }

            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
              $no = 1;
              while($row = $result->fetch_assoc()) {
                $sqlRow = "SELECT 
                          schedule.assigned_date
                          FROM 
                          focus_cimb.atm
                          LEFT JOIN 
                          focus_cimb.location ON location.id = atm.location_id
                          INNER JOIN 
                          focus_cimb.schedule ON schedule.location_id = atm.location_id
                          WHERE 
                          location.is_active = 1 AND
                          schedule.status = 'completed' AND
                          atm.wsid= '". $row['ATM_ID'] ."'
                          ORDER BY 
                          atm.wsid ASC,
                          schedule.assigned_date ASC;";
                $resultRow = $conn->query($sqlRow);
                echo "<tr>";
                echo "<td>" . $no++ . "</td>";
                echo "<td>" . $row['Vendor'] . "</td>";
                echo "<td>" . $row['UserName'] . "</td>";
                echo "<td>" . $row['ATM_ID'] . "</td>";
                echo "<td>" . $row['Location'] . "</td>";
                echo "<td>" . $row['effective_date'] . "</td>";
                echo "<td>" . $row['visit_count'] . "</td>";
                $dateIterator = [];
                while ($iterator = $resultRow->fetch_assoc()) {
                    $dateIterator[] = $iterator;
                }
                foreach ($period as $date) {
                    foreach($dateIterator as $dateIteration){
                        $date2 = Carbon::parse($dateIteration['assigned_date']);
                        if($date->eq($date2)){
                            echo "<td>1</td>";
                            continue 2;
                        }
                    }
                    echo "<td>0</td>";
                }
                 echo "<td> Scheduled </td>";
                 echo "</tr>";
              }
            } else {
              echo "<tr><td colspan='7'>Tidak ada laporan!</td></tr>";
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
