<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<div class="sidebar close">
    <div class="logo-details">
      <img src="website_icon/websidebar.png" alt="PharmASsist Logo">
      <span class="logo_name">Menu</span>
    </div>
    <ul class="nav-links">
      <li>
        <a href="homepage.php">
          <i class='bx bx-home'></i>
          <span class="link_name">Home</span>
        </a>
        <ul class="sub-menu blank">
          <li><a class="link_name" href="homepage.php">Home</a></li>
        </ul>
      </li>
      <li>
          <a href="guide.php">
            <i class='bx bx-bulb'></i>
            <span class="link_name">Guide</span>
          </a>
        <ul class="sub-menu">
          <li><a class="link_name" href="guide.php">Guide</a></li>
        </ul>
      </li>
      <li>
          <a href="medicines.php">
            <i class="bi bi-card-list"></i>
            <span class="link_name">Medicine List</span>
          </a>
        <ul class="sub-menu">
          <li><a class="link_name" href="medicines.php">Medicine List</a></li>
        </ul>
      </li>
       <li>
          <a href="cart.php">
            <i class="bi bi-cart"></i>
            <span class="link_name">Cart</span>
          </a>
        <ul class="sub-menu">
          <li><a class="link_name" href="cart.php">Cart</a></li>
        </ul>
      </li>
       <li>
          <a href="user_notif.php">
            <i class="bi bi-bell"></i>
            <span class="link_name">Notification</span>
          </a>
        <ul class="sub-menu">
          <li><a class="link_name" href="user_notif.php">Notification</a></li>
        </ul>
      </li>
       <li>
          <a href="send_message.php">
            <i class="bi bi-envelope"></i>
            <span class="link_name">Messages</span>
          </a>
        <ul class="sub-menu">
          <li><a class="link_name" href="send_message.php">Messages</a></li>
        </ul>
      <li>
        <div class="profile-details">
          <div class="profile-content">
            <img src="img/user-profile.png" alt="profileImg">
          </div>
          <div class="name-job">
            <div class="profile_name">
              <?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : "Guest"; ?>
            </div>
            <div class="job">User</div>
          </div>
          <a href="login.php"><i class='bx bx-log-out'></i></a>
        </div>
      </li>
    </ul>
  </div>