<?php
include 'config/connection.php';
session_start();

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'ceo') {
    header("Location: login.php");
    exit();
}

// Get all branches with their low stock medicines
// Real-world thresholds:
// - Critical: < 5 units (immediate reorder needed)
// - Low: 5-20 units (monitor closely, reorder soon)
// - Out of Stock: 0 units (emergency)

$low_stock_threshold = 20;  // Warning threshold
$critical_threshold = 5;    // Critical threshold

// Get all active alerts (unresolved)
$alerts_query = "
    SELECT 
        m.medicine_id,
        m.medicine_name,
        m.quantity as stock,
        m.branch,
        b.branch_id,
        CASE 
            WHEN m.quantity = 0 THEN 'out_of_stock'
            WHEN m.quantity < $critical_threshold THEN 'critical_stock'
            WHEN m.quantity <= $low_stock_threshold THEN 'low_stock'
        END as alert_type
    FROM medicine m
    LEFT JOIN branches b ON b.branch_name = m.branch
    WHERE m.quantity <= $low_stock_threshold
    ORDER BY 
        CASE 
            WHEN m.quantity = 0 THEN 1
            WHEN m.quantity < $critical_threshold THEN 2
            WHEN m.quantity <= $low_stock_threshold THEN 3
        END ASC,
        m.quantity ASC,
        m.branch ASC
";

$alerts_result = $conn->query($alerts_query);
if (!$alerts_result) {
    die("Query Error: " . $conn->error);
}
$total_alerts = $alerts_result->num_rows;

// Count by severity
$severity_counts = [
    'out_of_stock' => 0,
    'critical_stock' => 0,
    'low_stock' => 0
];

// Reset pointer and count
$temp_result = $conn->query($alerts_query);
while($row = $temp_result->fetch_assoc()) {
    $severity_counts[$row['alert_type']]++;
}

// Get stats
$total_branches = $conn->query("SELECT COUNT(*) as count FROM branches")->fetch_assoc()['count'];
$affected_branches = $conn->query("
    SELECT COUNT(DISTINCT branch) as count FROM medicine 
    WHERE quantity <= $low_stock_threshold
")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Stock Alerts | PharmAssist</title>

<link rel="stylesheet" href="plugins/admin_sidebar.css?v=2">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@400;600;700&family=Tinos:wght@400;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet">

<style>
* {
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

.dashboard-container {
    padding: 30px;
    max-width: 1400px;
    margin: 0 auto;
}

.page-header {
    background: white;
    padding: 30px;
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
    display: flex;
    align-items: center;
    gap: 12px;
}

.page-header p {
    color: #6C737E;
    margin: 0;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    text-align: center;
}

.stat-card.critical {
    border-top: 4px solid #DC3545;
    background: linear-gradient(135deg, #FFE5E5 0%, #FFF0F0 100%);
}

.stat-card.warning {
    border-top: 4px solid #FFC107;
    background: linear-gradient(135deg, #FFF9E5 0%, #FFFBF0 100%);
}

.stat-card.normal {
    border-top: 4px solid #17A2B8;
    background: linear-gradient(135deg, #E5F8FB 0%, #F0FCFD 100%);
}

.stat-card.info {
    border-top: 4px solid #7393A7;
    background: linear-gradient(135deg, #E8ECF1 0%, #F0F4F7 100%);
}

.stat-number {
    font-size: 2.5rem;
    font-weight: 700;
    color: #7393A7;
    margin: 10px 0;
}

.stat-card.critical .stat-number { color: #DC3545; }
.stat-card.warning .stat-number { color: #FFC107; }

.stat-label {
    color: #6C737E;
    font-size: 14px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.alerts-container {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    padding: 30px;
    margin-bottom: 30px;
}

.alerts-container h2 {
    color: #7393A7;
    font-size: 1.5rem;
    margin: 0 0 20px 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.branch-section {
    margin-bottom: 25px;
}

.branch-header {
    background: linear-gradient(135deg, #7393A7 0%, #B5CFD8 100%);
    color: white;
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.branch-header h3 {
    margin: 0;
    font-size: 1.1rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.branch-alert-count {
    background: rgba(255,255,255,0.2);
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 600;
}

.alert-item {
    border-left: 4px solid #7393A7;
    padding: 15px;
    margin-bottom: 12px;
    border-radius: 8px;
    background: #f8f9fa;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.alert-item.out-of-stock {
    border-left-color: #DC3545;
    background: #FFE5E5;
}

.alert-item.critical-stock {
    border-left-color: #FFC107;
    background: #FFF9E5;
}

.alert-item.low-stock {
    border-left-color: #17A2B8;
    background: #E5F8FB;
}

.alert-content {
    flex: 1;
}

.alert-medicine {
    font-weight: 700;
    color: #333;
    margin-bottom: 5px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.alert-icon {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    color: white;
    font-weight: 700;
}

.alert-item.out-of-stock .alert-icon {
    background: #DC3545;
}

.alert-item.critical-stock .alert-icon {
    background: #FFC107;
}

.alert-item.low-stock .alert-icon {
    background: #17A2B8;
}

.alert-stock {
    color: #6C737E;
    font-size: 13px;
}

.stock-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.85rem;
}

.stock-badge.out-of-stock {
    background: #DC3545;
    color: white;
}

.stock-badge.critical-stock {
    background: #FFC107;
    color: #333;
}

.stock-badge.low-stock {
    background: #17A2B8;
    color: white;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #6C737E;
}

.empty-state i {
    font-size: 4rem;
    color: #B5CFD8;
    margin-bottom: 20px;
    display: block;
}

.empty-state h3 {
    color: #7393A7;
    font-size: 1.3rem;
    margin: 0 0 10px 0;
}

.threshold-info {
    background: linear-gradient(135deg, #E8ECF1 0%, #F0F4F7 100%);
    border-left: 4px solid #7393A7;
    padding: 15px 20px;
    border-radius: 8px;
    margin-top: 30px;
    font-size: 13px;
    color: #6C737E;
}

.threshold-info strong {
    color: #7393A7;
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .alert-item {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .stock-badge {
        margin-top: 8px;
    }
}
</style>
</head>

<body>
    <?php include 'super_admin_sidebar.php'; ?>
  <section class="home-section">
    <div class="home-content">
      <i class='bx bx-menu'></i>
      <span class="text">PharmAssist</span>
    </div>

    <div class="dashboard-container">
      <!-- Page Header -->
      <div class="page-header">
        <h1 style="font-family: 'Bricolage Grotesque';">
          <i class="bi bi-exclamation-triangle"></i> Stock Alerts
        </h1>
        <p>Monitor medicine stock levels across all branches and receive critical low-stock alerts.</p>
      </div>

      <!-- Stats Grid -->
      <div class="stats-grid">
        <div class="stat-card critical">
          <div class="stat-label" ><i class="bi bi-exclamation-circle"></i> Out of Stock</div>
          <div class="stat-number"><?= $severity_counts['out_of_stock'] ?></div>
        </div>
        
        <div class="stat-card warning">
          <div class="stat-label"><i class="bi bi-exclamation"></i> Critical Stock</div>
          <div class="stat-number"><?= $severity_counts['critical_stock'] ?></div>
        </div>
        
        <div class="stat-card normal">
          <div class="stat-label"><i class="bi bi-info-circle"></i> Low Stock</div>
          <div class="stat-number"><?= $severity_counts['low_stock'] ?></div>
        </div>

        <div class="stat-card info">
          <div class="stat-label"><i class="bi bi-building"></i> Affected Branches</div>
          <div class="stat-number"><?= $affected_branches ?> / <?= $total_branches ?></div>
        </div>
      </div>

      <!-- Alerts Section -->
      <div class="alerts-container">
        <h2 style="font-family: 'Bricolage Grotesque';">
          <i class="bi bi-bell-fill"></i>
          Branch Stock Status
          <span style="margin-left: auto; font-size: 0.9rem; color: #6C737E; font-weight: normal;">
            Total Alerts: <?= $total_alerts ?>
          </span>
        </h2>

        <?php if ($total_alerts > 0): ?>
          <?php 
          // Group by branch
          $branches_alerts = [];
          $alerts_result = $conn->query($alerts_query);
          
          while ($row = $alerts_result->fetch_assoc()) {
              $branch = $row['branch'] ?? 'Unknown Branch';
              if (!isset($branches_alerts[$branch])) {
                  $branches_alerts[$branch] = [];
              }
              $branches_alerts[$branch][] = $row;
          }
          
          // Display by branch
          foreach ($branches_alerts as $branch => $alerts): 
          ?>
            <div class="branch-section">
              <div class="branch-header">
                <h3>
                  <i class="bi bi-shop"></i>
                  <?= htmlspecialchars($branch) ?>
                </h3>
                <span class="branch-alert-count"><?= count($alerts) ?> alert<?= count($alerts) !== 1 ? 's' : '' ?></span>
              </div>

              <div style="padding-left: 10px;">
                <?php foreach ($alerts as $alert): 
                  $alert_type = $alert['alert_type'];
                  $icon = '';
                  $text = '';
                  
                  if ($alert_type === 'out_of_stock') {
                      $icon = '!';
                      $text = 'Out of Stock';
                  } elseif ($alert_type === 'critical_stock') {
                      $icon = '⚠';
                      $text = 'Critical Stock';
                  } else {
                      $icon = 'i';
                      $text = 'Low Stock';
                  }
                ?>
                  <div class="alert-item <?= str_replace('_', '-', $alert_type) ?>">
                    <div class="alert-content">
                      <div class="alert-medicine">
                        <span class="alert-icon"><?= $icon ?></span>
                        <?= htmlspecialchars($alert['medicine_name']) ?>
                      </div>
                      <div class="alert-stock">
                        Current Stock: <strong><?= $alert['stock'] ?> units</strong>
                      </div>
                    </div>
                    <span class="stock-badge <?= str_replace('_', '-', $alert_type) ?>">
                      <?= $text ?>
                    </span>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endforeach; ?>

          <!-- Threshold Info -->
          <div class="threshold-info">
            <strong>⚙️ Alert Thresholds:</strong><br>
            🔴 <strong>Out of Stock:</strong> 0 units (Critical - Immediate Action Required)<br>
            🟡 <strong>Critical Stock:</strong> 1-4 units (High Priority - Reorder Immediately)<br>
            🔵 <strong>Low Stock:</strong> 5-20 units (Monitor Closely - Plan Reorder Soon)
          </div>

        <?php else: ?>
          <div class="empty-state">
            <i class="bi bi-check-circle"></i>
            <h3>All Stock Levels Normal</h3>
            <p>No low stock alerts at this time. All branches have adequate medicine inventory.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <script src="source/sidebar.js"></script>
  <script src="source/homepage.js"></script>
</body>
</html>