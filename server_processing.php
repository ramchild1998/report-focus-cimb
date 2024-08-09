<?php
include 'connection.php';
require 'vendor/autoload.php';

use Carbon\Carbon;
use Carbon\CarbonPeriod;

// Set header untuk JSON response
header('Content-Type: application/json');

// Error handling
function handleError($errno, $errstr, $errfile, $errline) {
    $error = [
        "error" => true,
        "message" => "$errstr in $errfile on line $errline"
    ];
    echo json_encode($error);
    file_put_contents('error.log', json_encode($error));
    exit();
}
set_error_handler("handleError");

// Periksa koneksi database
if ($conn->connect_error) {
    handleError(E_USER_ERROR, "Connection failed: " . $conn->connect_error, __FILE__, __LINE__);
}

// Validasi parameter `draw`
$draw = isset($_POST['draw']) ? intval($_POST['draw']) : 0;
$start = isset($_POST['start']) ? intval($_POST['start']) : 0;
$length = isset($_POST['length']) ? intval($_POST['length']) : 10;

$month = $_POST['month'] ?? Carbon::now()->format('m');
$year = $_POST['year'] ?? Carbon::now()->year;
$office = $_POST['office'] ?? 83;
$start_month = Carbon::createFromDate($year, $month, 1)->startOfMonth()->format('Y-m-d H:i:s');
$end_month = Carbon::createFromDate($year, $month, 1)->endOfMonth()->format('Y-m-d H:i:s');

// Query untuk data terjadwal
$sqlScheduled = "SELECT 
          atm.wsid AS ATM_ID,
          vendor.name AS Vendor,
          location.name AS Location,
          agent_schedule.effective_date AS effective_date,
          user.name AS UserName,
          COUNT(schedule.id) AS visit_count,
          'Scheduled' AS visit_type
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
          location.office_id = $office AND
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

// Query untuk data tidak terjadwal
$sqlUnscheduled = "
        SELECT 
            atm.wsid AS ATM_ID,
            vendor.name AS Vendor,
            location.name AS Location,
            user.name AS UserName,
            user.id AS agent_id,
            location.id AS location_id,
            COUNT(unscheduled_visit.id) AS visit_count,
            'Unscheduled' AS visit_type
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
            location.office_id = $office AND
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
            atm.wsid ASC";

$resultScheduled = $conn->query($sqlScheduled);
$resultUnscheduled = $conn->query($sqlUnscheduled);

// Gabungkan hasil query
$combinedResults = [];
if ($resultScheduled->num_rows > 0) {
    while ($row = $resultScheduled->fetch_assoc()) {
        $combinedResults[] = $row;
    }
}
if ($resultUnscheduled->num_rows > 0) {
    while ($row = $resultUnscheduled->fetch_assoc()) {
        $combinedResults[] = $row;
    }
}

// Total records tanpa limit
$totalRecords = count($combinedResults);

// Terapkan batasan LIMIT pada hasil gabungan
$paginatedResults = array_slice($combinedResults, $start, $length);

$data = [];
$no = $start + 1;

foreach ($paginatedResults as $row) {
    $sqlRow = $row['visit_type'] === 'Scheduled' ? 
        "SELECT 
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
            atm.wsid= '". $row['ATM_ID'] ."' AND
            schedule.assigned_date BETWEEN '". $start_month . "' AND '" . $end_month ."'
        ORDER BY 
            atm.wsid ASC,
            schedule.assigned_date ASC;" :
        "SELECT 
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
            location.id= '". $row['location_id'] ."' AND
            unscheduled_visit.assigned_date BETWEEN '". $start_month . "' AND '" . $end_month ."'
        ORDER BY 
            atm.wsid ASC,
            unscheduled_visit.assigned_date ASC;";

    $resultRow = $conn->query($sqlRow);
    $rowData = [
        $no++,
        $row['Vendor'],
        $row['UserName'],
        $row['ATM_ID'],
        $row['Location'],
        $row['effective_date'] ?? '',
        $row['visit_count']
    ];

    // Tambahkan kolom untuk setiap hari dalam periode
    $startOfMonth = Carbon::createFromDate($year, $month, 1)->startOfMonth();
    $endOfMonth = Carbon::createFromDate($year, $month, 1)->endOfMonth();
    $period = CarbonPeriod::create($startOfMonth, $endOfMonth);

    $dateIterator = [];
    while ($iterator = $resultRow->fetch_assoc()) {
        $dateIterator[] = $iterator;
    }

    foreach ($period as $date) {
        foreach($dateIterator as $dateIteration){
            $date2 = Carbon::parse($dateIteration['assigned_date']);
            if($date->startOfDay()->eq($date2->startOfDay())){
                $rowData[] = 1;
                continue 2;
            }
        }
        $rowData[] = 0;
    }

    $rowData[] = $row['visit_type'];
    $data[] = $rowData;
}

$response = [
    "draw" => $draw,
    "recordsTotal" => $totalRecords,
    "recordsFiltered" => $totalRecords,
    "data" => $data
];

echo json_encode($response);

// Tambahkan ini untuk memeriksa output JSON
file_put_contents('debug.log', json_encode($response));
?>
