<?php
date_default_timezone_set('Asia/Manila');
session_start();
require 'config/connection.php';
require_once 'check_role.php';

// ===================== HANDLE POST FIRST =====================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $action = $_POST['action'] ?? '';
    $shiftId = $_POST['shift_assignment_id'] ?? 0;

    if (!$shiftId) {
        echo json_encode(['success' => false, 'message' => 'Invalid shift ID']);
        exit();
    }

    if ($action === 'check_in') {
        $query = "UPDATE staff_shifts 
                  SET check_in_time = NOW(), status = 'active'
                  WHERE shift_assignment_id = ?";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $shiftId);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Checked in successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Check-in failed']);
        }

        $stmt->close();
        exit();
    }

    if ($action === 'check_out') {

    // Get shift details first
    $query = "SELECT ss.assigned_date, s.shift_time_start, s.shift_time_end
              FROM staff_shifts ss
              JOIN shifts s ON ss.shift_id = s.shift_id
              WHERE ss.shift_assignment_id = ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $shiftId);
    $stmt->execute();
    $result = $stmt->get_result();
    $shift = $result->fetch_assoc();
    $stmt->close();

    if (!$shift) {
        echo json_encode(['success' => false, 'message' => 'Shift not found']);
        exit();
    }

    // Build shift end datetime
    $assignedDate = $shift['assigned_date'];
    $start = $shift['shift_time_start'];
    $end = $shift['shift_time_end'];

    // Detect overnight shift
    if ($end < $start) {
        $endDateTime = date('Y-m-d H:i:s', strtotime($assignedDate . ' ' . $end . ' +1 day'));
    } else {
        $endDateTime = date('Y-m-d H:i:s', strtotime($assignedDate . ' ' . $end));
    }

    $now = date('Y-m-d H:i:s');

    // 🚫 BLOCK EARLY CHECKOUT
    if ($now < $endDateTime) {
        echo json_encode([
            'success' => false,
            'message' => 'You cannot check out before your shift ends.'
        ]);
        exit();
    }

    // ✅ Allow checkout
    $query = "UPDATE staff_shifts 
              SET check_out_time = NOW(), status = 'completed'
              WHERE shift_assignment_id = ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $shiftId);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Checked out successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Check-out failed']);
    }

    $stmt->close();
    exit();
}
}

// ===================== NORMAL PAGE LOAD =====================
requireLogin();
requireRole(['manager1','manager2', 'p_assistant1', 'p_assistant2', 'p_technician1', 'p_technician2', 'ceo']);

$currentRole = getCurrentUserRole();

$roleToPosition = [
    'manager1' => 'Pharmacist Manager',
    'manager2' => 'Pharmacist Manager',
    'p_assistant1' => 'Pharmacy Assistant',
    'p_assistant2' => 'Pharmacy Assistant',
    'p_technician1' => 'Pharmacy Technician',
    'p_technician2' => 'Pharmacy Technician',
    'ceo' => 'Chief Executive Officer'
];

$position = $roleToPosition[$currentRole] ?? '';

$userInfo = getUserInfo($_SESSION['id']);
$branch_id = $userInfo['branch_id'] ?? null;
$branch_name = ($branch_id == 1) ? "Branch 1" : "Branch 2";

// Include sidebar AFTER POST handling
include 'admin_sidebar.php';

$today = date('Y-m-d');

$shifts_result = null;
$upcoming_result = null;


// ===================== TODAY SHIFTS =====================
$query = "SELECT ss.shift_assignment_id, ss.staff_id, CONCAT(sm.first_name, ' ', sm.last_name) AS fullname, sm.position, 
                 s.shift_id, s.shift_name, s.shift_time_start, s.shift_time_end,
                 ss.assigned_date, ss.status, ss.check_in_time, ss.check_out_time,
                 ss.is_active
          FROM staff_shifts ss
          JOIN staff_members sm ON ss.staff_id = sm.staff_id
          JOIN shifts s ON ss.shift_id = s.shift_id
          WHERE sm.position = ?
            AND ss.assigned_date >= DATE_SUB(?, INTERVAL 1 DAY)
            AND ss.is_active = 1
            AND sm.branch_id = ?
          ORDER BY s.shift_time_start ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param("ssi", $position, $today, $branch_id);
$stmt->execute();
$shifts_result = $stmt->get_result();
$stmt->close();


// ===================== UPCOMING =====================
$upcoming_query = "SELECT ss.shift_assignment_id, ss.staff_id, CONCAT(sm.first_name, ' ', sm.last_name) AS fullname, sm.position, 
                         s.shift_id, s.shift_name, s.shift_time_start, s.shift_time_end,
                         ss.assigned_date, ss.status, ss.is_active
                  FROM staff_shifts ss
                  JOIN staff_members sm ON ss.staff_id = sm.staff_id
                  JOIN shifts s ON ss.shift_id = s.shift_id
                  WHERE sm.position = ?
                    AND ss.assigned_date > ? 
                    AND ss.assigned_date <= DATE_ADD(?, INTERVAL 7 DAY) 
                    AND ss.is_active = 1
                    AND sm.branch_id = ?
                  ORDER BY ss.assigned_date ASC, s.shift_time_start ASC";

$upcoming_stmt = $conn->prepare($upcoming_query);
$upcoming_stmt->bind_param("sssi", $position, $today, $today, $branch_id);
$upcoming_stmt->execute();
$upcoming_result = $upcoming_stmt->get_result();
$upcoming_stmt->close();


// ===================== ROLE DISPLAY =====================
$roleDisplay = str_replace('_', ' ', ucfirst($currentRole));
if ($currentRole === 'p_assistant1' || $currentRole === 'p_assistant2') {
    $roleDisplay = 'Pharmacy Assistant';
} elseif ($currentRole === 'p_technician1' || $currentRole === 'p_technician2') {
    $roleDisplay = 'Pharmacy Technician';
} elseif ($currentRole === 'manager1' || $currentRole === 'manager2') {
    $roleDisplay = 'Pharmacist Manager';
} elseif ($currentRole === 'ceo') {
    $roleDisplay = 'Chief Executive Officer';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>My Shifts | PharmAssist</title>

    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="plugins/admin_sidebar.css?v=4">
    <link rel="icon" href="website_icon/favicon.png" type="image/png" sizes="64x64">

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,200..800&family=Tinos:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" crossorigin="anonymous"/>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Tinos', serif;
            background-color: #E8ECF1;
            margin: 0;
        }
        .home-content, .text, .section-title { color: white; font-size: 18px; font-weight: 600; font-family: 'Bricolage Grotesque', sans-serif; }

        .home-section {
            position: relative;
            background: #E8ECF1;
            min-height: 100vh;
            left: 250px;
            width: calc(100% - 250px);
            transition: all 0.5s ease;
            padding: 100px 40px 40px 40px;
        }

        .sidebar.close ~ .home-section {
            left: 78px;
            width: calc(100% - 78px);
        }

        .home-content {
            height: 60px;
            display: flex;
            align-items: center;
            background: linear-gradient(to right, #7393A7, #B5CFD8);
            padding: 0 20px;
            position: fixed;
            top: 0;
            left: 250px;
            width: calc(100% - 250px);
            z-index: 90;
            transition: all 0.4s ease;
        }

        .sidebar.close ~ .home-section .home-content {
            left: 78px;
            width: calc(100% - 78px);
        }

        .home-content i {
            color: #E8ECF1;
            font-size: 35px;
            margin-right: 15px;
            cursor: pointer;
        }

        .home-content .text {
            color: #E8ECF1;
            font-size: 26px;
            font-weight: 600;
        }

        /* WELCOME BANNER */
        .welcome-banner {
            background: white;
            border-radius: 16px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
            border: 1px solid #b5cfd8;
            padding: 30px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .welcome-content h1 {
            font-family: "Tinos", serif;
            color: #6C737E;
            font-size: 2rem;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .welcome-content p {
            color: #999;
            font-size: 1.1rem;
            margin: 0;
        }

        .role-badge {
            display: inline-block;
            background: linear-gradient(135deg, #7393A7, #B5CFD8);
            color: white;
            padding: 10px 20px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .section-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
            border: 1px solid #b5cfd8;
            padding: 30px;
            margin-bottom: 30px;
        }

        .section-header {
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e8ecf1;
        }

        .section-title {
            font-family: "Tinos", serif;
            font-weight: 700;
            font-size: 1.8rem;
            margin-bottom: 8px;
            color: #6C737E;
        }

        .section-subtitle {
            color: #999;
            font-size: 0.95rem;
            margin: 0;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        @media (max-width: 1200px) {
            .grid-2 {
                grid-template-columns: 1fr;
            }
        }

        /* TABLE STYLES */
        .shifts-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .shifts-table thead {
            background: linear-gradient(135deg, #7393A7, #B5CFD8);
            color: white;
        }

        .shifts-table th {
            padding: 15px 12px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
        }

        .shifts-table td {
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
        }

        .shifts-table tbody tr:hover {
            background: #f9f9f9;
        }

        /* STATUS BADGES */
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-completed {
            background: #d1ecf1;
            color: #0c5460;
        }

        /* ACTION BUTTONS */
        .btn-action {
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }

        .btn-checkin {
            background: #59AC77;
            color: white;
        }

        .btn-checkin:hover {
            background: #4a9266;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(89, 172, 119, 0.3);
        }

        .btn-checkout {
            background: #7393A7;
            color: white;
        }

        .btn-checkout:hover {
            background: #5a7a8a;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(115, 147, 167, 0.3);
        }

        .btn-completed {
            background: #e0e0e0;
            color: #666;
            cursor: default;
        }

        /* STAFF NAME */
        .staff-name {
            font-weight: 600;
            color: #2c3e50;
        }

        .staff-position {
            font-size: 11px;
            color: #999;
            margin-top: 2px;
        }

        /* EMPTY STATE */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }

        .empty-state i {
            font-size: 36px;
            color: #ddd;
            display: block;
            margin-bottom: 10px;
        }

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

        <!-- WELCOME BANNER -->
        <div class="welcome-banner">
            <div class="welcome-content">
                <h1 style="font-family: 'Bricolage Grotesque';">Welcome, <?= htmlspecialchars($_SESSION['username'] ?? 'Staff Member') ?>!</h1>
                <p>You are logged in as <strong><?= htmlspecialchars($roleDisplay) ?></strong></p>
            </div>
            <div class="role-badge">
                <?= htmlspecialchars($roleDisplay) ?>
            </div>
        </div>

        <div class="grid-2">
            <!-- Today's Shifts -->
            <div>
                <div class="section-container">
                    <div class="section-header">
                        <h2 class="section-title" style="font-family: 'Bricolage Grotesque';">Today's Shifts</h2>
                        <p class="section-subtitle"><?= date('l, F j, Y') ?></p>
                    </div>

                    <?php if ($shifts_result && $shifts_result->num_rows > 0): ?>
                        <table class="shifts-table">
                            <thead>
                                <tr>
                                    <th>Staff Member</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($shift = $shifts_result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div class="staff-name"><?= htmlspecialchars($shift['fullname']) ?></div>
                                        <div class="staff-position"><?= htmlspecialchars($shift['shift_name']) ?> Shift</div>
                                    </td>
                                    <td class="shift-time">
                                        <?= date('h:i A', strtotime($shift['shift_time_start'])) ?> - 
                                        <?= date('h:i A', strtotime($shift['shift_time_end'])) ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?= $shift['status'] ?>">
                                            <?= ucfirst($shift['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!$shift['check_in_time']): ?>
                                            <button class="btn-action btn-checkin" onclick="checkIn(<?= $shift['shift_assignment_id'] ?>)">
                                                <i class="fas fa-sign-in-alt"></i> Check In
                                            </button>
                                        <?php elseif (!$shift['check_out_time']): ?>
                                            <button class="btn-action btn-checkout" onclick="checkOut(<?= $shift['shift_assignment_id'] ?>)">
                                                <i class="fas fa-sign-out-alt"></i> Check Out
                                            </button>
                                        <?php else: ?>
                                            <button class="btn-action btn-completed" disabled>
                                                <i class="fas fa-check"></i> Completed
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-times"></i>
                            <p>No shifts assigned for today</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Upcoming Shifts -->
            <div>
                <div class="section-container">
                    <div class="section-header">
                        <h2 class="section-title" style="font-family: 'Bricolage Grotesque';">Upcoming Shifts</h2>
                        <p class="section-subtitle">Next 7 days scheduled</p>
                    </div>

                    <?php if ($upcoming_result && $upcoming_result->num_rows > 0): ?>
                        <table class="shifts-table">
                            <thead>
                                <tr>
                                    <th>Staff Member</th>
                                    <th>Date & Time</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($shift = $upcoming_result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div class="staff-name"><?= htmlspecialchars($shift['fullname']) ?></div>
                                        <div class="staff-position"><?= htmlspecialchars($shift['shift_name']) ?> Shift</div>
                                    </td>
                                    <td>
                                        <div class="shift-time"><?= date('M d', strtotime($shift['assigned_date'])) ?></div>
                                        <div class="staff-position">
                                            <?= date('h:i A', strtotime($shift['shift_time_start'])) ?> - 
                                            <?= date('h:i A', strtotime($shift['shift_time_end'])) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?= $shift['status'] ?>">
                                            <?= ucfirst($shift['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-hourglass-end"></i>
                            <p>No upcoming shifts scheduled</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <script src="source/sidebar.js"></script>
    <script src="source/homepage.js"></script>
    <script>
        function checkIn(shiftAssignmentId) {
            const formData = new FormData();
            formData.append('action', 'check_in');
            formData.append('shift_assignment_id', shiftAssignmentId);
            
            fetch('staff_dashboard.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✓ ' + data.message);
                    location.reload();
                } else {
                    alert('✗ ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred');
            });
        }

        function checkOut(shiftAssignmentId) {
            if (!confirm('Are you sure you want to check out?')) return;
            
            const formData = new FormData();
            formData.append('action', 'check_out');
            formData.append('shift_assignment_id', shiftAssignmentId);
            
            fetch('staff_dashboard.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✓ ' + data.message);
                    location.reload();
                } else {
                    alert('✗ ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred');
            });
        }
    </script>
</body>
</html>