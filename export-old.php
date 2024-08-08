<?php
include 'connection.php';
require 'vendor/autoload.php';

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$headerStyleArray = [
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
        ],
    ],
    'font' => [
        'bold' => true,
        'color' => ['argb' => 'FFFFFFFF'],
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => [
            'argb' => 'FF4CAF50',
        ],
    ],
];

$bodyStyleArray = [
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
        ],
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
    ],
];

$sheet->setCellValue('A1', 'No');
$sheet->setCellValue('B1', 'Vendor');
$sheet->setCellValue('C1', 'UserName');
$sheet->setCellValue('D1', 'ATM ID');
$sheet->setCellValue('E1', 'Location');
$sheet->setCellValue('F1', 'Start Date');
$sheet->setCellValue('G1', 'ATM Monthly Visit');

$selectedMonth = isset($_POST['month']) ? $_POST['month'] : Carbon::now()->format('m');
$selectedYear = isset($_POST['year']) ? $_POST['year'] : Carbon::now()->year;
$startDate = Carbon::createFromDate($selectedYear, $selectedMonth, 1)->startOfMonth()->startOfDay()->format("Y-m-d H:i:s");
$endDate = Carbon::createFromDate($selectedYear, $selectedMonth, 1)->endOfMonth()->endOfDay()->format("Y-m-d H:i:s");
$period = CarbonPeriod::create($startDate, $endDate);

$col = 'G';
foreach ($period as $date) {
    $col++;
    $sheet->setCellValue($col . '1', $date->format('l j'));
}
$col++;
$sheet->setCellValue($col . '1', 'Type Visit');

$sheet->getStyle('A1:' . $col . '1')->applyFromArray($headerStyleArray);
$sheet->setAutoFilter('A1:' . $col . '1');

$sqlScheduled = "SELECT 
                  atm.wsid AS ATM_ID,
                  vendor.name AS Vendor,
                  location.name AS Location,
                  user.name AS UserName,
                  agent_schedule.effective_date AS Start_Date,
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
                  agent_schedule.effective_date BETWEEN '$startDate' AND '$endDate'
                GROUP BY 
                  atm.wsid, 
                  vendor.name, 
                  location.name, 
                  user.name,
                  agent_schedule.effective_date
                ORDER BY 
                  atm.wsid ASC";

$sqlUnscheduled = "SELECT 
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
                    unscheduled_visit.status = 'completed' AND
                    unscheduled_visit.assigned_date BETWEEN '$startDate' AND '$endDate'
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

$rowNum = 2;
$no = 1;

function fillSheet($conn, $result, $sheet, $period, $bodyStyleArray, &$rowNum, &$no, $type) {
    while ($row = $result->fetch_assoc()) {
        $sheet->setCellValue('A' . $rowNum, $no++);
        $sheet->setCellValue('B' . $rowNum, $row['Vendor']);
        $sheet->setCellValue('C' . $rowNum, $row['UserName']);
        $sheet->setCellValue('D' . $rowNum, $row['ATM_ID']);
        $sheet->setCellValue('E' . $rowNum, $row['Location']);
        $sheet->setCellValue('F' . $rowNum, isset($row['Start_Date']) ? Carbon::parse($row['Start_Date'])->format('Y-m-d') : '');
        $sheet->setCellValue('G' . $rowNum, $row['visit_count']);
        
        $sheet->getStyle('A' . $rowNum . ':G' . $rowNum)->applyFromArray($bodyStyleArray);

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
        $dateIterator = [];
        while ($iterator = $resultRow->fetch_assoc()) {
            $dateIterator[] = $iterator;
        }
        $col = 'H';
        foreach ($period as $date) {
            $status = 0;
            foreach($dateIterator as $dateIteration){
                $date2 = Carbon::parse($dateIteration['assigned_date']);
                if($date->eq($date2)){
                    $status = 1;
                    break;
                }
            }
            $sheet->setCellValue($col . $rowNum, $status);
            $sheet->getStyle($col . $rowNum)->applyFromArray($bodyStyleArray);
            $sheet->getStyle($col . $rowNum)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $col++;
        }
        $sheet->setCellValue($col . $rowNum, $type);
        $rowNum++;
    }
}

if ($resultScheduled->num_rows > 0) {
    fillSheet($conn, $resultScheduled, $sheet, $period, $bodyStyleArray, $rowNum, $no, 'Scheduled');
}

if ($resultUnscheduled->num_rows > 0) {
    fillSheet($conn, $resultUnscheduled, $sheet, $period, $bodyStyleArray, $rowNum, $no, 'Unscheduled');
}

// Set column width to auto size based on content
foreach ($sheet->getColumnIterator() as $column) {
    $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
}

$writer = new Xlsx($spreadsheet);
$filename = 'focus_cimb_' . date('Ymd_His') . '.xlsx';

// Clear output buffer to prevent any additional output
if (ob_get_length()) {
    ob_end_clean();
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Cache-Control: cache, must-revalidate');
header('Pragma: public');

$writer->save('php://output');
exit;
?>