<?php
session_start();
include 'config/connection.php';
include 'admin_sidebar.php';
require_once 'check_role.php';
 
// Check if logged in and has required role (pharmacy_assistant or super_admin)
requireLogin();
requireRole(['p_assistant', 'ceo','manager1', 'manager2', 'p_assistant1', 'p_assistant2']);
 
$currentRole = getCurrentUserRole();

$userInfo = getUserInfo($_SESSION['id']);
$branch_id = $userInfo['branch_id'] ?? null;
$branch_name = ($branch_id == 1) ? "Branch 1" : "Branch 2";

$stmt = $conn->prepare("SELECT * FROM reservations WHERE branch_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $branch_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status | PharmAssist</title>

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
            color: #6C737E;
            font-weight: 700;
            text-align: center;
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
            background-color: #7393A7;
            color: white;
            text-align: left;
            padding: 12px;
            font-weight: 600;
        }

        .status-select {
            background-color: #5a7a8a;
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 4px;
            padding: 4px 8px;
            font-family: inherit;
            font-size: 0.85rem;
            cursor: pointer;
            outline: none;
        }

        .status-select option {
            background-color: white;
            color: #333;
        }

        /* Adjusted alignment for 6 columns */
        th:nth-child(4), th:nth-child(5), th:nth-child(6) { text-align: center; }
        td:nth-child(4), td:nth-child(5), td:nth-child(6) { text-align: center; }

        td {
            padding: 12px;
            border-bottom: 1px solid #e8ecf1;
            color: #333;
            font-family: "Bricolage Grotesque", sans-serif;
        }

        tr:nth-child(even) { background: #f6faff; }

        .status-approved { color: #3fa44b; font-weight: 600; }
        .status-rejected { color: #c83f12; font-weight: 600; }
        .status-pending { color: #6C737E; font-weight: 600; }

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
            <h2 style="font-family: 'Bricolage Grotesque';">Reservations Status</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Medicine</th>
                        <th>Quantity</th>
                        <th>Total Price</th>
                        <th>
                            <select id="statusFilter" class="status-select" onchange="filterStatus()">
                                <option value="all">Status (All)</option>
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </th>
                    </tr>
                </thead>
                <tbody id="reservationTable">
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['reservation_id'] ?></td>
                        <td><?= htmlspecialchars(trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''))) ?></td>
                        <td><?= htmlspecialchars($row['medicine']) ?></td>
                        <td><?= $row['quantity'] ?></td>
                        <td>₱<?= number_format($row['price'] * $row['quantity'], 2) ?></td>
                        <td>
                            <span class="status-<?= strtolower($row['status']) ?>">
                                <?= ucfirst($row['status']) ?>
                            </span>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </section>

    <script>
    function filterStatus() {
        const filterValue = document.getElementById('statusFilter').value;
        const rows = document.querySelectorAll('#reservationTable tr');

        rows.forEach(row => {
            // Updated to nth-child(6) because Status is now the 6th column
            const statusSpan = row.querySelector('td:nth-child(6) span');
            
            if (!statusSpan) return; // Skip if span not found

            if (filterValue === 'all') {
                row.style.display = '';
            } else {
                // Check if the span has the specific class
                if (statusSpan.classList.contains('status-' + filterValue)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            }
        });
    }
    </script>

    <script src="source/sidebar.js"></script>
    <script src="source/homepage.js"></script>
</body>
</html>