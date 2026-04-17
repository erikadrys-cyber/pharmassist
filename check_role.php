<?php
/**
 * Role-based Access Control Functions
 * 
 * This file handles all authentication and authorization checks
 * Make sure to include this file at the top of every protected page
 */

// 1. ====== REQUIRE LOGIN ======
/**
 * Check if user is logged in
 * If not, redirect to login page
 */
function requireLogin() {
    if (!isset($_SESSION['id']) || empty($_SESSION['id'])) {
        header('Location: login.php');
        exit();
    }
}

// 2. ====== GET CURRENT USER ROLE ======
/**
 * Get the current logged-in user's role from the database
 * Always fetch from DB, never rely on session alone
 * 
 * @return string|null The user's role or null if not found
 */
function getCurrentUserRole() {
    global $conn;
    
    // Check if user is logged in
    if (!isset($_SESSION['id']) || empty($_SESSION['id'])) {
        return null;
    }
    
    $user_id = $_SESSION['id'];
    
    // Query database for the user's role
    $query = "SELECT role FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        error_log("Database prepare error: " . $conn->error);
        return null;
    }
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        return null; // User not found
    }
    
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row['role'];
}


/**
 * Check if the current user has one of the required roles
 * If not authorized, redirect to access_denied page
 * 
 * @param array $allowedRoles Array of allowed role strings
 * @example requireRole(['manager', 'ceo']);
 */
function requireRole($allowedRoles) {
    // Get the current user's role
    $currentRole = getCurrentUserRole();
    
    // If role not found (user not in database)
    if ($currentRole === null) {
        header('Location: login.php');
        exit();
    }
    
    // If role is not in the allowed list
    if (!in_array($currentRole, $allowedRoles)) {
        // Build the required roles string for display
        $required = implode(', ', $allowedRoles);
        
        // Redirect to access denied page
        header("Location: access_denied.php?required=" . urlencode($required));
        exit();
    }
}

// 4. ====== GET USER INFO ======
/**
 * Get complete information about a user
 * 
 * @param int $user_id The user's ID
 * @return array|null User data or null if not found
 */
function getUserInfo($user_id) {
    global $conn;
    
    if (empty($user_id)) {
        return null;
    }
    
    $query = "SELECT * FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        error_log("Database prepare error: " . $conn->error);
        return null;
    }
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        return null;
    }
    
    $userInfo = $result->fetch_assoc();
    $stmt->close();
    
    return $userInfo;
}

// 5. ====== GET TODAY'S SHIFT ======
/**
 * Get the current shift assignment for a staff member today
 * Used to display role/position in sidebar
 * 
 * @param int $staff_id The staff member's ID
 * @return array|null Shift data or null if no shift today
 */
function getTodaysShift($staff_id) {
    global $conn;
    
    if (empty($staff_id)) {
        return null;
    }
    
    $today = date('Y-m-d');
    
    $query = "SELECT ss.*, s.shift_name, s.shift_time_start, s.shift_time_end, sm.position
              FROM staff_shifts ss
              JOIN shifts s ON ss.shift_id = s.shift_id
              JOIN staff_members sm ON ss.staff_id = sm.staff_id
              WHERE ss.staff_id = ? AND ss.assigned_date = ? AND ss.is_active = 1
              LIMIT 1";
    
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        error_log("Database prepare error: " . $conn->error);
        return null;
    }
    
    $stmt->bind_param("is", $staff_id, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        return null;
    }
    
    $shift = $result->fetch_assoc();
    $stmt->close();
    
    return $shift;
}

// 6. ====== GET STAFF ID FROM USER ID ======
/**
 * Get the staff_id from the users table
 * Useful for queries that need to join with staff_members
 * 
 * @param int $user_id The user's ID
 * @return int|null The staff member's ID or null
 */
function getStaffIdFromUserId($user_id) {
    global $conn;
    
    if (empty($user_id)) {
        return null;
    }
    
    // This assumes your users table has a reference to staff_members
    // Adjust the query based on your schema
    $query = "SELECT staff_id FROM staff_members WHERE user_id = ? OR staff_id = ?";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        error_log("Database prepare error: " . $conn->error);
        return null;
    }
    
    $stmt->bind_param("ii", $user_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        return null;
    }
    
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row['staff_id'];
}

// 7. ====== ROLE-BASED PERMISSION CHECK ======
/**
 * Check if user has a specific role (returns boolean instead of redirecting)
 * Useful for conditional logic within a page
 * 
 * @param string|array $roles Role(s) to check
 * @return bool True if user has the role
 */
function hasRole($roles) {
    $currentRole = getCurrentUserRole();
    
    if (is_array($roles)) {
        return in_array($currentRole, $roles);
    } else {
        return $currentRole === $roles;
    }
}

// 8. ====== SAFE REDIRECT ======
/**
 * Safely redirect to home/dashboard based on user's role
 * 
 * @param string|null $override Optional custom redirect URL
 */
function redirectToHome($override = null) {
    if ($override) {
        header("Location: " . $override);
        exit();
    }
    
    $role = getCurrentUserRole();
    
    switch($role) {
        case 'manager1':
        case 'manager2':
        case 'p_assistant1':
        case 'p_assistant2':
        case 'p_technician1':
        case 'p_technician2':
            header("Location: staff_dashboard.php");
            break;
        case 'ceo':
            header("Location: super_admin_dashboard.php");
            break;
        case 'customer':
            header("Location: homepage.php");
            break;
        default:
            header("Location: login.php");
    }
    exit();
}

?>