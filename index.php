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
        <form action="export.php" method="POST">
          <input type="hidden" name="month" value="<?php echo isset($selectedMonth) ? $selectedMonth : ''; ?>">
          <input type="hidden" name="year" value="<?php echo isset($selectedYear) ? $selectedYear : ''; ?>">
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
                <th> Type Visit </th>
              </tr>
            </thead>
            <tbody>
            <?php
            if (isset($_GET['month']) && !empty($_GET['month']) && isset($_GET['year']) && !empty($_GET['year'])) {
              $month = $_GET['month'];
              $year = $_GET['year'];
              $start_month = $year . '-' . $month . '-01';
              $end_month = Carbon::parse($start_month)->endOfMonth()->endOfDay()->format('Y-m-d H:i:s');
              $start_month = Carbon::parse($start_month)->startOfDay()->format('Y-m-d H:i:s');
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
                        agent_schedule.effective_date BETWEEN '$start_month' AND '$end_month'
                      GROUP BY 
                        atm.wsid, 
                        vendor.name, 
                        location.name, 
                        user.name,
                        agent_schedule.effective_date
                      ORDER BY 
                        atm.wsid ASC";
              $sqlUnscheduled = "
                      SELECT 
                          atm.wsid AS ATM_ID,
                          vendor.name AS Vendor,
                          location.name AS Location,
                          user.name AS UserName,
                          user.id AS agent_id,
                          location.id AS location_id,
                          COUNT(unscheduled_visit.id) AS visit_count
                      FROM 
                          focus_cimb.atm
                      LEFT JOIN 
                          focus_cimb.vendor ON vendor.id = atm.vendor_id
                      LEFT JOIN 
                          focus_cimb.location ON location.id = atm.location_id
                      JOIN 
                          focus_cimb.unscheduled_visit ON unscheduled_visit.location_id = atm.location_id
                      LEFT JOIN 
                          focus_cimb.user ON user.id = unscheduled_visit.agent_id
                      WHERE 
                          location.is_active = 1 AND
                          unscheduled_visit.status = 'completed' AND
                          unscheduled_visit.assigned_date BETWEEN '$start_month' AND '$end_month'
                      GROUP BY 
                          atm.wsid, 
                          vendor.name, 
                          location.name, 
                          user.name,
                          user.id,
                          location.id
                      ORDER BY 
                          atm.wsid ASC;";
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
              $sqlUnscheduled = "
                      SELECT 
                          atm.wsid AS ATM_ID,
                          vendor.name AS Vendor,
                          location.name AS Location,
                          user.name AS UserName,
                          user.id AS agent_id,
                          location.id AS location_id,
                          COUNT(unscheduled_visit.id) AS visit_count
                      FROM 
                          focus_cimb.atm
                      LEFT JOIN 
                          focus_cimb.vendor ON vendor.id = atm.vendor_id
                      LEFT JOIN 
                          focus_cimb.location ON location.id = atm.location_id
                      JOIN 
                          focus_cimb.unscheduled_visit ON unscheduled_visit.location_id = atm.location_id
                      LEFT JOIN 
                          focus_cimb.user ON user.id = unscheduled_visit.agent_id
                      WHERE 
                          location.is_active = 1 AND
                          unscheduled_visit.status = 'completed'
                      GROUP BY 
                          atm.wsid, 
                          vendor.name, 
                          location.name, 
                          user.name,
                          user.id,
                          location.id
                      ORDER BY 
                          atm.wsid ASC;";
            }

            $result = $conn->query($sql);
            $no = 1;
            $resultUnscheduled = $conn->query($sqlUnscheduled);
            if ($result->num_rows > 0) {
             
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
            }
            if ($resultUnscheduled->num_rows > 0) {
        
              while($row = $resultUnscheduled->fetch_assoc()) {
                $sqlRow = "SELECT 
                          unscheduled_visit.assigned_date
                          FROM 
                          focus_cimb.atm
                          LEFT JOIN 
                          focus_cimb.location ON location.id = atm.location_id
                          INNER JOIN 
                          focus_cimb.unscheduled_visit ON unscheduled_visit.location_id = atm.location_id
                          LEFT JOIN 
                          focus_cimb.user ON user.id = unscheduled_visit.agent_id
                          WHERE 
                          location.is_active = 1 AND
                          unscheduled_visit.status = 'completed' AND
                          atm.wsid= '". $row['ATM_ID'] ."' AND
                          user.id= '". $row['agent_id'] ."' AND
                          location.id= '". $row['location_id'] ."'
                          ORDER BY 
                          atm.wsid ASC,
                          unscheduled_visit.assigned_date ASC;";
                $resultRow = $conn->query($sqlRow);
                echo "<tr>";
                echo "<td>" . $no++ . "</td>";
                echo "<td>" . $row['Vendor'] . "</td>";
                echo "<td>" . $row['UserName'] . "</td>";
                echo "<td>" . $row['ATM_ID'] . "</td>";
                echo "<td>" . $row['Location'] . "</td>";
                echo "<td>" . (isset($row['effective_date']) ? $row['effective_date'] : '' ) . "</td>";
                echo "<td>" . $row['visit_count'] . "</td>";
                $dateIterator = [];
                while ($iterator = $resultRow->fetch_assoc()) {
                    $dateIterator[] = $iterator;
                }
                foreach ($period as $date) {
                    foreach($dateIterator as $dateIteration){
                        $date2 = Carbon::parse($dateIteration['assigned_date'])->startOfDay();
                        if($date->startOfDay()->eq($date2)){
                            echo "<td>1</td>";
                            continue 2;
                        }
                    }
                    echo "<td>0</td>";
                }
                 echo "<td> Unscheduled </td>";
                 echo "</tr>";
              }
            }
            if ($resultUnscheduled->num_rows < 1 && $result->num_rows < 1) {
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
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <script>
    $(document).ready(function() {
      $('#laporanTable').DataTable({
        "order": [[ $('th').length - 1, "asc" ]]
      });
    });
  </script>

</body>

</html>
