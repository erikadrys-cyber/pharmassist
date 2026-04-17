<?php
session_start();
include 'config/connection.php';
include 'admin_sidebar.php';
require_once 'check_role.php';
 
requireLogin();
requireRole(['ceo', 'manager1', 'manager2','p_assistant1', 'p_assistant2']);

 
$currentRole = getCurrentUserRole();

$userInfo = getUserInfo($_SESSION['id']);
$branch_id = $userInfo['branch_id'] ?? null;
$branch_name = ($branch_id == 1) ? "Branch 1" : "Branch 2";

$filterType = isset($_GET['filter']) && in_array($_GET['filter'], ['daily', 'monthly']) ? $_GET['filter'] : 'daily';
$filterDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$filterMonth = isset($_GET['month']) ? $_GET['month'] : date('Y-m');

// Prepare query
if ($filterType === 'monthly') {
    $where_clause = "DATE_FORMAT(created_at, '%Y-%m') = ?";
    $param = $filterMonth;
    $display_date = date('F Y', strtotime($filterMonth));
} else {
    $where_clause = "DATE(created_at) = ?";
    $param = $filterDate;
    $display_date = date('M d, Y', strtotime($filterDate));
}

// Summary (Approved Only)
$summaryQuery = "SELECT 
                    SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as total_approved,
                    SUM(CASE WHEN status = 'Approved' THEN price * quantity ELSE 0 END) as total_revenue
                FROM reservations WHERE $where_clause AND branch_id = ?";
$sumStmt = $conn->prepare($summaryQuery);
$sumStmt->bind_param("si", $param, $branch_id);
$sumStmt->execute();
$summary = $sumStmt->get_result()->fetch_assoc();

// List (Approved Only)
$listQuery = "SELECT reservation_id, first_name, last_name, medicine, quantity, price FROM reservations WHERE $where_clause AND branch_id = ? AND status = 'Approved' ORDER BY created_at DESC";
$listStmt = $conn->prepare($listQuery);
$listStmt->bind_param("si", $param, $branch_id);
$listStmt->execute();
$result = $listStmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report | PharmAssist</title>

    <link rel="stylesheet" href="plugins/admin_sidebar.css?v=2">
    <link rel="stylesheet" href="dashboard/dashboard.css?v=2">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@400;600&family=Tinos:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Tinos', serif;
            background-color: #E8ECF1;
            margin: 0;
        }
        .home-content, .text, .section-title { color: white; font-size: 18px; font-weight: 600; font-family: 'Bricolage Grotesque', sans-serif; }
        :root { 
            --accent: #7393A7; 
            --dark-text: #6C737E;
        }
        
        body { 
            font-family: "Bricolage Grotesque", sans-serif; 
            background: #f8fafc; 
        }

        /* Updated Table Container to match reference */
        .table-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
            border: 1px solid #b5cfd8;
            padding: 30px;
            margin: 30px;
        }

        .table-container h2 {
            font-family: 'Tinos', serif;
            color: var(--dark-text);
            font-weight: 700;
            text-align: center;
            margin-bottom: 5px;
        }

        .report-subtitle {
            text-align: center;
            color: #94a3b8;
            font-size: 0.9rem;
            margin-bottom: 25px;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: #fff;
            border-radius: 10px;
            overflow: hidden;
        }

        th {
            background-color: var(--accent);
            color: white;
            text-align: left;
            padding: 12px;
            font-weight: 600;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #e8ecf1;
            color: #333;
        }

        tr:nth-child(even) { background: #f6faff; }

        /* Stats Section */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: #f1f5f9; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; text-align: center; }
        .stat-label { font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 700; display: block; }
        .stat-value { font-size: 1.5rem; font-weight: 700; color: var(--accent); }

        /* Action Bar */
        .action-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; gap: 15px; }
        .filter-form select, .filter-form input { 
            padding: 6px 10px; 
            border: 1px solid #b5cfd8; 
            border-radius: 4px; 
            font-family: inherit;
        }

        .btn-export {
            background: var(--accent);
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 5px;
            border: none;
            cursor: pointer;
        }

        .btn-export:hover { background: #5a7a8a; }

        .branch-badge {
            background: #7393A7;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-left: auto;
        }
    </style>
</head>

<body>
    <section class="home-section">
        <div class="home-content">
            <i class='bx bx-menu'></i>
            <span class="text">PharmAssist</span>
            <span class="branch-badge">📍 <?= $branch_name ?></span>
        </div>

        <div class="table-container">
            <h2 style="font-family: 'Bricolage Grotesque';">Approved Summary</h2>
            <p class="report-subtitle">Approved transactions for <?= $display_date ?></p>

            <div class="stats-grid">
                <div class="stat-card">
                    <span class="stat-label" style="font-family: 'Bricolage Grotesque';">Total Revenue</span>
                    <span class="stat-value">₱<?= number_format($summary['total_revenue'] ?? 0, 2) ?></span>
                </div>
                <div class="stat-card">
                    <span class="stat-label" style="font-family: 'Bricolage Grotesque';">Approved Orders</span>
                    <span class="stat-value"><?= $summary['total_approved'] ?? 0 ?></span>
                </div>
            </div>

            <div class="action-bar">
                <form method="GET" class="filter-form">
                    <select name="filter" onchange="this.form.submit()">
                        <option value="daily" <?= $filterType === 'daily' ? 'selected' : '' ?>>Daily</option>
                        <option value="monthly" <?= $filterType === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                    </select>
                    <?php if($filterType === 'daily'): ?>
                        <input type="date" name="date" value="<?= $filterDate ?>" onchange="this.form.submit()">
                    <?php else: ?>
                        <input type="month" name="month" value="<?= $filterMonth ?>" onchange="this.form.submit()">
                    <?php endif; ?>
                </form>

                <button class="btn-export" onclick="exportPDF()">
                    <i class='bx bxs-file-pdf'></i> Export PDF
                </button>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Patient Name</th>
                        <th>Medicine</th>
                        <th style="text-align: center;">Qty</th>
                        <th style="text-align: center;">Unit Price</th>
                        <th style="text-align: right;">Total Price</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td>#<?= $row['reservation_id'] ?></td>
                            <td><?= htmlspecialchars(trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''))) ?></td>
                            <td style="font-weight: 600;"><?= htmlspecialchars($row['medicine']) ?></td>
                            <td style="text-align: center;"><?= $row['quantity'] ?></td>
                            <td style="text-align: center;">₱<?= number_format($row['price'], 2) ?></td>
                            <td style="text-align: right; font-weight: 600; color: var(--accent);">₱<?= number_format($row['price'] * $row['quantity'], 2) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align: center; color: #94a3b8; padding: 40px;">No approved transactions found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <script>
        function exportPDF() {
            const params = new URLSearchParams(window.location.search);
            const url = 'transaction_sales_report.php?' + params.toString();
            
            window.open(url, '_blank');
        }
    </script>
    <script src="source/sidebar.js"></script>
    <script src="source/homepage.js"></script>
</body>
</html>