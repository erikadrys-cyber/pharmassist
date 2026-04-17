<?php
include 'config/connection.php';
session_start();

if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION['role'] !== 'ceo') {
    header("Location: homepage.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>CEO Dashboard | PharmAssist</title>

  <link rel="stylesheet" href="plugins/admin_sidebar.css?v=2">
  <link rel="stylesheet" href="plugins/footer.css">
  <link rel="icon" href="website_icon/favicon.png" type="image/png" sizes="64x64">

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,200..800&family=Tinos:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet"/>
  <link rel="icon" href="website_icon/favicon.png" type="image/png" sizes="64x64">

  <style>
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
      top: 0;
      left: 78px;
      width: calc(100% - 78px);
      transition: all 0.5s ease;
      z-index: 2;
    }

    .sidebar.open ~ .home-section {
      left: 250px;
      width: calc(100% - 250px);
    }

    /* Top Bar */
    .home-section .home-content {
      height: 60px;
      display: flex;
      align-items: center;
      background: linear-gradient(to right, #7393A7, #B5CFD8);
      padding: 0 20px;
      position: fixed;
      top: 0;
      left: 250px;
      width: calc(100% - 250px);
      transition: all 0.4s ease;
      z-index: 90;
    }

    .sidebar.close ~ .home-section .home-content {
      left: 78px;
      width: calc(100% - 78px);
    }

    .home-content i {
      color: white;
      font-size: 20px;
      cursor: pointer;
      margin-right: 15px;
    }

    .home-content .text {
      color: white;
      font-size: 18px;
      font-weight: 600;
    }

    .dashboard-container {
      padding: 30px;
      max-width: 1400px;
      margin: 0 auto;
    }

    .page-header {
      background: white;
      padding: 25px 30px;
      border-radius: 12px;
      margin-bottom: 30px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
      margin-top: 80px;
    }

    .page-header h1 {
      color: #7393A7;
      font-size: 2rem;
      font-weight: 700;
      margin: 0 0 10px 0;
    }

    .page-header p {
      color: #6C737E;
      margin: 0;
    }

    .dashboard-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }

    .stat-card {
      background: white;
      padding: 25px;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
      display: flex;
      align-items: center;
      gap: 20px;
    }

    .stat-icon {
      width: 60px;
      height: 60px;
      background: #E8ECF1;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 28px;
      color: #7393A7;
    }

    .stat-content h3 {
      color: #6C737E;
      font-size: 12px;
      font-weight: 500;
      margin: 0 0 5px 0;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .stat-content .number {
      color: #7393A7;
      font-size: 28px;
      font-weight: 700;
      margin: 0;
    }

    .action-section {
      background: white;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
      margin-bottom: 30px;
    }

    .action-section h2 {
      color: #7393A7;
      font-size: 1.5rem;
      font-weight: 700;
      margin-bottom: 20px;
    }

    .action-buttons {
      display: flex;
      flex-wrap: wrap;
      gap: 15px;
    }

    .btn-action {
      background: linear-gradient(135deg, #7393A7 0%, #5f7a8d 100%);
      color: white;
      padding: 15px 25px;
      border: none;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 10px;
      font-size: 14px;
    }

    .btn-action:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(115, 147, 167, 0.3);
      color: white;
      text-decoration: none;
    }

    .btn-action i {
      font-size: 16px;
    }

    .welcome-box {
      background: linear-gradient(135deg, #7393A7 0%, #5f7a8d 100%);
      color: white;
      padding: 30px;
      border-radius: 12px;
      margin-top: 3%;
      margin-bottom: 30px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .welcome-box h2 {
      margin: 0 0 10px 0;
      font-size: 1.8rem;
    }

    .welcome-box p {
      margin: 0;
      opacity: 0.9;
      font-size: 16px;
    }
  </style>
</head>

<?php include 'super_admin_sidebar.php'; ?>

<body>
  <section class="home-section">
    <div class="home-content">
      <i class='bx bx-menu'></i>
      <span class="text">PharmAssist</span>
    </div>

    <div class="dashboard-container">
      <!-- Welcome Section -->
      <div class="welcome-box">
        <h2 style="font-family: 'Bricolage Grotesque';">Welcome, CEO!</h2>
        <p>You have full access to system-wide operations and branch management.</p>
      </div>

      <!-- Page Header -->
      <div class="page-header" style="margin-top: -1%;">
        <h1 style="font-family: 'Bricolage Grotesque';">CEO Dashboard</h1>
        <p>Manage all pharmacy branches and system operations from here.</p>
      </div>

      <!-- Stats Section -->
      <div class="dashboard-grid">
        <?php
          // Get total branches count
          $branches_result = $conn->query("SELECT COUNT(*) as total FROM branches");
          $branches_count = $branches_result->fetch_assoc()['total'];

          // Get total medicines count
          $medicines_result = $conn->query("SELECT COUNT(*) as total FROM medicine");
          $medicines_count = $medicines_result->fetch_assoc()['total'];

          // Get total users count
          $users_result = $conn->query("SELECT COUNT(*) as total FROM users");
          $users_count = $users_result->fetch_assoc()['total'];

          // Get total admins count
          $admins_result = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'admin'");
          $admins_count = $admins_result->fetch_assoc()['total'];
        ?>

        <div class="stat-card">
          <div class="stat-icon">
            <i class="bi bi-building"></i>
          </div>
          <div class="stat-content">
            <h3>Total Branches</h3>
            <p class="number"><?php echo $branches_count; ?></p>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon">
            <i class="bi bi-capsule"></i>
          </div>
          <div class="stat-content">
            <h3>Total Medicines</h3>
            <p class="number"><?php echo $medicines_count; ?></p>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon">
            <i class="bi bi-people"></i>
          </div>
          <div class="stat-content">
            <h3>Total Users</h3>
            <p class="number"><?php echo $users_count; ?></p>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon">
            <i class="bi bi-person-badge"></i>
          </div>
          <div class="stat-content">
            <h3>Total Admins</h3>
            <p class="number"><?php echo $admins_count; ?></p>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="action-section">
        <h2 style="font-family: 'Bricolage Grotesque';"><i class="bi bi-lightning-charge"></i> Quick Actions</h2>
        <div class="action-buttons">
          <a href="super_admin_manage_branches.php" class="btn-action">
            <i class="bi bi-building"></i> Manage Branches
          </a>
          <a href="super_admin_notif.php" class="btn-action">
            <i class="bi bi-bell"></i> Notifications
          </a>
          <a href="super_admin_messages.php" class="btn-action">
            <i class="bi bi-chat-dots"></i> Messages
          </a>
        </div>
      </div>

    </div>
  </section>

  <script src="source/sidebar.js"></script>
  <script src="source/homepage.js"></script>
</body>
</html>