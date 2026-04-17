<?php
include 'config/connection.php';
include 'config/email_functions.php';
session_start();

if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION['role'] !== 'ceo') {
    header("Location: homepage.php");
    exit();
}

if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION['role'] !== 'ceo') {
    header("Location: homepage.php");
    exit();
}

$error_message = '';
$success_message = '';

// Handle ID approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $user_id = intval($_POST['user_id']);
    $rejection_reason = trim($_POST['rejection_reason'] ?? '');

    if ($action === 'approve') {
        // Approve ID
        $update_query = "UPDATE users SET id_verification_status = 'approved' WHERE user_id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param('i', $user_id);

        if ($stmt->execute()) {
            // Log the action
            $log_query = "INSERT INTO id_verification_log (user_id, admin_id, status, reviewed_at) VALUES (?, ?, 'approved', NOW())";
            $log_stmt = $conn->prepare($log_query);
            $admin_id = $_SESSION['id'];
            $log_stmt->bind_param('ii', $user_id, $admin_id);
            $log_stmt->execute();
            $log_stmt->close();

            // Get user email and name
            $user_query = "SELECT email, first_name, last_name FROM users WHERE user_id = ?";
            $user_stmt = $conn->prepare($user_query);
            $user_stmt->bind_param('i', $user_id);
            $user_stmt->execute();
            $user_result = $user_stmt->get_result()->fetch_assoc();
            $user_stmt->close();

            // Send approval email
            sendIDVerificationNotification($user_result['email'], $user_result['first_name'] . ' ' . $user_result['last_name'], 'approved');

            $success_message = 'ID approved successfully! User has been notified.';
        } else {
            $error_message = 'Failed to approve ID. Please try again.';
        }
        $stmt->close();

    } elseif ($action === 'reject') {
        if (empty($rejection_reason)) {
            $error_message = 'Please provide a reason for rejection.';
        } else {
            // Reject ID
            $update_query = "UPDATE users SET id_verification_status = 'rejected', id_rejected_reason = ? WHERE user_id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param('si', $rejection_reason, $user_id);

            if ($stmt->execute()) {
                // Log the action
                $log_query = "INSERT INTO id_verification_log (user_id, admin_id, status, rejection_reason, reviewed_at) VALUES (?, ?, 'rejected', ?, NOW())";
                $log_stmt = $conn->prepare($log_query);
                $admin_id = $_SESSION['id'];
                $log_stmt->bind_param('iis', $user_id, $admin_id, $rejection_reason);
                $log_stmt->execute();
                $log_stmt->close();

                // Get user email and name
                $user_query = "SELECT email, first_name, last_name FROM users WHERE user_id = ?";
                $user_stmt = $conn->prepare($user_query);
                $user_stmt->bind_param('i', $user_id);
                $user_stmt->execute();
                $user_result = $user_stmt->get_result()->fetch_assoc();
                $user_stmt->close();

                // Send rejection email
                sendIDVerificationNotification($user_result['email'], $user_result['first_name'] . ' ' . $user_result['last_name'], 'rejected', $rejection_reason);

                $success_message = 'ID rejected. User has been notified to resubmit.';
            } else {
                $error_message = 'Failed to reject ID. Please try again.';
            }
            $stmt->close();
        }
    }
}

// Get filter status
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'pending';
$valid_statuses = ['pending', 'approved', 'rejected'];
if (!in_array($filter_status, $valid_statuses)) {
    $filter_status = 'pending';
}

// Get search query
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Fetch pending/approved/rejected IDs with search functionality
$query = "SELECT u.user_id, u.first_name, u.last_name, u.username, u.email, u.id_photo, u.id_verification_status 
          FROM users u 
          WHERE u.id_photo IS NOT NULL 
          AND u.id_verification_status = ?";

// Add search condition if search query is provided
if (!empty($search_query)) {
    $query .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR CONCAT(u.first_name, ' ', u.last_name) LIKE ?)";
}

$query .= " ORDER BY u.user_id DESC";

$stmt = $conn->prepare($query);

if (!empty($search_query)) {
    $search_param = '%' . $search_query . '%';
    $stmt->bind_param('sssss', $filter_status, $search_param, $search_param, $search_param, $search_param);
} else {
    $stmt->bind_param('s', $filter_status);
}

$stmt->execute();
$result = $stmt->get_result();
$submissions = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get statistics
$stats_query = "SELECT 
                COUNT(CASE WHEN id_verification_status = 'pending' THEN 1 END) as pending_count,
                COUNT(CASE WHEN id_verification_status = 'approved' THEN 1 END) as approved_count,
                COUNT(CASE WHEN id_verification_status = 'rejected' THEN 1 END) as rejected_count
                FROM users WHERE id_photo IS NOT NULL";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>ID Verification | PharmAssist</title>

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

    /* ── Layout ── */
.home-section {
      position: relative;
      background: #E8ECF1;
      min-height: 100vh;
      top: 0;
      left: 78px;
      width: calc(100% - 78px);
      transition: all 0.5s ease;
      z-index: auto;
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
      margin-top: 3%;
      padding: 30px;
      max-width: 1400px;
      margin-left: auto;
      margin-right: auto;
    }

    h1 {
      color: #2c3e50;
      margin-bottom: 10px;
      font-size: 2.5rem;
      font-weight: 700;
    }

    .page-subtitle {
      color: #7393A7;
      margin-bottom: 30px;
      font-size: 1rem;
    }

    /* Stats Grid */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }

    .stat-card {
      background: white;
      padding: 25px;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
      text-align: center;
      transition: transform 0.3s, box-shadow 0.3s;
    }

    .stat-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
    }

    .stat-number {
      font-size: 2.5rem;
      font-weight: 700;
      color: #2c3e50;
      margin: 10px 0;
    }

    .stat-label {
      color: #7393A7;
      font-size: 0.95rem;
    }

    /* Filter Tabs */
    .filter-tabs {
      display: flex;
      gap: 15px;
      flex-wrap: wrap;
    }

    .filter-btn {
      padding: 10px 25px;
      border: 2px solid #7393A7;
      border-radius: 25px;
      background: white;
      color: #7393A7;
      text-decoration: none;
      cursor: pointer;
      transition: all 0.3s;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .filter-btn:hover {
      border-color: #2c3e50;
      color: #2c3e50;
    }

    .filter-btn.active {
      background: #2c3e50;
      color: white;
      border-color: #2c3e50;
    }

    /* Search Bar */
    .search-container {
      display: flex;
      gap: 10px;
      margin-bottom: 20px;
      align-items: center;
    }

    .search-input {
      flex: 1;
      padding: 12px 16px;
      border: 2px solid #7393A7;
      border-radius: 25px;
      font-size: 0.95rem;
      transition: all 0.3s;
      font-family: 'Bricolage Grotesque', sans-serif;
    }

    .search-input:focus {
      outline: none;
      border-color: #2c3e50;
      box-shadow: 0 0 0 3px rgba(44, 62, 80, 0.1);
    }

    .search-btn {
      padding: 12px 24px;
      background: #2c3e50;
      color: white;
      border: none;
      border-radius: 25px;
      cursor: pointer;
      transition: all 0.3s;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .search-btn:hover {
      background: #1a252f;
    }

    .clear-search-btn {
      padding: 12px 16px;
      background: #f0f0f0;
      color: #2c3e50;
      border: none;
      border-radius: 25px;
      cursor: pointer;
      transition: all 0.3s;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .clear-search-btn:hover {
      background: #e0e0e0;
    }

    /* Submissions Grid */
    .submissions-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
      gap: 25px;
    }

    .submission-card {
      background: white;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
      transition: all 0.3s;
    }

    .submission-card:hover {
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
      transform: translateY(-3px);
    }

    .id-photo-container {
      width: 100%;
      height: 200px;
      background: #f0f0f0;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
    }

    .id-photo {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.3s;
    }

    .id-photo:hover {
      transform: scale(1.05);
    }

    .submission-info {
      padding: 20px;
    }

    .submission-info h3 {
      color: #2c3e50;
      margin: 0 0 15px 0;
      font-size: 1.2rem;
    }

    .submission-info p {
      color: #666;
      margin: 8px 0;
      font-size: 0.95rem;
    }

    .status-badge {
      display: inline-block;
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 0.85rem;
      font-weight: 600;
      margin: 10px 0;
    }

    .status-pending {
      background-color: #fff3cd;
      color: #856404;
    }

    .status-approved {
      background-color: #d4edda;
      color: #155724;
    }

    .status-rejected {
      background-color: #f8d7da;
      color: #721c24;
    }

    /* Action Buttons */
    .action-buttons {
      display: flex;
      gap: 10px;
      margin-top: 15px;
    }

    .btn-small {
      flex: 1;
      padding: 10px;
      border: none;
      border-radius: 6px;
      font-size: 0.9rem;
      cursor: pointer;
      transition: all 0.3s;
      font-weight: 600;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
    }

    .btn-view {
      background-color: #84B179;
      color: white;
    }

    .btn-view:hover {
      background-color: #218838;
    }

    .btn-reject {
      background-color: #E5707E;
      color: white;
    }

    .btn-reject:hover {
      background-color: #c82333;
    }

    /* Empty State */
    .empty-state {
      text-align: center;
      padding: 60px 20px;
      background: white;
      border-radius: 12px;
    }

    .empty-state i {
      font-size: 60px;
      color: #ccc;
      margin-bottom: 20px;
    }

    .empty-state h3 {
      color: #2c3e50;
      margin: 20px 0;
    }

    .empty-state p {
      color: #7393A7;
    }

    /* Modal Customization */
    .modal-content {
      border-radius: 12px;
      border: none;
    }

    .modal-header {
      background-color: #f8f9fa;
      border-bottom: 1px solid #dee2e6;
      border-radius: 12px 12px 0 0;
    }

    .modal-title {
      color: #2c3e50;
      font-weight: 600;
    }

    /* Responsive */
    @media (max-width: 768px) {
      .dashboard-container {
        margin-top: 60px;
        padding: 15px;
      }

      h1 {
        font-size: 1.8rem;
      }

      .submissions-grid {
        grid-template-columns: 1fr;
      }

      .stats-grid {
        grid-template-columns: 1fr;
      }

      .filter-tabs {
        flex-direction: column;
      }

      .filter-btn {
        justify-content: center;
      }

      .action-buttons {
        flex-direction: column;
      }

      .btn-small {
        width: 100%;
      }

      .search-container {
        flex-direction: column;
      }

      .search-input {
        width: 100%;
      }

      .search-btn {
        width: 100%;
        justify-content: center;
      }

      .clear-search-btn {
        width: 100%;
        justify-content: center;
      }
    }

    .branch-management-container {
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
      margin-top: 60px;
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
  </style>
</head>
<body>
  <?php include 'super_admin_sidebar.php'; ?>
  <section class="home-section">
    <div class="home-content">
      <i class='bx bx-menu'></i>
      <span class="text">PharmAssist</span>
    </div>

    <div class="branch-management-container">
      <!-- Header -->
      <div class="page-header">
        <h1 style="font-family: 'Bricolage Grotesque';"><i class="bi bi-person-vcard"></i> ID Verification</h1>
        <p>Review and manage user ID verifications</p>
      </div>

      <?php if (isset($success_message) && !empty($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <?php if (isset($error_message) && !empty($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <i class="bi bi-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <!-- Statistics -->
      <div class="stats-grid">
        <div class="stat-card">
          <div style="color: #ff9800; font-size: 30px;"><i class="bi bi-clock"></i></div>
          <div class="stat-number"><?php echo $stats['pending_count']; ?></div>
          <div class="stat-label">Pending Review</div>
        </div>
        <div class="stat-card">
          <div style="color: #28a745; font-size: 30px;"><i class="bi bi-check-circle"></i></div>
          <div class="stat-number"><?php echo $stats['approved_count']; ?></div>
          <div class="stat-label">Approved</div>
        </div>
        <div class="stat-card">
          <div style="color: #dc3545; font-size: 30px;"><i class="bi bi-x-circle"></i></div>
          <div class="stat-number"><?php echo $stats['rejected_count']; ?></div>
          <div class="stat-label">Rejected</div>
        </div>
      </div>

      <!-- Filter Tabs -->
      <div style="background: white; padding: 20px; border-radius: 12px; margin-bottom: 30px;">
        <div class="filter-tabs">
          <a href="?status=pending" class="filter-btn <?php echo $filter_status === 'pending' ? 'active' : ''; ?>">
            <i class="bi bi-clock"></i> Pending
          </a>
          <a href="?status=approved" class="filter-btn <?php echo $filter_status === 'approved' ? 'active' : ''; ?>">
            <i class="bi bi-check-circle"></i> Approved
          </a>
          <a href="?status=rejected" class="filter-btn <?php echo $filter_status === 'rejected' ? 'active' : ''; ?>">
            <i class="bi bi-x-circle"></i> Rejected
          </a>
        </div>
      </div>

      <!-- Search Bar -->
      <div style="background: white; padding: 20px; border-radius: 12px; margin-bottom: 30px;">
        <form method="GET" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
          <input type="hidden" name="status" value="<?php echo htmlspecialchars($filter_status); ?>">
          <input type="text" name="search" class="search-input" placeholder="Search by name or email..." value="<?php echo htmlspecialchars($search_query); ?>" style="flex: 1;">
          <button type="submit" class="search-btn">
            <i class="bi bi-search"></i> Search
          </button>
          <?php if (!empty($search_query)): ?>
            <a href="?status=<?php echo htmlspecialchars($filter_status); ?>" class="clear-search-btn">
              <i class="bi bi-x"></i> Clear
            </a>
          <?php endif; ?>
        </form>
      </div>

      <!-- Submissions Grid -->
      <?php if (count($submissions) > 0): ?>
        <div class="submissions-grid">
          <?php foreach ($submissions as $submission): ?>
            <div class="submission-card">
              <div class="id-photo-container">
                <?php
                $file_path = 'uploads/id_photos/' . htmlspecialchars($submission['id_photo']);
                if (file_exists($file_path)) {
                    echo '<img src="' . $file_path . '" alt="ID Photo" class="id-photo" style="cursor:pointer;">';
                } else {
                    echo '<i class="bi bi-file-earmark-image" style="font-size: 60px;"></i>';
                }
                ?>
              </div>
              <div class="submission-info">
                <h3><?php echo htmlspecialchars($submission['first_name'] . ' ' . $submission['last_name']); ?></h3>
                <p><strong>Username:</strong> <?php echo htmlspecialchars($submission['username']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($submission['email']); ?></p>

                <span class="status-badge status-<?php echo htmlspecialchars($submission['id_verification_status']); ?>">
                  <?php echo strtoupper(htmlspecialchars($submission['id_verification_status'])); ?>
                </span>

                <?php if ($filter_status === 'pending'): ?>
                  <div class="action-buttons">
                    <button class="btn-small btn-view" data-bs-toggle="modal" data-bs-target="#approveModal<?php echo $submission['user_id']; ?>">
                      <i class="bi bi-check"></i> Approve
                    </button>
                    <button class="btn-small btn-reject" data-bs-toggle="modal" data-bs-target="#rejectModal<?php echo $submission['user_id']; ?>">
                      <i class="bi bi-x"></i> Reject
                    </button>
                  </div>
                <?php endif; ?>
              </div>
            </div>

            <!-- Approve Modal -->
            <div class="modal fade" id="approveModal<?php echo $submission['user_id']; ?>" tabindex="-1">
              <div class="modal-dialog">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title">Approve ID</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <form method="POST">
                    <div class="modal-body">
                      <p>Are you sure you want to approve the ID for <strong><?php echo htmlspecialchars($submission['first_name']); ?></strong>?</p>
                      <p style="color: #666; font-size: 12px;">The user will be notified via email.</p>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                      <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle"></i> Approve ID
                      </button>
                    </div>
                    <input type="hidden" name="action" value="approve">
                    <input type="hidden" name="user_id" value="<?php echo $submission['user_id']; ?>">
                  </form>
                </div>
              </div>
            </div>

            <!-- Reject Modal -->
            <div class="modal fade" id="rejectModal<?php echo $submission['user_id']; ?>" tabindex="-1">
              <div class="modal-dialog">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title">Reject ID</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <form method="POST">
                    <div class="modal-body">
                      <p>Are you sure you want to reject the ID for <strong><?php echo htmlspecialchars($submission['first_name'] . ' ' . $submission['last_name']); ?></strong>?</p>
                      <div class="mb-3">
                        <label for="rejection_reason" class="form-label">Reason for Rejection *</label>
                        <textarea class="form-control" name="rejection_reason" id="rejection_reason" rows="3" required placeholder="e.g., ID not clear, expired, or invalid format"></textarea>
                      </div>
                      <p style="color: #666; font-size: 12px;">The user will be notified via email and can resubmit their ID.</p>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                      <button type="submit" class="btn btn-danger">
                        <i class="bi bi-x-circle"></i> Reject ID
                      </button>
                    </div>
                    <input type="hidden" name="action" value="reject">
                    <input type="hidden" name="user_id" value="<?php echo $submission['user_id']; ?>">
                  </form>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="empty-state">
          <i class="bi bi-inbox"></i>
          <h3>No submissions</h3>
          <p><?php echo !empty($search_query) ? 'No results found for your search.' : 'There are no ID submissions to review for this status.'; ?></p>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <div id="imageModal" style="
    display:none;
    position:fixed;
    z-index:9999;
    top:0;
    left:0;
    width:100%;
    height:100%;
    background:rgba(0,0,0,0.8);
    justify-content:center;
    align-items:center;
">
    <img id="modalImage" style="max-width:90%; max-height:90%;">
  </div>

  <script>
    document.querySelectorAll('.id-photo').forEach(img => {
    img.addEventListener('click', function () {
        const modal = document.getElementById('imageModal');
        const modalImg = document.getElementById('modalImage');

        modal.style.display = 'flex';
        modalImg.src = this.src;
    });
});

    document.getElementById('imageModal').addEventListener('click', function () {
    this.style.display = 'none';
});

    </script>
  <script src="source/sidebar.js"></script>
  <script src="source/homepage.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>