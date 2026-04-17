<?php
include 'config/connection.php';
session_start();

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'customer') {
    header("Location: login.php");
    exit();
}


$user_id = $_SESSION['id'];

$stmt = $conn->prepare("
    SELECT r.*, b.branch_name
    FROM reservations r
    LEFT JOIN branches b ON r.branch_id = b.branch_id
    WHERE r.user_id = ?
    ORDER BY r.date_reserved DESC
");

if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Notifications | PharmAssist</title>

  <link rel="stylesheet" href="plugins/sidebar.css?v=7">
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,200..800&family=Tinos:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
  <link rel="icon" href="website_icon/favicon.png" type="image/png" sizes="64x64">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Tinos', serif;
      background: #E8ECF1;
      overflow-x: hidden;
    }

    .page-wrapper {
      padding: 80px 20px 20px 20px;
      min-height: calc(100vh - 60px);
      transform-origin: top center;
      transition: transform 0.2s ease-out;
    }

    .notif-container {
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
      border: 1px solid #b5cfd8;
      padding: 30px;
      width: 100%;
      max-width: 1200px;
      margin: 0 auto;
    }

    .notif-header {
      text-align: center;
      font-family: "Tinos", serif;
      color: #6C737E;
      margin-bottom: 25px;
    }

    .notif-header h2 {
      font-size: 1.8rem;
      font-weight: 700;
      margin-bottom: 8px;
    }

    .notif-header p {
      font-size: 0.95rem;
    }

    .success-msg {
      background: #d4edda;
      color: #155724;
      padding: 12px 20px;
      border-radius: 8px;
      margin-bottom: 20px;
      border: 1px solid #c3e6cb;
      text-align: center;
      font-weight: 600;
    }

    .error-msg {
      background: #f8d7da;
      color: #721c24;
      padding: 12px 20px;
      border-radius: 8px;
      margin-bottom: 20px;
      border: 1px solid #f5c6cb;
      text-align: center;
      font-weight: 600;
    }

    .notif-container table {
      width: 100%;
      border-collapse: collapse;
      background: #fff;
    }

    .notif-container th {
      background-color: #7393A7;
      color: white;
      text-align: left;
      padding: 12px;
      font-weight: 600;
    }

    .notif-container th:nth-child(1), 
    .notif-container th:nth-child(3),  
    .notif-container th:nth-child(4),  
    .notif-container th:nth-child(5) { 
      text-align: center;
    }

    .notif-container td {
      padding: 12px;
      border-bottom: 1px solid #e8ecf1;
      color: #333;
      font-family: "Bricolage Grotesque", sans-serif;
    }

    .notif-container td:nth-child(1),  
    .notif-container td:nth-child(3), 
    .notif-container td:nth-child(4),  
    .notif-container td:nth-child(5) { 
      text-align: center;
    }

    .notif-container tbody tr:nth-child(even) {
      background: #E8ECF1;
    }

    .notif-container tbody tr:hover {
      background: #B5CFD8;
      transition: background 0.3s ease;
    }

    .status-approved { 
      color: #28a745; 
      font-weight: bold; 
      padding: 6px 16px;
      border-radius: 20px;
      display: inline-block;
      font-size: 0.9rem;
    }
    
    .status-rejected { 
      color: #dc3545; 
      font-weight: bold; 
      padding: 6px 16px;
      border-radius: 20px;
      display: inline-block;
      font-size: 0.9rem;
    }
    
    .status-pending { 
      color: #ffc107; 
      font-weight: bold; 
      padding: 6px 16px;
      border-radius: 20px;
      display: inline-block;
      font-size: 0.9rem;
    }

    .code-display {
      padding: 8px 16px;
      border-radius: 6px;
      font-family: monospace;
      font-size: 16px;
      font-weight: bold;
      color: #7393A7;
      display: inline-block;
      margin-top: 5px;
      letter-spacing: 1px;
    }

    .rejection-reason {
      color: #721c24;
      font-style: italic;
      padding: 6px 12px;
      border-radius: 4px;
      display: inline-block;
      margin-top: 5px;
    }

    .waiting-text {
      color: #6C737E;
      font-style: italic;
    }

    /* ── TTS styles ── */
    .tts-notif-bar {
      display: flex;
      align-items: center;
      gap: 10px;
      background: #F8FAFC;
      border: 1px solid #D9EAFD;
      border-radius: 10px;
      padding: 8px 14px;
      margin-bottom: 20px;
    }

    .tts-notif-bar span {
      font-size: 13px;
      color: #7393A7;
      font-family: "Bricolage Grotesque", sans-serif;
      flex: 1;
    }

    .tts-read-all-btn, .tts-stop-btn {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      font-size: 12px;
      padding: 5px 14px;
      border-radius: 20px;
      border: 1px solid #BCCCDC;
      background: #D9EAFD;
      color: #2d3f50;
      cursor: pointer;
      font-family: "Bricolage Grotesque", sans-serif;
      transition: background 0.2s ease;
    }

    .tts-read-all-btn:hover { background: #BCCCDC; }
    .tts-stop-btn { background: #F8FAFC; }
    .tts-stop-btn:hover { background: #D9EAFD; }

    .tts-row-btn {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      font-size: 11px;
      padding: 3px 10px;
      border-radius: 20px;
      border: 1px solid #BCCCDC;
      background: #F8FAFC;
      color: #7393A7;
      cursor: pointer;
      font-family: "Bricolage Grotesque", sans-serif;
      margin-top: 6px;
      transition: background 0.2s ease;
    }

    .tts-row-btn:hover { background: #D9EAFD; }

    /* Tab Styles */
    .modal-tabs {
      display: flex;
      gap: 0;
      border-bottom: 2px solid #e8ecf1;
      margin-bottom: 25px;
      flex-wrap: wrap;
    }

    .modal-tab-btn {
      padding: 12px 20px;
      background: none;
      border: none;
      color: #7393A7;
      font-family: "Bricolage Grotesque", sans-serif;
      font-size: 14px;
      font-weight: 500;
      cursor: pointer;
      border-bottom: 3px solid transparent;
      transition: all 0.3s ease;
      position: relative;
      top: 2px;
    }

    .modal-tab-btn:hover {
      color: #5B7A92;
    }

    .modal-tab-btn.active {
      color: #2d3f50;
      border-bottom-color: #7393A7;
    }

    .modal-tab-content {
      display: none;
    }

    .modal-tab-content.active {
      display: block;
      animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
      }
      to {
        opacity: 1;
      }
    }

    .multimodal-info {
      background: #F8FAFC;
      padding: 15px;
      border-left: 4px solid #7393A7;
      border-radius: 6px;
      margin: 15px 0;
      font-size: 14px;
      color: #5A6B7A;
    }

    .multimodal-info h4 {
      color: #2d3f50;
      margin-top: 0;
      margin-bottom: 8px;
      font-family: "Bricolage Grotesque", sans-serif;
    }

    .multimodal-info ul {
      margin: 8px 0 0 20px;
      padding: 0;
    }

    .multimodal-info li {
      margin-bottom: 5px;
    }

    .tts-row-btn.reading {
      background: #D9EAFD;
      border-color: #9AA6B2;
      color: #2d3f50;
    }

    .tts-dot {
      width: 6px;
      height: 6px;
      border-radius: 50%;
      background: #9AA6B2;
      display: inline-block;
      animation: tts-pulse 1s infinite;
    }

    @keyframes tts-pulse {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.3; }
    }

    /* Floating Action Buttons */
    .floating-buttons {
      position: fixed;
      bottom: 30px;
      right: 30px;
      z-index: 999;
      display: flex;
      flex-direction: column;
      gap: 12px;
      align-items: center;
    }

    .fab {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      background: #7393A7;
      color: white;
      border: none;
      font-size: 24px;
      font-weight: bold;
      cursor: pointer;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.3s ease;
    }

    .fab:hover {
      background: #5B7A92;
      box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
      transform: scale(1.1);
    }

    .fab:active {
      transform: scale(0.95);
    }

    .fab:disabled {
      background: #ccc;
      cursor: not-allowed;
      opacity: 0.6;
    }

    #zoomInBtn {
      animation: slideIn 0.4s ease 0s;
    }

    #zoomOutBtn {
      animation: slideIn 0.4s ease 0.1s;
    }

    #guideBtn {
      animation: slideIn 0.4s ease 0.2s;
    }

    @keyframes slideIn {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* Modal Styles */
    .modal-overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0, 0, 0, 0.5);
      z-index: 1000;
    }

    .modal-overlay.active {
      display: flex;
      align-items: center;
      justify-content: center;
      animation: fadeIn 0.3s ease;
    }

    .modal-content {
      background: white;
      border-radius: 12px;
      width: 90%;
      max-width: 850px;
      height: 75vh;
      display: flex;
      flex-direction: column;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
      position: relative;
      overflow: hidden;
      animation: slideUp 0.3s ease;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
      }
      to {
        opacity: 1;
      }
    }

    @keyframes slideUp {
      from {
        transform: translateY(30px);
        opacity: 0;
      }
      to {
        transform: translateY(0);
        opacity: 1;
      }
    }

    .modal-header {
      background: #7393A7;
      padding: 25px 30px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-shrink: 0;
      border-radius: 12px 12px 0 0;
    }

    .modal-header h2 {
      color: white;
      font-family: "Bricolage Grotesque", sans-serif;
      font-weight: 600;
      font-size: 24px;
      margin: 0;
    }

    .modal-close {
      background: none;
      border: none;
      color: white;
      font-size: 32px;
      cursor: pointer;
      padding: 0;
      width: 40px;
      height: 40px;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: opacity 0.2s;
      line-height: 1;
    }

    .modal-close:hover {
      opacity: 0.7;
    }

    .modal-inner {
      flex: 1;
      overflow-y: auto;
      padding: 30px;
      background: white;
    }

    /* Scrollbar styling */
    .modal-inner::-webkit-scrollbar {
      width: 8px;
    }

    .modal-inner::-webkit-scrollbar-track {
      background: #f1f1f1;
    }

    .modal-inner::-webkit-scrollbar-thumb {
      background: #7393A7;
      border-radius: 4px;
    }

    .modal-inner::-webkit-scrollbar-thumb:hover {
      background: #5B7A92;
    }

    /* TTS Bar inside Modal */
    .modal-tts-bar {
      display: flex;
      align-items: center;
      gap: 10px;
      background: #F8FAFC;
      border: 1px solid #D9EAFD;
      border-radius: 10px;
      padding: 10px 16px;
      margin-bottom: 20px;
    }

    .modal-tts-bar span {
      font-size: 13px;
      color: #7393A7;
      font-family: "Bricolage Grotesque", sans-serif;
      flex: 1;
    }

    .modal-tts-btn, .modal-tts-stop-btn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-size: 12px;
      padding: 6px 16px;
      border-radius: 20px;
      border: 1px solid #BCCCDC;
      background: #D9EAFD;
      color: #2d3f50;
      cursor: pointer;
      font-family: "Bricolage Grotesque", sans-serif;
      transition: background 0.2s ease;
    }

    .modal-tts-btn:hover { background: #BCCCDC; }
    .modal-tts-stop-btn { background: #F8FAFC; }
    .modal-tts-stop-btn:hover { background: #D9EAFD; }

    .modal-step {
      margin-bottom: 25px;
      padding-bottom: 20px;
      border-bottom: 1px solid #e8ecf1;
    }

    .modal-step:last-child {
      border-bottom: none;
    }

    .modal-step h3 {
      color: #7393A7;
      font-family: "Bricolage Grotesque", sans-serif;
      font-size: 18px;
      margin-bottom: 10px;
    }

    .modal-step p {
      color: #333;
      line-height: 1.6;
      margin-bottom: 10px;
    }

    .modal-step ul {
      margin-left: 20px;
      margin-bottom: 15px;
    }

    .modal-step li {
      color: #333;
      line-height: 1.6;
      margin-bottom: 8px;
    }

    .modal-tts-step-btn {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      font-size: 12px;
      padding: 5px 14px;
      border-radius: 20px;
      border: 1px solid #BCCCDC;
      background: #F8FAFC;
      color: #7393A7;
      cursor: pointer;
      font-family: "Bricolage Grotesque", sans-serif;
      transition: background 0.2s ease;
      margin-top: 8px;
    }

    .modal-tts-step-btn:hover { background: #D9EAFD; }

    .modal-tts-step-btn.reading {
      background: #D9EAFD;
      border-color: #9AA6B2;
      color: #2d3f50;
    }

    .modal-step-image {
      background: #F4F8FA;
      border: 2px dashed #7393A7;
      border-radius: 8px;
      padding: 20px;
      margin: 15px 0;
      text-align: center;
      min-height: 200px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .modal-step-image img {
      max-width: 100%;
      height: auto;
      border-radius: 6px;
    }

    .modal-step-image-placeholder {
      color: #9AA6B2;
      font-style: italic;
      font-size: 14px;
    }

    @media (max-width: 768px) {
      .notif-container {
        padding: 15px;
        overflow-x: auto;
      }
      
      .notif-container table {
        font-size: 0.85rem;
      }
      
      .notif-container th,
      .notif-container td {
        padding: 8px;
      }

      .floating-buttons {
        bottom: 20px;
        right: 20px;
      }

      .fab {
        width: 50px;
        height: 50px;
        font-size: 20px;
      }
    }
    .modal-step-image-placeholder {
            background: #F4F8FA;
            border: 2px dashed #D9EAFD;
            border-radius: 8px;
            padding: 20px;
            margin: 15px 0;
            text-align: center;
            min-height: 150px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #9AA6B2;
            font-size: 14px;
            font-family: "Bricolage Grotesque", sans-serif;
        }

        @media (max-width: 600px) {
            .cart-wrapper { padding: 68px 12px 40px; }
            .form-row     { grid-template-columns: 1fr; }
            .item-thumb   { width: 64px; height: 64px; }
        }
  </style>
</head>
<body>

  <?php include 'sidebar.php'; ?>

  <section class="home-section">
    <div class="home-content">
      <i class='bx bx-menu'></i>
      <span><a href ="homepage.php" style="text-decoration: none; color: white; font-size: 1.5rem;" class="text fw-semibold">PharmAssist</span></a>
    </div>

    <div class="page-wrapper">
      <div class="notif-container">
        <div class="notif-header">
          <h2 style="font-family: 'Bricolage Grotesque';">Reservation Notifications</h2>
          <p>Track your medicine reservation status</p>
        </div>

        <?php if (isset($_GET['success'])): ?>
          <div class="success-msg">
            ✓ Reservation submitted successfully! Your request is now pending approval.
          </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
          <div class="error-msg">
            ✗ There was an error submitting your reservation. Please try again.
          </div>
        <?php endif; ?>

        <!-- Status Filter -->
        <div style="margin-bottom: 20px; display: flex; gap: 10px; align-items: center;">
          <label for="statusFilter" style="color: #6C737E; font-weight: 600; margin: 0;">Filter by Status:</label>
          <select id="statusFilter" style="padding: 8px 12px; border: 1px solid #b5cfd8; border-radius: 6px; font-family: 'Bricolage Grotesque', sans-serif; color: #333; background: white; cursor: pointer; font-size: 14px;">
            <option value="">All Statuses</option>
            <option value="Approved">Approved</option>
            <option value="Rejected">Rejected</option>
            <option value="Pending">Pending</option>
          </select>
        </div>

        <!-- TTS bar -->
        <div class="tts-notif-bar">
          <i class="bi bi-volume-up-fill" style="color:#9AA6B2; font-size:16px;"></i>
          <span>Text to speech</span>
          <button class="tts-read-all-btn" id="ttsReadAll">
            <i class="bi bi-play-fill"></i> Read all
          </button>
          <button class="tts-stop-btn" id="ttsStop">
            <i class="bi bi-stop-fill"></i> Stop
          </button>
        </div>

        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Medicine</th>
              <th>Quantity</th>
              <th>Code</th>
              <th>Branch</th>
              <th>Total Price</th>
              <th>Status</th>
              <th>Details</th>
            </tr>
          </thead>
          <tbody>
          <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): 
              // Build TTS text per row
              $ttsStatus = $row['status'];
              $ttsMed = htmlspecialchars($row['medicine']);
              $ttsQty = $row['quantity'];
              $ttsPrice = number_format($row['price'] * $row['quantity'], 2);
              $hasCode = !empty($row['code']);

              if ($hasCode) {
                  $ttsDetail = "Reservation code: " . htmlspecialchars($row['code']) . ". Present this code at the pharmacy.";
              } elseif ($row['status'] == "Rejected" && !empty($row['remarks'])) {
                  $ttsDetail = "Rejection reason: " . htmlspecialchars($row['remarks']);
              } else {
                  $ttsDetail = "Waiting for approval.";
              }

              $ttsBranch = !empty($row['branch_name']) ? " Branch: " . $row['branch_name'] . "." : "";
              $ttsText = "Reservation ID " . $row['reservation_id'] . ". Medicine: $ttsMed. Quantity: $ttsQty. Total price: $ttsPrice pesos. Status: $ttsStatus." . $ttsBranch . " $ttsDetail";
            ?>
              <tr data-tts="<?php echo htmlspecialchars($ttsText); ?>" data-status="<?php echo $row['status']; ?>">
                <td><?= $row['reservation_id'] ?></td>
                <td><?= htmlspecialchars($row['medicine']) ?></td>
                <td><?= $row['quantity'] ?></td>
                <td><?= $hasCode ? htmlspecialchars($row['code']) : '-' ?></td>
                <td><?= htmlspecialchars($row['branch_name'] ?? 'Unknown') ?></td>
                <td>₱<?= number_format($row['price'] * $row['quantity'], 2) ?></td>
                <td>
                  <?php if ($row['status'] == "Approved"): ?>
                    <span class="status-approved">Approved</span>
                  <?php elseif ($row['status'] == "Rejected"): ?>
                    <span class="status-rejected">Rejected</span>
                  <?php else: ?>
                    <span class="status-pending">Pending</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($row['status'] == "Rejected"): ?>
                     <div style="margin-bottom: 5px;">
                       <strong style="color: #dc3545;">Rejection Reason:</strong>
                     </div>
                     <span class="rejection-reason"><?= !empty($row['remarks']) ? htmlspecialchars($row['remarks']) : 'No reason provided.' ?></span>
                  <?php elseif ($row['status'] == "Approved" && $hasCode): ?>
                     <div style="margin-bottom: 5px;">
                       <strong style="color: #7393A7;">Reservation Code:</strong>
                       <span class="code-display"><?= htmlspecialchars($row['code']) ?></span>
                     </div>
                     <div style="margin-top: 8px; font-size: 14px; color: #28a745;">
                       Present this code at the pharmacy
                     </div>
                  <?php else: ?>
                     <span class="waiting-text">
                       <i class='bx bx-time-five'></i> Waiting for approval
                     </span>
                  <?php endif; ?>
                  <!-- TTS per-row button -->
                  <br>
                  <button class="tts-row-btn" data-tts="<?php echo htmlspecialchars($ttsText); ?>">
                    <i class="bi bi-volume-up"></i> Read
                  </button>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="7" style="text-align:center; color:#6C737E; padding: 40px;">
                <i class='bx bx-folder-open' style="font-size: 3rem; display: block; margin-bottom: 10px; opacity: 0.5;"></i>
                <strong>No reservations found</strong><br>
                <span style="font-size: 0.9rem;">Make a reservation to see it here!</span>
              </td>
            </tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>

  <!-- Floating Action Buttons -->
  <div class="floating-buttons">
    <button class="fab" id="zoomInBtn" title="Zoom In">+</button>
    <button class="fab" id="zoomOutBtn" title="Zoom Out">−</button>
    <button class="fab" id="guideBtn" title="Guide">?</button>
  </div>

  <!-- Guide Modal -->
  <div class="modal-overlay" id="guideModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Notifications Guide</h2>
        <button class="modal-close" id="closeBtn">&times;</button>
      </div>
      <!-- Tab Buttons -->
      <div style="background: white; padding: 0 30px; border-bottom: 1px solid #e8ecf1;">
        <div class="modal-tabs">
          <button class="modal-tab-btn active" data-tab="guide-tab">Page Guide</button>
          <button class="modal-tab-btn" data-tab="tts-tab">Text-to-Speech</button>
        </div>
      </div>

      <div class="modal-inner" id="modalInner">
        
        <!-- ===== GUIDE TAB ===== -->
        <div id="guide-tab" class="modal-tab-content active">
          <!-- TTS Controls -->
          <div class="modal-tts-bar">
            <i class="bi bi-volume-up-fill" style="color:#9AA6B2; font-size:15px;"></i>
            <span>Text to speech</span>
            <button class="modal-tts-btn" id="modalTtsReadGuide">
              <i class="bi bi-play-fill"></i> Read guide
            </button>
            <button class="modal-tts-stop-btn" id="modalTtsStop">
              <i class="bi bi-stop-fill"></i> Stop
            </button>
          </div>

          <!-- Overview -->
          <div class="modal-step" data-step="Reservation Notifications page. This page shows all your medicine reservations and their statuses. You can track whether your reservation is approved, rejected, or still pending approval.">
            <h3>Overview</h3>
            <p>This page displays all your medicine reservations with their current status. You can track whether your reservation is approved, rejected, or still pending approval.</p>
            <button class="modal-tts-step-btn">
              <i class="bi bi-volume-up"></i> Read step
            </button>
          </div>

          <!-- Understanding Status -->
          <div class="modal-step" data-step="Understanding Reservation Status. Approved means your reservation is ready to be collected. Rejected means your reservation was not accepted and you can see the reason. Pending means your request is waiting for approval.">
            <h3>Understanding Reservation Status</h3>
            <p><strong>Approved:</strong> Your reservation has been approved. You will see a reservation code to present at the pharmacy.</p>
            <p><strong>Rejected:</strong> Your reservation was not approved. A reason will be provided for the rejection.</p>
            <p><strong>Pending:</strong> Your reservation is waiting for admin approval.</p>
            <div class="modal-step-image-placeholder">
              <i class="bi bi-info-circle" style="font-size: 2rem; color: #D9EAFD; margin-right: 8px;"></i>
              <img src="screenshots/u1.png">
            </div>
            <button class="modal-tts-step-btn">
              <i class="bi bi-volume-up"></i> Read step
            </button>
          </div>

          <!-- Using the Page -->
          <div class="modal-step" data-step="How to use this page. The table shows your reservation ID, medicine name, quantity, total price, and status. Use the TTS feature to listen to details. Check your reservation status to know if you can collect your medicine. Look for the reservation code when approved to present at the pharmacy.">
            <h3>How to Use This Page</h3>
            <ul>
              <li>View your reservation table with all important details</li>
              <li>Use the Text-to-Speech buttons to hear your reservation information</li>
              <li>Check each reservation's status (Approved, Rejected, or Pending)</li>
              <li>When approved, copy or note the reservation code to present at the pharmacy</li>
              <li>Review rejection reasons if your reservation was not approved</li>
            </ul>
            <div class="modal-step-image-placeholder">
              <i class="bi bi-table" style="font-size: 2rem; color: #D9EAFD; margin-right: 8px;"></i>
              <img src="screenshots/u2.png">
              </div>
              <button class="modal-tts-step-btn">
                <i class="bi bi-volume-up"></i> Read step
              </button>
            </div>
        </div>

        <!-- ===== TEXT-TO-SPEECH TAB ===== -->
        <div id="tts-tab" class="modal-tab-content">
          <!-- TTS Controls -->
          <div class="modal-tts-bar">
            <i class="bi bi-volume-up-fill" style="color:#9AA6B2; font-size:15px;"></i>
            <span>Text to speech</span>
            <button class="modal-tts-btn" id="ttsTabReadGuide">
              <i class="bi bi-play-fill"></i> Read guide
            </button>
            <button class="modal-tts-stop-btn" id="ttsTabStop">
              <i class="bi bi-stop-fill"></i> Stop
            </button>
          </div>

          <h3 style="color: #7393A7; margin-top: 0;">Text-to-Speech (TTS) Feature</h3>
          
          <div class="multimodal-info">
            <h4>What is Text-to-Speech?</h4>
            <p>Text-to-Speech technology reads text content aloud to you, making it easier to understand your reservation details without having to read. This is especially helpful for customers with visual impairments or those who prefer listening to information.</p>
          </div>

          <div class="modal-step" data-step="How to Use TTS on This Page. Step 1: At the top of the guide modal, look for the Text-to-Speech bar with a speaker icon. Step 2: Click Read guide to hear the entire page guide from start to finish. Step 3: For individual sections, scroll to a step and click the Read step button. Step 4: To stop audio at any time, click the Stop button.">
            <h3>How to Use TTS on This Page</h3>
            <p>Follow these simple steps to use the Text-to-Speech feature:</p>
            <ul>
              <li><strong>Step 1:</strong> At the top of the guide modal, look for the Text-to-Speech bar with a speaker icon</li>
              <li><strong>Step 2:</strong> Click <strong>"Read guide"</strong> to hear the entire page guide from start to finish</li>
              <li><strong>Step 3:</strong> For individual sections, scroll to a step and click the <strong>"Read step"</strong> button</li>
              <li><strong>Step 4:</strong> To stop audio at any time, click the <strong>"Stop"</strong> button</li>
            </ul>
            <div class="modal-step-image-placeholder">
              <i class="bi bi-play-circle" style="font-size: 2rem; color: #D9EAFD; margin-right: 8px;"></i>
              <img src="screenshots/tu1.png">
            </div>
            <button class="modal-tts-step-btn">
              <i class="bi bi-volume-up"></i> Read step
            </button>
          </div>

          <div class="modal-step" data-step="Using TTS on the Main Page. The notifications page also has TTS controls in the main content area. Look for the speaker icon and Text to speech label above the table. Use Read all to hear all your reservations from top to bottom. Use Read buttons on individual rows to hear just that reservation's details. Click Stop to stop the audio playback at any time.">
            <h3>Using TTS on the Main Page</h3>
            <p>The notifications page also has TTS controls in the main content area:</p>
            <ul>
              <li>Look for the speaker icon and "Text to speech" label above the table</li>
              <li>Use <strong>"Read all"</strong> to hear all your reservations from top to bottom</li>
              <li>Use <strong>"Read"</strong> buttons on individual rows to hear just that reservation's details</li>
              <li>Click <strong>"Stop"</strong> to stop the audio playback at any time</li>
            </ul>
            <div class="modal-step-image-placeholder">
              <i class="bi bi-volume-up-fill" style="font-size: 2rem; color: #D9EAFD; margin-right: 8px;"></i>
              <img src="screenshots/tu2.png">
          </div>
          <button class="modal-tts-step-btn">
              <i class="bi bi-volume-up"></i> Read step
          </button>
          </div>

          <div class="modal-step" data-step="Understanding What TTS Reads. Reservation ID: Your unique reservation number. Medicine Name: The name of the medicine you reserved. Quantity: How many units you reserved. Total Price: The total cost of your reservation. Status: Current status, which can be Approved, Rejected, or Pending.">
            <h3>Understanding What TTS Reads</h3>
            <ul>
              <li><strong>Reservation ID:</strong> Your unique reservation number</li>
              <li><strong>Medicine Name:</strong> The name of the medicine you reserved</li>
              <li><strong>Quantity:</strong> How many units you reserved</li>
              <li><strong>Total Price:</strong> The total cost of your reservation</li>
              <li><strong>Status:</strong> Current status (Approved, Rejected, or Pending)</li>
            </ul>
            <div class="modal-step-image-placeholder">
              <i class="bi bi-chat-left-text" style="font-size: 2rem; color: #D9EAFD; margin-right: 8px;"></i>
              <img src="screenshots/tu3.png">
          </div>
          <button class="modal-tts-step-btn">
              <i class="bi bi-volume-up"></i> Read step
          </button>
          </div>

          <div class="modal-step" data-step="TTS Tips and Tricks. Adjust Your Browser Volume: Use your device's volume controls for comfortable listening. Browser Compatibility: Works best on Chrome, Firefox, Safari, and Edge browsers. Language Support: Currently reads in English Philippines accent for optimal experience. Multiple Readings: You can click stop and restart reading at any section.">
            <h3>TTS Tips & Tricks</h3>
            <ul>
              <li><strong>Adjust Your Browser Volume:</strong> Use your device's volume controls for comfortable listening</li>
              <li><strong>Use Keyboard Shortcuts:</strong> Most browsers support spacebar to play/pause in some contexts</li>
              <li><strong>Browser Compatibility:</strong> Works best on Chrome, Firefox, Safari, and Edge browsers</li>
              <li><strong>Language Support:</strong> Currently reads in English (Philippines) accent for optimal experience</li>
              <li><strong>Multiple Readings:</strong> You can click stop and restart reading at any section</li>
            </ul>
          <button class="modal-tts-step-btn">
              <i class="bi bi-volume-up"></i> Read step
          </button>
          </div>
        </div>

       </div>
    </div>
  </div>


  <script src="sidebar/sidebar.js"></script>
  <script>
    let currentZoom = 100;
    const minZoom = 80;
    const maxZoom = 150;
    const zoomStep = 10;

    const zoomInBtn = document.getElementById('zoomInBtn');
    const zoomOutBtn = document.getElementById('zoomOutBtn');
    const guideBtn = document.getElementById('guideBtn');
    const pageWrapper = document.querySelector('.page-wrapper');
    const guideModal = document.getElementById('guideModal');
    const closeBtn = document.getElementById('closeBtn');

    // ===== STATUS FILTER FUNCTION =====
    const statusFilter = document.getElementById('statusFilter');
    const tableRows = document.querySelectorAll('table tbody tr[data-status]');

    statusFilter.addEventListener('change', function() {
      const selectedStatus = this.value;
      tableRows.forEach(row => {
        if (selectedStatus === '') {
          // Show all rows
          row.style.display = '';
        } else {
          // Show only rows matching the selected status
          if (row.getAttribute('data-status') === selectedStatus) {
            row.style.display = '';
          } else {
            row.style.display = 'none';
          }
        }
      });
    });

    // ===== MODAL TTS FUNCTIONS (DEFINED FIRST) =====
    let currentModalStepBtn = null;

    window.speechSynthesis.onvoiceschanged = function() {
      window.speechSynthesis.getVoices();
    };

    function speakModal(text, onEnd) {
      window.speechSynthesis.cancel();
      const utter = new SpeechSynthesisUtterance(text);
      utter.lang = 'en-PH';
      utter.rate = 1;
      
      let voices = window.speechSynthesis.getVoices();
      const rosaVoice = voices.find(v => v.name === 'Microsoft Rosa Online (Natural) - English (Philippines)');
      if (rosaVoice) utter.voice = rosaVoice;
      
      if (onEnd) utter.onend = onEnd;
      window.speechSynthesis.speak(utter);
    }

    function stopModalTTS() {
      window.speechSynthesis.cancel();
      if (currentModalStepBtn) { 
        resetModalBtn(currentModalStepBtn); 
        currentModalStepBtn = null; 
      }
    }

    function setModalReading(btn) {
      if (currentModalStepBtn && currentModalStepBtn !== btn) resetModalBtn(currentModalStepBtn);
      currentModalStepBtn = btn;
      btn.classList.add('reading');
      btn.innerHTML = '<span class="tts-dot"></span> Reading...';
    }

    function resetModalBtn(btn) {
      btn.classList.remove('reading');
      btn.innerHTML = '<i class="bi bi-play-fill"></i> Read guide';
    }

    // ===== ZOOM FUNCTIONS =====
    function updateZoom() {
      const scale = currentZoom / 100;
      pageWrapper.style.transform = `scale(${scale})`;
      
      zoomInBtn.disabled = currentZoom >= maxZoom;
      zoomOutBtn.disabled = currentZoom <= minZoom;
    }

    zoomInBtn.addEventListener('click', function() {
      if (currentZoom < maxZoom) {
        currentZoom += zoomStep;
        updateZoom();
      }
    });

    zoomOutBtn.addEventListener('click', function() {
      if (currentZoom > minZoom) {
        currentZoom -= zoomStep;
        updateZoom();
      }
    });

    // ===== MODAL EVENT LISTENERS =====
    // Open modal
    guideBtn.addEventListener('click', function() {
      guideModal.classList.add('active');
    });

    // Close modal
    closeBtn.addEventListener('click', function() {
      stopModalTTS();
      guideModal.classList.remove('active');
    });

    // Close modal when clicking outside
    guideModal.addEventListener('click', function(e) {
      if (e.target === guideModal) {
        stopModalTTS();
        guideModal.classList.remove('active');
      }
    });

    // Close modal with ESC key
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && guideModal.classList.contains('active')) {
        stopModalTTS();
        guideModal.classList.remove('active');
      }
    });

    // Keyboard shortcuts (Ctrl/Cmd + Plus/Minus)
    document.addEventListener('keydown', function(e) {
      if (e.ctrlKey || e.metaKey) {
        if (e.key === '+' || e.key === '=') {
          e.preventDefault();
          zoomInBtn.click();
        } else if (e.key === '-') {
          e.preventDefault();
          zoomOutBtn.click();
        } else if (e.key === '0') {
          e.preventDefault();
          currentZoom = 100;
          updateZoom();
        }
      }
    });

    updateZoom();

    // ===== MODAL TTS EVENT LISTENERS =====
    // Per-step Read buttons in modal
    document.querySelectorAll('.modal-tts-step-btn').forEach(btn => {
      btn.addEventListener('click', function () {
        const stepDiv = this.closest('.modal-step');
        const text = stepDiv ? stepDiv.getAttribute('data-step') : '';
        if (this.classList.contains('reading')) { stopModalTTS(); return; }
        setModalReading(this);
        speakModal(text, () => { resetModalBtn(this); currentModalStepBtn = null; });
      });
    });

    // Read entire guide in modal
    document.getElementById('modalTtsReadGuide').addEventListener('click', function () {
      const steps = document.querySelectorAll('.modal-step');
      if (steps.length === 0) return;
      let index = 0;

      function readNext() {
        if (index >= steps.length) {
          if (currentModalStepBtn) { resetModalBtn(currentModalStepBtn); currentModalStepBtn = null; }
          return;
        }
        const step = steps[index];
        const btn = step.querySelector('.modal-tts-step-btn');
        const text = step.getAttribute('data-step');
        if (btn) setModalReading(btn);
        index++;
        speakModal(text, readNext);
      }

      readNext();
    });

    // Stop modal TTS
    document.getElementById('modalTtsStop').addEventListener('click', stopModalTTS);

    // ===== TAB SWITCHING FUNCTIONALITY =====
    document.querySelectorAll('.modal-tab-btn').forEach(btn => {
      btn.addEventListener('click', function() {
        const tabName = this.getAttribute('data-tab');
        
        // Remove active class from all buttons and contents
        document.querySelectorAll('.modal-tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.modal-tab-content').forEach(c => c.classList.remove('active'));
        
        // Add active class to clicked button and corresponding content
        this.classList.add('active');
        const tabContent = document.getElementById(tabName);
        if (tabContent) {
          tabContent.classList.add('active');
          
          // Reset scroll to top of new tab
          const modalInner = document.getElementById('modalInner');
          if (modalInner) {
            modalInner.scrollTop = 0;
          }
          
          // Update TTS to read only active tab
          setupTabTTS(tabName);
        }
      });
    });

    // Tab-specific TTS setup
    function setupTabTTS(tabName) {
      // Get new TTS buttons
      const readBtn = document.getElementById('modalTtsReadGuide');
      const stopBtn = document.getElementById('modalTtsStop');
      
      if (!readBtn || !stopBtn) return;
      
      // Clone to remove old listeners
      readBtn.replaceWith(readBtn.cloneNode(true));
      stopBtn.replaceWith(stopBtn.cloneNode(true));
      
      // Re-add listeners
      document.getElementById('modalTtsReadGuide').addEventListener('click', function() {
        const steps = document.querySelectorAll('.modal-tab-content.active .modal-step');
        if (steps.length === 0) return;
        let index = 0;

        function readNext() {
          if (index >= steps.length) {
            if (currentModalStepBtn) { resetModalBtn(currentModalStepBtn); currentModalStepBtn = null; }
            return;
          }
          const step = steps[index];
          const btn = step.querySelector('.modal-tts-step-btn');
          const text = step.getAttribute('data-step');
          if (btn) setModalReading(btn);
          index++;
          speakModal(text, readNext);
        }

        readNext();
      });

      document.getElementById('modalTtsStop').addEventListener('click', stopModalTTS);
    }

    // TTS tab "Read guide" and "Stop" buttons
    document.getElementById('ttsTabReadGuide').addEventListener('click', function () {
      const steps = document.querySelectorAll('#tts-tab .modal-step');
      if (steps.length === 0) return;
      let index = 0;
      function readNext() {
        if (index >= steps.length) {
          if (currentModalStepBtn) { resetModalBtn(currentModalStepBtn); currentModalStepBtn = null; }
          return;
        }
        const step = steps[index];
        const btn = step.querySelector('.modal-tts-step-btn');
        const text = step.getAttribute('data-step');
        if (btn) setModalReading(btn);
        index++;
        speakModal(text, readNext);
      }
      readNext();
    });

    document.getElementById('ttsTabStop').addEventListener('click', stopModalTTS);

    // Regular page TTS (existing functionality)
    let currentRowBtn = null;
    
    function speak(text, onEnd) {
      window.speechSynthesis.cancel();
      const utter = new SpeechSynthesisUtterance(text);
      utter.lang = 'en-PH';
      utter.rate = 1;
      
      let voices = window.speechSynthesis.getVoices();
      const rosaVoice = voices.find(v => v.name === 'Microsoft Rosa Online (Natural) - English (Philippines)');
      if (rosaVoice) utter.voice = rosaVoice;
      
      if (onEnd) utter.onend = onEnd;
      window.speechSynthesis.speak(utter);
    }

    function stopTTS() {
      window.speechSynthesis.cancel();
      if (currentRowBtn) { resetBtn(currentRowBtn); currentRowBtn = null; }
    }

    function setReading(btn) {
      if (currentRowBtn && currentRowBtn !== btn) resetBtn(currentRowBtn);
      currentRowBtn = btn;
      btn.classList.add('reading');
      btn.innerHTML = '<span class="tts-dot"></span> Reading...';
    }

    function resetBtn(btn) {
      btn.classList.remove('reading');
      btn.innerHTML = '<i class="bi bi-volume-up"></i> Read';
    }

    // Per-row Read buttons
    document.querySelectorAll('.tts-row-btn').forEach(btn => {
      btn.addEventListener('click', function () {
        const text = this.getAttribute('data-tts');
        if (this.classList.contains('reading')) { stopTTS(); return; }
        setReading(this);
        speak(text, () => { resetBtn(this); currentRowBtn = null; });
      });
    });

    // Read all
    document.getElementById('ttsReadAll').addEventListener('click', function () {
      const rows = document.querySelectorAll('tr[data-tts]');
      if (rows.length === 0) return;
      let index = 0;

      function readNext() {
        if (index >= rows.length) {
          if (currentRowBtn) { resetBtn(currentRowBtn); currentRowBtn = null; }
          return;
        }
        const row = rows[index];
        const btn = row.querySelector('.tts-row-btn');
        const text = row.getAttribute('data-tts');
        if (btn) setReading(btn);
        index++;
        speak(text, readNext);
      }

      readNext();
    });

    // Stop
    document.getElementById('ttsStop').addEventListener('click', stopTTS);
  </script>
</body>
</html>
<?php
$stmt->close();
$conn->close();
?>