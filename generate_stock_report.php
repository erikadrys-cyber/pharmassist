<?php
ob_start(); 
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'vendor/autoload.php';
include 'config/connection.php';

date_default_timezone_set('Asia/Manila');

use Dompdf\Dompdf;
use Dompdf\Options;


if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}


$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);


$logoPath = __DIR__ . '/website_icon/web_logo.png'; 

$logoBase64 = '';

if (file_exists($logoPath)) {
    $imageData = file_get_contents($logoPath);
    $logoBase64 = 'data:image/png;base64,' . base64_encode($imageData);
} else {
    die("⚠️ Logo not found at: " . $logoPath); 
}

$query = "
    SELECT 
        m.medicine_id,
        m.medicine_name,
        m.category,
        m.price,
        m.branch,
        b.stock_quantity,
        COALESCE(b.stocks_status, 'Available') AS stocks_status,
        m.created_at
    FROM medicine m
    INNER JOIN branch_inventory b ON m.medicine_id = b.medicine_id
    ORDER BY m.created_at DESC
";

$result = mysqli_query($conn, $query);
if (!$result) {
    die('Query error: ' . mysqli_error($conn));
}

$html = '
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>PharmAssist Stock Report</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,300;500;700&family=Tinos:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
<style>
    body {
        font-family: "Tinos", serif;
        margin: 45px;
        color: #333;
        background-color: #fff;
        line-height: 1.5;
    }
    .header {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        margin-bottom: 10px;
    }
    .header img {
        height: 65px;
        margin-bottom: 5px;
    }
    .title {
        font-family: "Bricolage Grotesque", sans-serif;
        font-weight: 700;
        font-size: 28px;
        color: #7393A7;
        letter-spacing: 0.5px;
        margin: 0;
    }
    hr {
        border: none;
        height: 2px;
        background-color: #7393A7;
        width: 100px;
        margin: 8px auto 25px;
        border-radius: 3px;
    }
    .subtitle {
        text-align: center;
        font-size: 14px;
        color: #666;
        margin-bottom: 25px;
        font-family: "Bricolage Grotesque", sans-serif;
        font-weight: 400;
    }
    /* TABLE DESIGN */
    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
        margin-top: 10px;
    }
    th {
        background-color: #7393A7;
        color: white;
        padding: 10px 8px;
        text-align: center;
        font-family: "Bricolage Grotesque", sans-serif;
        font-weight: 500;
        letter-spacing: 0.3px;
    }
    td {
        border: 1px solid #ccc;
        padding: 8px 10px;
        text-align: center;
        vertical-align: middle;
    }
    td:nth-child(2) {
        text-align: left;
    }
    tr:nth-child(even) td {
        background-color: #f8f9fa;
    }
    th:first-child { width: 5%; }
    th:nth-child(2) { width: 20%; }
    th:nth-child(3) { width: 15%; }
    th:nth-child(4) { width: 10%; }
    th:nth-child(5) { width: 10%; }
    th:nth-child(6) { width: 10%; }
    th:nth-child(7) { width: 10%; }
    th:nth-child(8) { width: 20%; }

    .no-data {
        text-align: center;
        padding: 18px;
        color: #777;
        background: #f8f9fa;
        border-radius: 6px;
        font-size: 14px;
    }
    .footer {
        margin-top: 35px;
        text-align: center;
        font-size: 12px;
        color: #666;
        font-style: italic;
    }
</style>
</head>
<body>

<div class="header">
    <img src="'.$logoBase64.'" alt="PharmAssist Logo">
    <div class="title">PharmAssist</div>
</div>

<hr>
<div class="subtitle">Medicine Stocks Report</div>

<table>
    <tr>
        <th>ID</th>
        <th>Medicine Name</th>
        <th>Category</th>
        <th>Price (₱)</th>
        <th>Branch</th>
        <th>Quantity</th>
        <th>Status</th>
        <th>Date Added</th>
    </tr>';

if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $html .= "
        <tr>
            <td>{$row['medicine_id']}</td>
            <td>" . htmlspecialchars($row['medicine_name']) . "</td>
            <td>" . htmlspecialchars($row['category']) . "</td>
            <td>" . number_format($row['price'], 2) . "</td>
            <td>" . htmlspecialchars($row['branch']) . "</td>
            <td>" . htmlspecialchars($row['stock_quantity']) . "</td>
            <td>" . htmlspecialchars($row['stocks_status']) . "</td>
            <td>" . htmlspecialchars($row['created_at']) . "</td>
        </tr>";
    }
} else {
    $html .= '
    <tr>
        <td colspan="8" class="no-data">No medicine stocks found.</td>
    </tr>';
}

$html .= '
</table>

<div class="footer">
    Report generated on ' . date("F j, Y, g:i a") . '<br>
    © ' . date("Y") . ' PharmAssist
</div>

</body>
</html>
';

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

ob_end_clean();
$dompdf->stream("PharmAssist_Stock_Report.pdf", ["Attachment" => false]);
exit;
?>
