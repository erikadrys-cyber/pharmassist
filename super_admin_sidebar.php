<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
<link rel="stylesheet" href="plugins/admin_sidebar.css?v=4">

<?php
// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user has CEO role
$currentRole = $_SESSION['role'] ?? 'customer';
if ($currentRole !== 'ceo') {
    // Allow access only to CEO role
    header("Location: homepage.php");
    exit();
}
?>

<div class="sidebar close">
  <div class="logo-details">
    <img src="website_icon/websidebar.png" alt="PharmAssist Logo">
    <span class="logo_name">Menu</span>
  </div>

  <ul class="nav-links">
    <li>
      <a href="super_admin_dashboard.php">
        <i class='bx bx-grid-alt'></i>
        <span class="link_name">Dashboard</span>
      </a>
      <ul class="sub-menu blank">
        <li><a class="link_name" href="super_admin_dashboard.php">Dashboard</a></li>
      </ul>
    </li>

    <li>
      <a href="super_admin_manage_branches.php">
        <i class='bx bx-buildings'></i>
        <span class="link_name">Manage Branches</span>
      </a>
      <ul class="sub-menu blank">
        <li><a class="link_name" href="super_admin_manage_branches.php">Manage Branches</a></li>
      </ul>
    </li>

    <li>
      <a href="super_admin_verify_ids.php">
        <i class='bx bx-id-card'></i>
        <span class="link_name">ID Verification</span>
      </a>
      <ul class="sub-menu blank">
        <li><a class="link_name" href="super_admin_verify_ids.php">ID Verification</a></li>
      </ul>
    </li>

    <li>
      <a href="super_admin_notif.php">
        <i class='bx bx-bell'></i>
        <span class="link_name">Notifications</span>
      </a>
      <ul class="sub-menu blank">
        <li><a class="link_name" href="super_admin_notif.php">Notifications</a></li>
      </ul>
    </li>

    <li>
      <a href="super_admin_messages.php">
        <i class='bx bx-message-dots'></i>
        <span class="link_name">Messages</span>
      </a>
      <ul class="sub-menu blank">
        <li><a class="link_name" href="super_admin_messages.php">Messages</a></li>
      </ul>
    </li>

    <li>
      <div class="profile-details">
        <div class="profile-content">
          <img src="img/admin.png" alt="profileImg">
        </div>
        <div class="name-job">
          <div class="profile_name">PharmAssist</div>
          <div class="job">Super Administrator</div>
        </div>
        <a href="login.php"><i class='bx bx-log-out'></i></a>
      </div>
    </li>
  </ul>
</div>