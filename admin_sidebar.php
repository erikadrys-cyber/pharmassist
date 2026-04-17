<?php
// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize session variables
if (!isset($_SESSION['id'])) {
    $_SESSION['id'] = null;
}
if (!isset($_SESSION['role'])) {
    $_SESSION['role'] = 'customer';
}
if (!isset($_SESSION['username'])) {
    $_SESSION['username'] = 'Guest';
}

// Get current user's role - SAFELY (no database calls yet)
// This will be called AFTER check_role.php is included on the page
$currentRole = isset($_SESSION['role']) ? $_SESSION['role'] : 'customer';

// Try to get user info if database is available
$userInfo = null;
$todaysShift = null;

// Only fetch from database if we have functions available
if (function_exists('getUserInfo')) {
    $userInfo = getUserInfo($_SESSION['id']);
}

// Try to get today's shift if function exists
if (function_exists('getTodaysShift')) {
    $todaysShift = getTodaysShift($_SESSION['id']);
}

// Determine display role
$displayRole = $currentRole;
if ($todaysShift) {
    $displayRole = str_replace('_', ' ', ucfirst($todaysShift['position'])) . ' (' . ucfirst($todaysShift['shift_name']) . ')';
} else {
    $displayRole = str_replace('_', ' ', ucfirst($currentRole ?? 'Guest'));
}

// Define menu items with their required roles
$menuItems = [
    [
        'title' => 'Dashboard',
        'icon' => 'bxs-dashboard',
        'link' => 'staff_dashboard.php',
        'requiredRoles' => ['manager1', 'manager2', 'p_assistant1','p_assistant2', 'p_technician1','p_technician2', 'ceo']
    ],
    [
        'title' => 'Staff Management',
        'icon' => 'bxs-group',
        'link' => 'staff_management.php',
        'requiredRoles' => ['manager1', 'manager2', 'ceo']
    ],
    [
        'title' => 'Inventory',
        'icon' => 'bx-package',
        'link' => 'med_inventory.php',
        'requiredRoles' => ['manager1', 'manager2', 'p_assistant1','p_assistant2', 'p_technician1','p_technician2', 'ceo']
    ],
    [
        'title' => 'Reservation Requests',
        'icon' => 'bxs-cart-alt',
        'link' => 'transaction_action.php',
        'requiredRoles' => ['manager1', 'manager2', 'p_technician1','p_technician2', 'ceo']
    ],
    [
        'title' => 'Reservation Status',
        'icon' => 'bxs-check-circle',
        'link' => 'transaction_status.php',
        'requiredRoles' => ['manager1', 'manager2', 'p_assistant1','p_assistant2', 'ceo']
    ],
    [
        'title' => 'Messages',
        'icon' => 'bx-message-dots',
        'link' => 'admin_messages.php',
        'requiredRoles' => ['manager1', 'manager2', 'ceo']
    ],
    [
        'title' => 'Summary Reports',
        'icon' => 'bxs-bar-chart-alt-2',
        'link' => 'transaction_summary.php',
        'requiredRoles' => ['manager1', 'manager2', 'p_assistant1','p_assistant2', 'ceo']
    ]
];

// Filter menu items based on current role
$visibleMenuItems = array_filter($menuItems, function($item) use ($currentRole) {
    return in_array($currentRole, $item['requiredRoles']);
});
?>

<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
<link rel="stylesheet" href="plugins/admin_sidebar.css?v=4">

<div class="sidebar close">
  <div class="logo-details">
    <img src="website_icon/websidebar.png" alt="PharmAssist Logo">
    <span class="logo_name">Menu</span>
  </div>

  <ul class="nav-links">
    <!-- Render only visible menu items based on role -->
    <?php foreach ($visibleMenuItems as $item): ?>
    <li>
      <a href="<?php echo $item['link']; ?>">
        <i class='bx <?php echo $item['icon']; ?>'></i>
        <span class="link_name"><?php echo $item['title']; ?></span>
      </a>
      <ul class="sub-menu blank">
        <li><a class="link_name" href="<?php echo $item['link']; ?>"><?php echo $item['title']; ?></a></li>
      </ul>
    </li>
    <?php endforeach; ?>

    <!-- User Profile and Logout (Always visible) -->
    <li>
      <div class="profile-details">
        <div class="profile-content">
          <img src="img/admin.png" alt="profileImg">
        </div>
        <div class="name-job">
          <div class="profile_name"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Guest'); ?></div>
          <div class="job" title="Current Role: <?php echo htmlspecialchars($displayRole); ?>">
            <?php echo htmlspecialchars($displayRole); ?>
          </div>
        </div>
        <a href="login.php"><i class='bx bx-log-out'></i></a>
      </div>
    </li>
  </ul>
</div>