<?php
include 'config/connection.php';
session_start();

require_once 'vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

// FIX 1: Role was 'admin' only — managers were being locked out.
require_once 'check_role.php';
requireLogin();
requireRole(['ceo', 'manager1', 'manager2', 'p_assistant1', 'p_assistant2']);

// FIX 2: Was pulling ALL branches — now scoped to the logged-in user's branch.
$userInfo    = getUserInfo($_SESSION['id']);
$branch_id   = $userInfo['branch_id'] ?? null;
$branch_name = ($branch_id == 1) ? 'Branch 1' : 'Branch 2';

$filterType  = isset($_GET['filter']) && in_array($_GET['filter'], ['daily', 'monthly']) ? $_GET['filter'] : 'daily';
$filterDate  = isset($_GET['date'])  ? $_GET['date']  : date('Y-m-d');
$filterMonth = isset($_GET['month']) ? $_GET['month'] : date('Y-m');

if ($filterType === 'monthly') {
    $where        = "DATE_FORMAT(created_at, '%Y-%m') = ?";
    $param        = $filterMonth;
    $period_label = date('F Y', strtotime($filterMonth . '-01'));
} else {
    $where        = "DATE(created_at) = ?";
    $param        = $filterDate;
    $period_label = date('M d, Y', strtotime($filterDate));
}

// FIX 3: Added branch_id to WHERE clause and updated bind_param to "si". Also added first_name, last_name
$query = "SELECT reservation_id, first_name, last_name, medicine, price, quantity, created_at
          FROM reservations
          WHERE $where AND branch_id = ? AND status = 'Approved'
          ORDER BY created_at ASC";
$stmt = $conn->prepare($query);
$stmt->bind_param("si", $param, $branch_id);
$stmt->execute();
$result = $stmt->get_result();

$total_all    = 0;
$record_count = 0;
$records      = [];

while ($row = $result->fetch_assoc()) {
    $records[]  = $row;
    $total_all += $row['price'] * $row['quantity'];
    $record_count++;
}

$html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: "DejaVu Sans", sans-serif; color: #333; line-height: 1.6; }
        .container { padding: 40px 30px; max-width: 900px; margin: 0 auto; }
        .header { border-bottom: 3px solid #7393A7; padding-bottom: 20px; margin-bottom: 30px; text-align: center; }
        .header h1 { font-size: 24pt; text-transform: uppercase; letter-spacing: 1px; color: #333; margin-bottom: 4px; }
        .header p { font-size: 11pt; color: #666; }
        .info-row { display: flex; justify-content: space-between; margin-top: 10px; font-size: 10pt; color: #666; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        thead { background-color: #7393A7; color: white; }
        th { text-align: left; font-size: 11pt; font-weight: 600; padding: 12px; border: 1px solid #7393A7; }
        td { padding: 11px 12px; font-size: 10pt; border: 1px solid #e0e0e0; }
        tbody tr:nth-child(even) { background-color: #f9f9f9; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .total-section { margin-top: 20px; padding-top: 20px; border-top: 2px solid #7393A7; }
        .total-row { display: flex; justify-content: space-between; font-weight: 600; font-size: 12pt; padding: 10px 0; }
        .total-row .amount { font-size: 13pt; color: #7393A7; }
        .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 9pt; color: #999; text-align: center; }
        .no-data { text-align: center; padding: 30px; font-size: 11pt; color: #999; }
    </style>
</head>
<body><div class="container">
    <div class="header">
        <h1>PharmAssist Approved Reservations Report</h1>
        <p>' . htmlspecialchars($branch_name) . '</p>
        <div class="info-row">
            <span>Period: ' . htmlspecialchars($period_label) . '</span>
            <span>Generated: ' . date('M d, Y H:i') . '</span>
        </div>
    </div>';

if ($record_count > 0) {
    $html .= '<table><thead><tr>
        <th>Order #</th><th>Patient Name</th><th>Medicine</th>
        <th class="text-right">Unit Price</th>
        <th class="text-center">Qty</th>
        <th class="text-right">Total</th>
    </tr></thead><tbody>';

    foreach ($records as $row) {
        $line_total = $row['price'] * $row['quantity'];
        $fullName = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
        $html .= '<tr>
            <td>#' . $row['reservation_id'] . '</td>
            <td>' . htmlspecialchars($fullName) . '</td>
            <td>' . htmlspecialchars($row['medicine']) . '</td>
            <td class="text-right">&#8369;' . number_format($row['price'], 2) . '</td>
            <td class="text-center">' . $row['quantity'] . '</td>
            <td class="text-right">&#8369;' . number_format($line_total, 2) . '</td>
        </tr>';
    }

    $html .= '</tbody></table>
    <div class="total-section">
        <div class="total-row">
            <span>Total Approved Orders:</span>
            <span>' . $record_count . '</span>
        </div>
        <div class="total-row">
            <span>Grand Total:</span>
            <span class="amount">' . number_format($total_all, 2) . '</span>
        </div>
    </div>';
} else {
    $html .= '<div class="no-data">No approved sales found for this period.</div>';
}

$html .= '<div class="footer">
    <p>PharmAssist &mdash; Approved Report | For official use only</p>
    <p>This is a computer-generated document.</p>
</div></div></body></html>';

try {
    $options = new Options();
    $options->set('defaultFont', 'Helvetica');
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isPhpEnabled', false);
    $options->set('isFontSubsettingEnabled', true);

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $filename = 'Sales_Report_' . ($filterType === 'monthly' ? $filterMonth : $filterDate) . '.pdf';
    $dompdf->stream($filename, ['Attachment' => true]);
    exit();
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Error generating PDF: ' . $e->getMessage()]);
    exit();
}
?>