<?php
session_start();
include 'config/connection.php';
include 'admin_sidebar.php';
require_once 'check_role.php';
 
// Check if logged in and has required role (admin or super_admin only)
requireLogin();
requireRole(['manager1','manager2', 'ceo']);
 
$currentRole = getCurrentUserRole();

$userInfo = getUserInfo($_SESSION['id']);
$branch_id = $userInfo['branch_id'] ?? null;
$branch_name = ($branch_id == 1) ? "Branch 1" : "Branch 2";

$message = '';
$msg_type = '';

// Helper function: Check shift coverage
function checkShiftCoverage($conn, $shift_id, $assigned_date) {
    $query = "SELECT 
                COUNT(DISTINCT CASE WHEN sm.position = 'Pharmacy Assistant' THEN 1 END) as has_assistant,
                COUNT(DISTINCT CASE WHEN sm.position = 'Pharmacy Technician' THEN 1 END) as has_technician
              FROM staff_shifts ss
              JOIN staff_members sm ON ss.staff_id = sm.staff_id
              WHERE ss.shift_id = ? AND ss.assigned_date = ? AND ss.is_active = 1";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $shift_id, $assigned_date);
    $stmt->execute();
    $result = $stmt->get_result();
    $coverage = $result->fetch_assoc();
    $stmt->close();
    
    return [
        'has_assistant' => $coverage['has_assistant'] > 0,
        'has_technician' => $coverage['has_technician'] > 0,
        'is_complete' => ($coverage['has_assistant'] > 0 && $coverage['has_technician'] > 0)
    ];
}

// Handle Add/Update/Delete Shift Assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'assign_shift') {
        $staff_id = intval($_POST['staff_id']);
        $shift_id = intval($_POST['shift_id']);
        $assigned_date = $_POST['assigned_date'];
        $assigned_by = $_SESSION['id'];
        
        // Get the position/role of the staff member being assigned
        $staff_query = "SELECT position FROM staff_members WHERE staff_id = ?";
        $staff_stmt = $conn->prepare($staff_query);
        $staff_stmt->bind_param("i", $staff_id);
        $staff_stmt->execute();
        $staff_result = $staff_stmt->get_result();
        
        if ($staff_result->num_rows === 0) {
            $message = "✗ Staff member not found";
            $msg_type = "error";
        } else {
            $staff_data = $staff_result->fetch_assoc();
            $staff_position = $staff_data['position'];
            $staff_stmt->close();
            
            // First, try to DELETE if exists (to avoid constraint issues)
            $delete_query = "DELETE FROM staff_shifts 
                            WHERE staff_id = ? AND shift_id = ? AND assigned_date = ?";
            $delete_stmt = $conn->prepare($delete_query);
            $delete_stmt->bind_param("iis", $staff_id, $shift_id, $assigned_date);
            $delete_stmt->execute();
            $delete_stmt->close();
            
            // Then INSERT the new assignment
            $insert_query = "INSERT INTO staff_shifts (staff_id, shift_id, assigned_date, assigned_by, status, is_active) 
                           VALUES (?, ?, ?, ?, 'pending', 1)";
            $insert_stmt = $conn->prepare($insert_query);
            
            if (!$insert_stmt) {
                $message = "✗ Database error: " . $conn->error;
                $msg_type = "error";
            } else {
                $insert_stmt->bind_param("iisi", $staff_id, $shift_id, $assigned_date, $assigned_by);
                if ($insert_stmt->execute()) {
                    // Check coverage after assignment
                    $coverage = checkShiftCoverage($conn, $shift_id, $assigned_date);
                    
                    if ($coverage['is_complete']) {
                        $message = "✓ Shift assigned successfully! All 2 roles now present for this shift.";
                        $msg_type = "success";
                    } else {
                        $missing = [];
                        if (!$coverage['has_assistant']) $missing[] = "Pharmacy Assistant";
                        if (!$coverage['has_technician']) $missing[] = "Pharmacy Technician";
                        
                        $message = "✓ Shift assigned! <br><strong>⚠️ Missing roles:</strong> " . implode(", ", $missing);
                        $msg_type = "warning";
                    }
                } else {
                    $message = "✗ Error assigning shift: " . $insert_stmt->error;
                    $msg_type = "error";
                }
                $insert_stmt->close();
            }
        }
    } 
    elseif ($action === 'delete_shift') {
        $shift_assignment_id = intval($_POST['shift_assignment_id']);
        
        // Get the shift_id and assigned_date before deleting
        $get_query = "SELECT shift_id, assigned_date FROM staff_shifts WHERE shift_assignment_id = ?";
        $get_stmt = $conn->prepare($get_query);
        $get_stmt->bind_param("i", $shift_assignment_id);
        $get_stmt->execute();
        $get_result = $get_stmt->get_result();
        $shift_info = $get_result->fetch_assoc();
        $get_stmt->close();
        
        // Delete the assignment
        $delete_query = "UPDATE staff_shifts SET is_active = 0 WHERE shift_assignment_id = ?";
        $delete_stmt = $conn->prepare($delete_query);
        $delete_stmt->bind_param("i", $shift_assignment_id);
        if ($delete_stmt->execute()) {
            // Check coverage after deletion
            $coverage = checkShiftCoverage($conn, $shift_info['shift_id'], $shift_info['assigned_date']);
            
            if (!$coverage['is_complete']) {
                $missing = [];
                if (!$coverage['has_assistant']) $missing[] = "Pharmacy Assistant";
                if (!$coverage['has_technician']) $missing[] = "Pharmacy Technician";
                
                $message = "✓ Shift deleted. <br><strong>⚠️ Warning: Shift now missing:</strong> " . implode(", ", $missing);
                $msg_type = "warning";
            } else {
                $message = "✓ Shift assignment deleted successfully!";
                $msg_type = "success";
            }
        } else {
            $message = "✗ Error deleting shift";
            $msg_type = "error";
        }
        $delete_stmt->close();
    }
    elseif ($action === 'update_status') {
        $shift_assignment_id = intval($_POST['shift_assignment_id']);
        $new_status = $_POST['status'];
        
        $update_query = "UPDATE staff_shifts SET status = ? WHERE shift_assignment_id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("si", $new_status, $shift_assignment_id);
        if ($update_stmt->execute()) {
            $message = "✓ Shift status updated!";
            $msg_type = "success";
        } else {
            $message = "✗ Error updating status";
            $msg_type = "error";
        }
        $update_stmt->close();
    }
}

// Fetch all active staff members EXCLUDING Pharmacists, grouped by position
$staff_query = "SELECT staff_id, CONCAT(first_name, ' ', last_name) AS fullname, position, email, phone 
                FROM staff_members 
                WHERE is_active = 1 AND branch_id = ? AND position != 'Pharmacist'
                ORDER BY position ASC, first_name ASC, last_name ASC";

$staff_stmt = $conn->prepare($staff_query);
$staff_stmt->bind_param("i", $branch_id);
$staff_stmt->execute();
$staff_result = $staff_stmt->get_result();

// Fetch all available shifts
$shifts_query = "SELECT shift_id, shift_name, shift_time_start, shift_time_end 
                FROM shifts 
                WHERE is_active = 1 
                ORDER BY shift_time_start ASC";
$shifts_result = $conn->query($shifts_query);

// Fetch all shift assignments with coverage info (EXCLUDING Pharmacists)
$assignments_query = "SELECT ss.shift_assignment_id, ss.staff_id, CONCAT(sm.first_name, ' ', sm.last_name) AS fullname, sm.position,
                             s.shift_id, s.shift_name, s.shift_time_start, s.shift_time_end,
                             ss.assigned_date, ss.assigned_by, ss.status, ss.is_active,
                             ss.created_at, ss.updated_at
                      FROM staff_shifts ss
                      JOIN staff_members sm ON ss.staff_id = sm.staff_id
                      JOIN shifts s ON ss.shift_id = s.shift_id
                      WHERE ss.is_active = 1 AND sm.branch_id = ? AND sm.position != 'Pharmacist'
                      ORDER BY ss.assigned_date DESC, s.shift_time_start ASC";

$assignments_stmt = $conn->prepare($assignments_query);
$assignments_stmt->bind_param("i", $branch_id);
$assignments_stmt->execute();
$assignments_result = $assignments_stmt->get_result();

// Function to get who assigned the shift
function getAssignedByInfo($user_id, $conn) {
    $query = "SELECT fullname FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $stmt->close();
        return htmlspecialchars($row['fullname']);
    }
    $stmt->close();
    return 'Unknown';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Staff Management | PharmAssist</title>

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

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #6C737E;
        }

        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-family: inherit;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: #7393A7;
            box-shadow: 0 0 8px rgba(115, 147, 167, 0.2);
        }

        .btn-primary {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, #7393A7, #B5CFD8);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(115, 147, 167, 0.3);
        }

        .btn-danger {
            background: #e74c3c;
            color: white;
            font-size: 12px;
            padding: 6px 12px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }

        .btn-danger:hover {
            background: #c0392b;
        }

        .staff-card {
            display: flex;
            align-items: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 12px;
            margin-bottom: 12px;
            border: 1px solid #e0e0e0;
            transition: all 0.3s;
        }

        .staff-card:hover {
            background: #f0f5f8;
            border-color: #7393A7;
        }

        .staff-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, #7393A7, #B5CFD8);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            margin-right: 15px;
            font-size: 20px;
        }

        .staff-info h5 {
            margin: 0;
            color: #2c3e50;
            font-weight: 600;
        }

        .staff-info .position {
            font-size: 12px;
            color: #7393A7;
            margin: 3px 0;
            font-weight: 600;
        }

        .staff-info .email {
            font-size: 12px;
            color: #999;
            margin: 0;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .data-table thead {
            background: linear-gradient(135deg, #7393A7, #B5CFD8);
            color: white;
        }

        .data-table th {
            padding: 15px 10px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
        }

        .data-table td {
            padding: 12px 10px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }

        .data-table tbody tr:hover {
            background: #f8f9fa;
        }

        .role-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: capitalize;
        }

        .role-pharmacy-assistant {
            background: #f0f8e8;
            color: #00aa00;
        }

        .role-pharmacy-technician {
            background: #fff8e8;
            color: #ff9900;
        }

        .shift-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            background: #f0f0f0;
            color: #666;
            text-transform: capitalize;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .coverage-indicator {
            display: inline-flex;
            gap: 8px;
            margin-top: 10px;
        }

        .coverage-badge {
            font-size: 11px;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: 600;
        }

        .coverage-complete {
            background: #d4edda;
            color: #155724;
        }

        .coverage-missing {
            background: #f8d7da;
            color: #721c24;
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

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= strpos($msg_type, 'warning') !== false ? 'warning' : ($msg_type === 'success' ? 'success' : 'danger') ?>">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <div class="grid-2">
            <!-- Assign Shift Form -->
            <div>
                <div class="section-container">
                    <div class="section-header">
                        <h2 class="section-title" style="font-family: 'Bricolage Grotesque';">Assign Shift</h2>
                        <p class="section-subtitle">Assign pharmacy staff to shifts (all 2 roles required per shift)</p>
                    </div>

                    <form method="POST" action="staff_management.php">
                        <input type="hidden" name="action" value="assign_shift">

                        <div class="form-group">
                            <label class="form-label">Select Staff Member</label>
                            <select class="form-control" name="staff_id" required>
                                <option value="">-- Choose Staff Member --</option>
                                <?php 
                                $current_position = '';
                                if ($staff_result && $staff_result->num_rows > 0) {
                                    while($staff = $staff_result->fetch_assoc()): 
                                        if ($current_position !== $staff['position']) {
                                            if ($current_position !== '') echo '</optgroup>';
                                            echo '<optgroup label="' . htmlspecialchars($staff['position']) . '">';
                                            $current_position = $staff['position'];
                                        }
                                ?>
                                    <option value="<?= $staff['staff_id'] ?>">
                                        <?= htmlspecialchars($staff['fullname']) ?>
                                    </option>
                                <?php 
                                    endwhile;
                                    echo '</optgroup>';
                                    $staff_result->data_seek(0);
                                }
                                ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Select Shift</label>
                            <select class="form-control" name="shift_id" required>
                                <option value="">-- Select Shift --</option>
                                <?php 
                                if ($shifts_result && $shifts_result->num_rows > 0) {
                                    while($shift = $shifts_result->fetch_assoc()): 
                                ?>
                                    <option value="<?= $shift['shift_id'] ?>">
                                        <?= htmlspecialchars($shift['shift_name']) ?> 
                                        (<?= date('h:i A', strtotime($shift['shift_time_start'])) ?> - <?= date('h:i A', strtotime($shift['shift_time_end'])) ?>)
                                    </option>
                                <?php 
                                    endwhile;
                                    $shifts_result->data_seek(0);
                                }
                                ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Date</label>
                            <input type="date" class="form-control" name="assigned_date" required />
                        </div>

                        <button type="submit" class="btn-primary">
                            <i class="fas fa-plus"></i> Assign Shift
                        </button>
                    </form>

                    <div style="margin-top: 20px; padding: 15px; background: #f0f5f8; border-radius: 8px; border-left: 4px solid #7393A7;">
                        <strong>📋 Coverage Requirements:</strong>
                        <p style="margin: 10px 0 0 0; font-size: 13px; color: #555;">Each shift must have both roles assigned:<br>
                        ✓ Pharmacy Assistant<br>
                        ✓ Pharmacy Technician</p>
                    </div>
                </div>
            </div>

            <!-- Staff Directory -->
            <div>
                <div class="section-container">
                    <div class="section-header">
                        <h2 class="section-title" style="font-family: 'Bricolage Grotesque';">Staff Directory</h2>
                        <p class="section-subtitle">All active pharmacy staff (excluding managers)</p>
                    </div>

                    <div style="max-height: 500px; overflow-y: auto;">
                        <?php 
                        if ($staff_result && $staff_result->num_rows > 0) {
                            while($staff = $staff_result->fetch_assoc()): 
                        ?>
                            <div class="staff-card">
                                <div class="staff-avatar">
                                    <i class="fas fa-user-nurse"></i>
                                </div>
                                <div class="staff-info" style="flex: 1;">
                                    <h5><?= htmlspecialchars($staff['fullname']) ?></h5>
                                    <p class="position"><?= htmlspecialchars($staff['position']) ?></p>
                                    <p class="email"><i class="fas fa-envelope"></i> <?= htmlspecialchars($staff['email'] ?? 'N/A') ?></p>
                                </div>
                            </div>
                        <?php 
                            endwhile;
                        } else {
                            echo '<p style="text-align: center; color: #999; padding: 20px;">No staff members found</p>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Current Assignments -->
        <div class="section-container">
            <div class="section-header">
                <h2 class="section-title" style="font-family: 'Bricolage Grotesque';">Shift Assignments</h2>
                <p class="section-subtitle">View and manage all active shift assignments</p>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Staff Member</th>
                        <th>Position</th>
                        <th>Shift</th>
                        <th>Time</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $count = 1;
                    $current_shift_id = null;
                    $shift_assignments = [];
                    
                    if($assignments_result && $assignments_result->num_rows > 0):
                        // Group assignments by shift + date
                        while($assignment = $assignments_result->fetch_assoc()):
                            $shift_key = $assignment['shift_id'] . '_' . $assignment['assigned_date'];
                            if (!isset($shift_assignments[$shift_key])) {
                                $shift_assignments[$shift_key] = [];
                            }
                            $shift_assignments[$shift_key][] = $assignment;
                        endwhile;
                        
                        // Display grouped by shift
                        foreach ($shift_assignments as $shift_key => $assignments):
                            $first = true;
                            $coverage = null;
                            foreach ($assignments as $assignment):
                                // Calculate coverage for this shift
                                if ($first) {
                                    $coverage = checkShiftCoverage($conn, $assignment['shift_id'], $assignment['assigned_date']);
                                    $first = false;
                                }
                    ?>
                        <tr>
                            <td><?= $count++ ?></td>
                            <td><strong><?= htmlspecialchars($assignment['fullname']) ?></strong></td>
                            <td>
                                <span class="role-badge role-<?= str_replace('_', '-', strtolower($assignment['position'])) ?>">
                                    <?= htmlspecialchars($assignment['position']) ?>
                                </span>
                            </td>
                            <td><span class="shift-badge"><?= htmlspecialchars($assignment['shift_name']) ?></span></td>
                            <td>
                                <?= date('h:i A', strtotime($assignment['shift_time_start'])) ?> - 
                                <?= date('h:i A', strtotime($assignment['shift_time_end'])) ?>
                            </td>
                            <td><?= date('M d, Y', strtotime($assignment['assigned_date'])) ?></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="shift_assignment_id" value="<?= $assignment['shift_assignment_id'] ?>">
                                    <select name="status" class="form-control" style="font-size: 12px; padding: 4px; display: inline-block; width: auto;" onchange="this.form.submit();">
                                        <option value="pending" <?= $assignment['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="active" <?= $assignment['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                        <option value="completed" <?= $assignment['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                    </select>
                                </form>
                            </td>
                            <td>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to remove this assignment?');">
                                    <input type="hidden" name="action" value="delete_shift">
                                    <input type="hidden" name="shift_assignment_id" value="<?= $assignment['shift_assignment_id'] ?>">
                                    <button type="submit" class="btn-danger">
                                        <i class="fas fa-trash"></i> Remove
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php 
                            endforeach;
                            // Show coverage summary after each shift group
                            if ($coverage):
                    ?>
                        <tr style="background: #f9f9f9;">
                            <td colspan="8" style="padding: 10px; font-size: 12px; text-align: right;">
                                <strong>Coverage:</strong>
                                <span class="coverage-badge <?= $coverage['has_assistant'] ? 'coverage-complete' : 'coverage-missing' ?>">
                                    <?= $coverage['has_assistant'] ? '✓' : '✗' ?> Assistant
                                </span>
                                <span class="coverage-badge <?= $coverage['has_technician'] ? 'coverage-complete' : 'coverage-missing' ?>">
                                    <?= $coverage['has_technician'] ? '✓' : '✗' ?> Technician
                                </span>
                            </td>
                        </tr>
                    <?php 
                            endif;
                        endforeach;
                    else:
                    ?>
                        <tr>
                            <td colspan="8" style="text-align: center; color: #999; padding: 40px;">
                                No shift assignments yet
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <script src="source/sidebar.js"></script>
    <script src="source/homepage.js"></script>
    
    <script>
        // Set today's date as default
        const today = new Date().toISOString().split('T')[0];
        const dateInput = document.querySelector('input[name="assigned_date"]');
        if (dateInput) dateInput.value = today;
    </script>
</body>
</html>