<?php
include 'connection.php';
require 'vendor/autoload.php';

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$sheet->setCellValue('A1', 'No');
$sheet->setCellValue('B1', 'Vendor');
$sheet->setCellValue('C1', 'UserName');
$sheet->setCellValue('D1', 'ATM ID');
$sheet->setCellValue('E1', 'Location');
$sheet->setCellValue('F1', 'Start Date');
$sheet->setCellValue('G1', 'ATM Monthly Visit');

$startDate = Carbon::now()->startOfMonth();
$endDate = Carbon::now()->endOfMonth();
$period = CarbonPeriod::create($startDate, $endDate);

$col = 'H';
foreach ($period as $date) {
    $sheet->setCellValue($col . '1', $date->format('l j'));
    $col++;
}

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
    $rowNum = 2;
    $no = 1;
    while($row = $result->fetch_assoc()) {
        $sheet->setCellValue('A' . $rowNum, $no++);
        $sheet->setCellValue('B' . $rowNum, $row['vendor_name']);
        $sheet->setCellValue('C' . $rowNum, $row['user_name']);
        $sheet->setCellValue('D' . $rowNum, $row['wsid']);
        $sheet->setCellValue('E' . $rowNum, $row['location_name']);
        $sheet->setCellValue('F' . $rowNum, Carbon::parse($row['effective_date'])->format('Y-m-d H:i:s'));
        $sheet->setCellValue('G' . $rowNum, $row['atm_monthly_visit']);
        
        $col = 'H';
        foreach ($period as $date) {
            $status = ($row['status'] == 0) ? 'open' : 'done';
            $sheet->setCellValue($col . $rowNum, $row['day'] . " " . Carbon::parse($row['assigned_date'])->format('Y-m-d H:i:s') . " (" . $status . ")");
            $col++;
        }
        $rowNum++;
    }
}

$writer = new Xlsx($spreadsheet);
$filename = 'Laporan_' . date('Ymd_His') . '.xlsx';

// Clear output buffer to prevent any additional output
if (ob_get_length()) {
    ob_end_clean();
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');
header('Cache-Control: max-age=1');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Cache-Control: cache, must-revalidate');
header('Pragma: public');

$writer->save('php://output');
exit;
?>