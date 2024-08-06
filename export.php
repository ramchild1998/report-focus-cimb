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

// Dummy data
$data = [
    [
        'wsid' => 'ATM001',
        'vendor_name' => 'Vendor A',
        'user_name' => 'User A',
        'location_name' => 'Location A',
        'effective_date' => '2023-10-01 08:00:00',
        'atm_monthly_visit' => 5,
        'assigned_date' => '2023-10-01 08:00:00',
        'day' => 'Monday',
        'status' => 0
    ],
    // Tambahkan data dummy lainnya sesuai kebutuhan
];

$rowNum = 2;
$no = 1;
foreach ($data as $row) {
    $sheet->setCellValue('A' . $rowNum, $no++);
    $sheet->setCellValue('B' . $rowNum, $row['vendor_name']);
    $sheet->setCellValue('C' . $rowNum, $row['user_name']);
    $sheet->setCellValue('D' . $rowNum, $row['wsid']);
    $sheet->setCellValue('E' . $rowNum, $row['location_name']);
    $sheet->setCellValue('F' . $rowNum, Carbon::parse($row['effective_date'])->format('Y-m-d H:i:s'));
    $sheet->setCellValue('G' . $rowNum, $row['atm_monthly_visit']);
    
    $col = 'H';
    $status = 0;
    foreach ($period as $date) {
        $sheet->setCellValue($col . $rowNum, $status);
        $status = ($status == 0) ? 1 : 0;
        $col++;
    }
    $rowNum++;
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