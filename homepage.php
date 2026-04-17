<?php
include 'config/connection.php';
session_start();

if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION['role'] === 'admin') {
    header("Location: dashboard.php");
    exit();
}

// Get user's ID verification status
$user_id = $_SESSION['id'];
$id_status_query = "SELECT id_verification_status, id_rejected_reason FROM users WHERE user_id = ?";
$stmt = $conn->prepare($id_status_query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

$id_status = $user_data['id_verification_status'] ?? null;
$id_rejected_reason = $user_data['id_rejected_reason'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Home | PharmAssist</title>

  <!-- Styles -->
  <link rel="stylesheet" href="plugins/sidebar.css?v=3">
  <link rel="stylesheet" href="plugins/homepage.css?v=3">
  <link rel="stylesheet" href="plugins/branches.css?v=3">
  <link rel="stylesheet" href="plugins/footer.css?v=3">
  <link rel="stylesheet" href="plugins/carousel.css?v=3">
  <link rel="icon" href="website_icon/favicon.png" type="image/png" sizes="64x64">

  <!-- Icons + Bootstrap -->
  <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,200..800&family=Tinos:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
<style>
  .home-section .home-content {
    overflow: visible !important;
  }

  .home-section .home-content .search-container {
    position: relative;
    width: 280px;
    flex-shrink: 0;
  }

  .home-section .home-content .search-container input {
    border-radius: 30px;
    padding: 8px 42px 8px 15px;
    background-color: #E8ECF1;
    border: 1px solid #7393A7;
  }

  .home-section .home-content .search-container button {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #7393A7;
    font-size: 1.2rem;
    cursor: pointer;
  }

  /* Page Wrapper for Zoom */
  .page-wrapper {
  transform-origin: top center;
  transition: transform 0.2s ease-out;
  position: relative;
  z-index: 1;
}

.modal {
  z-index: 2000 !important;
}

.modal-backdrop {
  z-index: 1990 !important;
}

  /* ===== ID VERIFICATION STATUS ALERTS ===== */
  .id-status-alert {
    position: relative;
    z-index: 10;
    margin: 0;
    padding: 15px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    font-family: 'Tinos', serif;
    animation: slideDown 0.3s ease-out;
  }

  @keyframes slideDown {
    from {
      opacity: 0;
      transform: translateY(-10px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  .id-status-alert.rejected {
    background-color: #f8d7da;
    border-left: 4px solid #dc3545;
    color: #721c24;
  }

  .id-status-alert.pending {
    background-color: #fff3cd;
    border-left: 4px solid #ffc107;
    color: #856404;
  }

  .id-status-alert.approved {
    background-color: #d4edda;
    border-left: 4px solid #28a745;
    color: #155724;
  }

  .id-status-content {
    flex: 1;
  }

  .id-status-content strong {
    font-size: 14px;
    font-weight: 700;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .id-status-content strong i {
    font-size: 16px;
  }

  .id-status-content p {
    margin: 5px 0 0 0;
    font-size: 13px;
    line-height: 1.4;
  }

  .id-status-reason {
    margin-top: 8px;
    padding: 8px;
    border-radius: 3px;
    font-size: 12px;
    background-color: rgba(0, 0, 0, 0.08);
  }

  .id-status-action {
    flex-shrink: 0;
  }

  .btn-resubmit {
    background-color: #dc3545;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    text-decoration: none;
    font-weight: 600;
    font-family: 'Bricolage Grotesque', sans-serif;
    transition: all 0.3s;
    white-space: nowrap;
    cursor: pointer;
    font-size: 13px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }

  .btn-resubmit:hover {
    background-color: #c82333;
    color: white;
    text-decoration: none;
    box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3);
  }

  .btn-resubmit i {
    font-size: 13px;
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
    z-index: 3000;
  }

  .modal-content {
  pointer-events: auto;
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

  @media (max-width: 768px) {
    .id-status-alert {
      flex-direction: column;
      align-items: flex-start;
    }

    .id-status-action {
      width: 100%;
      margin-top: 10px;
    }

    .btn-resubmit {
      width: 100%;
      justify-content: center;
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
  .modal-dialog {
  max-width: 600px; /* optional for better layout */
}

.modal-content {
  max-height: 80vh;
  display: flex;
  flex-direction: column;
}

.modal-body {
  overflow-y: auto;
}

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

  .modal-step-image {
    margin: 20px 0;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  }

  .modal-step-image img {
    width: 100%;
    height: auto;
    display: block;
    border-radius: 8px;
  }

  .modal-step-image-placeholder {
    width: 100%;
    min-height: 200px;
    background: #F4F8FA;
    border: 2px dashed #D9EAFD;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #9AA6B2;
    font-size: 13px;
    text-align: center;
    padding: 20px;
    font-family: "Bricolage Grotesque", sans-serif;
    border-radius: 8px;
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


  </style>
</head>
<body>

  <!-- Sidebar -->
  <?php include 'sidebar.php'; ?>

  <!-- Main Content -->
  <section class="home-section">
      <div class="home-content d-flex align-items-center justify-content-between px-4">
  <div class="d-flex align-items-center gap-2">
    <i class='bx bx-menu' style="font-size: 1.8rem;"></i>
    <span><a href ="homepage.php" style="text-decoration: none; color: white; font-size: 1.5rem;" class="text fw-semibold">PharmAssist</span></a>
  </div>

  <div class="search-container">
    <input type="text" class="form-control" placeholder="Search medicines..." aria-label="Search">
    <button type="submit"><i class="bi bi-search"></i></button>
  </div>
</div>
  <!--Search Bar Functionality-->
  <script>
  // Homepage search redirect to medicines page
const searchContainer = document.querySelector('.search-container');
const searchInput = searchContainer.querySelector('input');
const searchButton = searchContainer.querySelector('button');

function performSearch() {
  const query = searchInput.value.trim();
  if (query) {
    // Redirect to medicines page with search query
    window.location.href = 'medicines.php?search=' + encodeURIComponent(query);
  }
}

searchButton.addEventListener('click', performSearch);
searchInput.addEventListener('keypress', function (e) {
  if (e.key === 'Enter') {
    e.preventDefault();
    performSearch();
  }
});
</script>
  <div class="page-wrapper">
      <!-- ===== ID VERIFICATION STATUS BANNER ===== -->
      <?php if ($id_status === 'rejected'): ?>
        <div class="id-status-alert rejected">
          <div class="id-status-content">
            <strong>
              <i class="fas fa-exclamation-circle"></i>
              ID Verification Rejected
            </strong>
            <p>Your ID submission was rejected and needs to be resubmitted for verification before you can reserve medicines.</p>
            <?php if ($id_rejected_reason): ?>
              <div class="id-status-reason">
                <strong style="display: block; margin-bottom: 5px;">Reason:</strong>
                <?php echo htmlspecialchars($id_rejected_reason); ?>
              </div>
            <?php endif; ?>
          </div>
          <div class="id-status-action">
            <a href="resubmit_id.php" class="btn-resubmit">
              <i class="fas fa-id-card"></i> Resubmit ID
            </a>
          </div>
        </div>
      <?php elseif ($id_status === 'pending'): ?>
        <div class="id-status-alert pending">
          <div class="id-status-content">
            <strong>
              <i class="fas fa-hourglass-half"></i>
              ID Verification Pending
            </strong>
            <p>Your ID is under review. You can browse medicines but cannot reserve them until verification is complete.</p>
          </div>
        </div>
      <?php endif; ?>

      <div class="container-fluid p-0 w-100">

    <div class="page-wrapper-inner">
      
      <div class="container-fluid p-0 w-100">
        <div class="parallax d-flex flex-lg-row flex-column justify-content-center align-items-center px-5 py-5 gap-5" style="background-color: #E8ECF1;">
          
          <div class="pe-lg-1 text-center text-lg-start">
            <h1 class="m-0 text-uppercase display-3 fw-bold" style="color: #7393A7;">
              Reserve it now. Pick it up later.
            </h1>
            <p class="fs-5" style="color: #6C737E;">
              Browse available medicines from any branch, reserve them online, and simply show your ticket when you arrive — no more waiting in line.
            </p>
            <div class="button d-flex flex-column flex-sm-row justify-content-center justify-content-lg-start align-items-center gap-2 mt-3">
              <a class="btn btn-lg w-100 w-sm-auto" data-bs-toggle="modal" data-bs-target="#aboutModal" style="background-color: #B5CFD8; color: #FFFFFF;">About Us</a>
              <a class="btn btn-lg w-100 w-sm-auto" data-bs-toggle="modal" data-bs-target="#contactModal" style="background-color: #7393A7; color: #FFFFFF;">Contact Us</a>
            </div>
          </div>

          <img class="img-fluid rounded front-image" src="img/front1.png" alt="PharmAssist" style="box-shadow: 0 8px 16px 0 rgba(0,0,0,0.3);"> 
        </div>
      </div>

<!-- Branches Section -->
<section class="branches-section" style="background-color: #E8ECF1 !important">
  <div class="container">
    <div class="section-header">
      <h2>Our Branches</h2>
      <p>Visit any of our conveniently located branches. Reserve online and pick up at your preferred location.</p>
    </div>

    <div class="row g-3">
      <?php
      // Fetch all branches from database
      $branches_query = $conn->query("SELECT * FROM branches ORDER BY branch_id ASC");
      
      if ($branches_query->num_rows > 0):
        $branch_index = 0;
        
        // Define specific info for each branch including unique map URLs
        $branch_info = [
          0 => [
            'phone' => '(049) 123-4567', 
            'hours' => 'Mon-Sun: 8:00 AM - 9:00 PM',
            'map' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3860.302472511541!2d120.98616917388061!3d14.638763876143631!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3397b678240d4e67%3A0xe13c0d0ec529d1a0!2sRo-Eful%20Pharmacy!5e0!3m2!1sen!2sph!4v1776362602872!5m2!1sen!2sph'
          ],
          1 => [
            'phone' => '(049) 234-5678', 
            'hours' => 'Mon-Sun: 8:00 AM - 9:00 PM',
            'map' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3858.833513197526!2d121.03772657388241!3d14.722002874095354!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3397b0f2d65b2077%3A0xd84e6f917516ae48!2sTGP%20The%20Generics%20Pharmacy%20(Novaliches%20Proper)!5e0!3m2!1sen!2sph!4v1776362947778!5m2!1sen!2sph'
          ],
          2 => [
            'phone' => '(049) 345-6789', 
            'hours' => 'Mon-Sun: 8:00 AM - 9:00 PM',
            'map' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15444.039029302437!2d120.96749732998464!3d14.598519825136101!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3397ca1d3e597eff%3A0x6c6652573d60b773!2sQuiapo%2C%20Manila%2C%201001%20Metro%20Manila!5e0!3m2!1sen!2sph!4v1761583975558!5m2!1sen!2sph'
          ],
          3 => [
            'phone' => '(049) 345-6789', 
            'hours' => 'Mon-Sun: 8:00 AM - 9:00 PM',
            'map' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15439.166684769903!2d120.922789880134!3d14.667760972003059!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3397b457d84f43fd%3A0x551612f98b99dd5a!2sDaanghari%2C%20Navotas%2C%20Metro%20Manila!5e0!3m2!1sen!2sph!4v1761584124313!5m2!1sen!2sph'
          ]
        ];
        
        while($branch = $branches_query->fetch_assoc()):
          $is_main = ($branch_index === 0); // First branch is main
          $badge_text = $is_main ? 'Main Branch' : 'Branch';
          
          $phone = isset($branch_info[$branch_index]) ? $branch_info[$branch_index]['phone'] : '(049) 000-0000';
          $hours = isset($branch_info[$branch_index]) ? $branch_info[$branch_index]['hours'] : 'Mon-Sat: 8:00 AM - 8:00 PM<br>Sun: 9:00 AM - 6:00 PM';
          $map_url = isset($branch_info[$branch_index]) ? $branch_info[$branch_index]['map'] : 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d30894.25135322409!2d121.00381880626182!3d14.554488599265037!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3397c90264a0ed01%3A0x2b066ed57830cace!2sMakati%20City%2C%20Metro%20Manila!5e0!3m2!1sen!2sph!4v1761583120613!5m2!1sen!2sph';
          
          $count_stmt = $conn->prepare("SELECT COUNT(*) as count FROM medicine WHERE branch = ?");
          $count_stmt->bind_param("s", $branch['branch_name']);
          $count_stmt->execute();
          $medicine_count = $count_stmt->get_result()->fetch_assoc()['count'];
          $count_stmt->close();
      ?>
        <div class="col-12">
          <div class="branch-card">
            <div class="map-container">
              <iframe 
                src="<?php echo $map_url; ?>" 
                width="600" 
                height="450" 
                style="border:0;" 
                allowfullscreen="" 
                loading="lazy" 
                referrerpolicy="no-referrer-when-downgrade">
              </iframe>
            </div>
            <div class="branch-content">
              <span class="branch-badge"><?php echo $badge_text; ?></span>
              <h3 class="branch-name"> <?php echo htmlspecialchars($branch['branch_name']); ?></h3>
              <div class="branch-info">
                <div class="info-item">
                  <i class="bi bi-geo-alt-fill"></i>
                  <span><?php echo htmlspecialchars($branch['branch_address']); ?></span>
                </div>
                <div class="info-item">
                  <i class="bi bi-clock-fill"></i>
                  <span><?php echo $hours; ?></span>
                </div>
                <div class="info-item">
                  <i class="bi bi-telephone-fill"></i>
                  <span><?php echo $phone; ?></span>
                </div>
              </div>
              <div class="branch-actions">
                <a href="medicines.php?branch=<?php echo urlencode($branch['branch_name']); ?>">
                  <button class="btn btn-view">View Medicines (<?php echo $medicine_count; ?>)</button>
                </a>
                <button class="btn btn-directions" onclick="window.open('<?php echo $map_url; ?>', '_blank')">Get Directions</button>
              </div>
            </div>
          </div>
        </div>
      <?php
          $branch_index++;
        endwhile;
      else:
      ?>
        <div class="col-12">
          <p class="text-center text-muted">No branches available yet.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

      <!--Carousel-->
      <div class="carousel-container">
        <div class="carousel-wrapper" id="carouselWrapper">
          <!-- Slide 1 -->
          <div class="carousel-slide">
            <div class="card" data-url="https://www.medscape.com/viewarticle/obesity-treatment-study-backs-dual-drug-strategy-2025a1000pu3?form=fpf" data-target="_blank">
              <img src="https://images.ctfassets.net/yixw23k2v6vo/6UcVlTNvtUSvHXU1x6HPQZ/7795e0da749cf7b4e2cc2a140c33e159/iStock-513070138.jpg" alt="Obesity Treatment" class="card-image">
              <div class="card-overlay">
                <div class="card-category">OBESITY • MEDICAL RESEARCH</div>
                <div class="card-title">Obesity Treatment: Study Backs Dual-Drug Strategy</div>
                <div class="card-description">Study on dual-drug strategy for obesity treatment effectiveness.</div>
              </div>
            </div>

            <div class="card" data-url="https://onehealthtrust.org/publications/peer-reviewed-articles/the-lancet-series-on-antimicrobial-resistance-the-need-for-sustainable-access-to-effective-antibiotics/?gad_source=1&gad_campaignid=21377001781&gclid=CjwKCAjwuePGBhBZEiwAIGCVSx-ulkjSStFRfCZ_j9AkuPvlKuUcPI81Xbn_IurMVO037JXhD6htgBoCHWMQAvD_BwE" data-target="_blank">
              <img src="https://medshadow.org/wp-content/uploads/2019/04/antibiotics-949x626.jpeg" alt="Antibiotics" class="card-image">
              <div class="card-overlay">
                <div class="card-category">PUBLIC HEALTH • ANTIMICROBIAL RESISTANCE • GLOBAL HEALTH</div>
                <div class="card-title">The Lancet Series on Antimicrobial Resistance: The need for sustainable access to effective antibiotics</div>
                <div class="card-description">Research on sustainable antibiotic access amid resistance challenges</div>
              </div>
            </div>

            <div class="card" data-url="https://shs.touro.edu/news/stories/25-fascinating-facts-about-the-health-sciences.php" data-target="_blank">
              <img src="https://shs.touro.edu/media/schools-and-colleges/shs/images/stories/2020/SHS_July_Blog_3_InterestingFacts.jpg" alt="Health Sciences" class="card-image">
              <div class="card-overlay">
                <div class="card-category">HEALTH SCIENCES • EDUCATION • MEDICAL FACTS</div>
                <div class="card-title">Fascinating Facts About The Health Sciences</div>
                <div class="card-description">Educational content with fascinating health science facts.</div>
              </div>
            </div>
          </div>

          <!-- Slide 2 -->
          <div class="carousel-slide">
            <div class="card" data-url="https://www.abs-cbn.com/news/health-science/2025/9/26/is-the-birth-control-pill-destroying-women-s-sex-drive-1612" data-target="_blank">
              <img src="https://od2-image-api.abs-cbn.com/prod/editorImage/1758874179237reproductive-health-supplies-coalition-fgqLiOKNjU8-unsplash.jpg" alt="Birth pill" class="card-image">
              <div class="card-overlay">
                <div class="card-category">WOMEN'S HEALTH • REPRODUCTIVE HEALTH • HORMONES</div>
                <div class="card-title">Is the birth control pill destroying women's sex drive?</div>
                <div class="card-description">Investigation of birth control pills' impact on women's libido.</div>
              </div>
            </div>

            <div class="card" data-url="https://www.bbc.com/news/articles/czx0dypkpvko" data-target="_blank">
              <img src="https://ichef.bbci.co.uk/news/800/cpsprodpb/68ad/live/1cfab9a0-8a6c-11f0-8ba8-9d1af6a803b3.jpg.webp" alt="Prescriptions" class="card-image">
              <div class="card-overlay">
                <div class="card-category">MEDICAL NEWS • HEALTH RESEARCH • CURRENT EVENTS</div>
                <div class="card-title">Cost of prescriptions rises year-on-year</div>
                <div class="card-description">Recent health-related news coverage and developments.</div>
              </div>
            </div>

            <div class="card" data-url="https://www.hopkinsmedicine.org/health/wellness-and-prevention/is-there-really-any-benefit-to-multivitamins" data-target="_blank">
              <img src="https://media.istockphoto.com/id/1414489487/photo/supplements-and-vitamins-on-a-white-background-selective-focus.jpg?s=612x612&w=0&k=20&c=FgfT2r6_yRH8Rlx5R5MGu-rX3fgAMefkl2QSkd_JkSk=" alt="Multivitamins" class="card-image">
              <div class="card-overlay">
                <div class="card-category">NUTRITION • SUPPLEMENTS • PREVENTIVE HEALTH</div>
                <div class="card-title">Is There Really Any Benefit to Multivitamins?</div>
                <div class="card-description">Evidence-based analysis of multivitamin health benefits</div>
              </div>
            </div>
          </div>

          <!-- Slide 3 -->
          <div class="carousel-slide">
            <div class="card" data-url="https://www.heart.org/en/health-topics/cardiac-rehab/managing-your-medicines/taking-control-of-your-medicines" data-target="_blank">
              <img src="https://www.heart.org/-/media/Images/Health-Topics/High-Blood-Pressure/man-holding-pills-and-glass-of-water.jpg?h=667&w=1000&sc_lang=en" alt="Medications" class="card-image">
              <div class="card-overlay">
                <div class="card-category">MEDICATION MANAGEMENT • HEALTHCARE EDUCATION</div>
                <div class="card-title">Taking Control of Your Medications</div>
                <div class="card-description">Guide to safely organizing and tracking prescription medications.</div>
              </div>
            </div>

            <div class="card" data-url="https://www.news-medical.net/news/20241008/Can-multivitamins-improve-mood-and-reduce-stress-in-older-adults.aspx" data-target="_blank">
              <img src="https://www.news-medical.net/image-handler/ts/20241008072446/ri/750/src/images/news/ImageForNews_792479_17283866854238540.jpg" alt="Multivitamins" class="card-image">
              <div class="card-overlay">
                <div class="card-category">AGING • MENTAL HEALTH • NUTRITIONAL SUPPLEMENTS</div>
                <div class="card-title">Can multivitamins improve mood and reduce stress in older adults?</div>
                <div class="card-description">Study on multivitamins improving mood in older adults.</div>
              </div>
            </div>

            <div class="card" data-url="https://www.todaysparent.com/toddler/taking-medicine" data-target="_blank">
              <img src="https://burtsrx.com/wp-content/uploads/2021/01/Getting-Your-Child-to-Take-Medication.jpg.webp" alt="Child takes medicine" class="card-image">
              <div class="card-overlay">
                <div class="card-category">PEDIATRIC HEALTH • PARENTING • MEDICATION SAFETY</div>
                <div class="card-title">How to get your child to take medicine</div>
                <div class="card-description">Parent resource for safely giving medicine to toddlers</div>
              </div>
            </div>
          </div>
        </div>

        <button class="carousel-nav prev" id="prevBtn">‹</button>
        <button class="carousel-nav next" id="nextBtn">›</button>

        <div class="carousel-indicators" id="indicators">
          <div class="indicator active" data-slide="0"></div>
          <div class="indicator" data-slide="1"></div>
          <div class="indicator" data-slide="2"></div>
        </div>
      </div>
    </div>
  </section>

  <?php
if (isset($_GET['search']) && !empty($_GET['search'])) {
    include 'config/connection.php';
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $query = "SELECT * FROM medicine WHERE medicine_name LIKE '%$search%'";
    $result = mysqli_query($conn, $query);
?>
<section class="search-results py-5" style="background-color:#E8ECF1;">
  <div class="container">
    <h2 class="mb-4 text-center">
      Search results for "<span style="color:#7393A7;"><?php echo htmlspecialchars($search); ?></span>"
    </h2>
    <div class="row justify-content-center g-4">
      <?php
      if (mysqli_num_rows($result) > 0) {
          while ($row = mysqli_fetch_assoc($result)) {
              echo '
              <div class="col-md-4 col-lg-3">
                <div class="card shadow-sm h-100">
                  <img src="uploads/'.htmlspecialchars($row['image']).'" class="card-img-top" alt="'.htmlspecialchars($row['medicine_name']).'">
                  <div class="card-body text-center">
                    <h5 class="card-title">'.htmlspecialchars($row['medicine_name']).'</h5>
                    <p class="text-muted">'.htmlspecialchars($row['category']).'</p>
                    <p class="fw-bold" style="color:#7393A7;">₱'.number_format($row['price'], 2).'</p>
                    <button class="btn btn-sm" style="background-color:#7393A7; color:white;">Reserve</button>
                  </div>
                </div>
              </div>';
          }
      } else {
          echo '<p class="text-muted text-center">No medicines found matching your search.</p>';
      }
      ?>
    </div>
  </div>
</section>
<?php } ?>

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
        <h2>Homepage Guide</h2>
        <button class="modal-close" id="closeBtn">&times;</button>
      </div>

      <!-- Tab Buttons -->
      <div style="background: white; padding: 0 30px; border-bottom: 1px solid #e8ecf1;">
        <div class="modal-tabs">
          <button class="modal-tab-btn active" data-tab="guide-tab">Page Guide</button>
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
          <div class="modal-step" data-step="Homepage overview. The homepage is the main page of PharmAssist where you can explore medicines and branches. It displays information about the platform, shows all pharmacy branches with their locations, and features a carousel of health-related articles and resources.">
            <h3>Overview</h3>
            <p>The Homepage is your main entry point to PharmAssist. Here you can explore all available branches, browse medicines, learn about our platform, and access health-related information through our carousel of featured articles.</p>
            <button class="modal-tts-step-btn">
              <i class="bi bi-volume-up"></i> Read step
            </button>
          </div>

          <!-- Branches Section -->
          <div class="modal-step" data-step="Our Branches section. This section displays all PharmAssist pharmacy branches. Each branch shows its location on a map, address, phone number, operating hours, and the number of medicines available. You can click View Medicines to browse items from that branch or Get Directions to see how to reach the branch.">
            <h3>Exploring Our Branches</h3>
            <p>The "Our Branches" section shows all available PharmAssist locations. For each branch, you can see:</p>
            <ul>
              <li><strong>Location Map:</strong> An interactive map showing the branch location</li>
              <li><strong>Address:</strong> The physical location of the branch</li>
              <li><strong>Operating Hours:</strong> When the branch is open</li>
              <li><strong>Phone Number:</strong> Contact information</li>
              <li><strong>Medicine Count:</strong> How many medicines are available at that branch</li>
              <li><strong>View Medicines Button:</strong> Click to browse medicines at that branch</li>
              <li><strong>Get Directions Button:</strong> Opens a map to help you navigate to the branch</li>
            </ul>
            <div class="modal-step-image-placeholder"><img src="screenshots/g1.png"></div>
            <button class="modal-tts-step-btn">
              <i class="bi bi-volume-up"></i> Read step
            </button>
          </div>

          <!-- Carousel Section -->
          <div class="modal-step" data-step="Carousel section. The carousel displays similar health and medical articles from trusted sources. You can click through the slides using the arrow buttons or click on an article to visit the full article on the source website. The carousel automatically rotates through articles.">
            <h3>Featured Articles Carousel</h3>
            <p>Below the branches section, you'll find a carousel of featured health-related articles and resources from trusted medical websites. Features include:</p>
            <ul>
              <li><strong>Browse Articles:</strong> Use the left and right arrow buttons to navigate through articles</li>
              <li><strong>Indicator Dots:</strong> Click on the dots at the bottom to jump to a specific slide</li>
              <li><strong>Auto-Play:</strong> The carousel automatically rotates through articles every 5 seconds</li>
              <li><strong>Visit Article:</strong> Click on any article card to visit the full article on the original source</li>
              <li><strong>Article Info:</strong> Each card shows category, title, and description of the article</li>
            </ul>
            <div class="modal-step-image-placeholder"><img src="screenshots/g2.png"></div>
            <button class="modal-tts-step-btn">
              <i class="bi bi-volume-up"></i> Read step
            </button>
          </div>

          <!-- Search Feature -->
          <div class="modal-step" data-step="Search feature. At the top of the page, there is a search bar where you can type the name of a medicine. Enter the medicine name and press Enter or click the search button to find that medicine across all branches.">
            <h3>Using the Search Feature</h3>
            <ul>
              <li>Click on the search box at the top of the page</li>
              <li>Type the name of the medicine you're looking for</li>
              <li>Press Enter or click the search icon button</li>
              <li>You'll be taken to the medicines page with search results</li>
            </ul>
            <div class="modal-step-image-placeholder"><img src="screenshots/g3.png"></div>
            <button class="modal-tts-step-btn">
              <i class="bi bi-volume-up"></i> Read step
            </button>
          </div>

          <!-- About & Contact -->
          <div class="modal-step" data-step="About and Contact buttons. In the hero section at the top, you can find About Us and Contact Us buttons. Click About Us to learn more about PharmAssist mission and services. Click Contact Us to see our contact information including email, phone, and social media.">
            <h3>About Us & Contact Information</h3>
            <ul>
              <li><strong>About Us Button:</strong> Learn about PharmAssist's mission, vision, and the services we provide</li>
              <li><strong>Contact Us Button:</strong> View our contact details including email, phone number, and social media</li>
            </ul>
            <div class="modal-step-image-placeholder"><img src="screenshots/g4.png"></div>
            <button class="modal-tts-step-btn">
              <i class="bi bi-volume-up"></i> Read step
            </button>
          </div>
        </div>

       </div>
    </div>
  </div>


  <?php include 'footer.php'; ?>

  <script src="source/sidebar.js"></script>
  <script src="source/homepage.js"></script>

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
      guideModal.classList.remove('active');
    });

    // Close modal when clicking outside
    guideModal.addEventListener('click', function(e) {
      if (e.target === guideModal) {
        guideModal.classList.remove('active');
      }
    });

    // Close modal with ESC key
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && guideModal.classList.contains('active')) {
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

    // ===== MODAL TTS FUNCTIONS =====
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

    // Carousel functionality
    let currentSlide = 0;
    const totalSlides = 3;
    const carouselWrapper = document.getElementById('carouselWrapper');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const indicators = document.querySelectorAll('.indicator');

    function updateCarousel() {
      const translateX = -currentSlide * 100;
      carouselWrapper.style.transform = `translateX(${translateX}%)`;
      
      indicators.forEach((indicator, index) => {
        indicator.classList.toggle('active', index === currentSlide);
      });
    }

    function nextSlide() {
      currentSlide = (currentSlide + 1) % totalSlides;
      updateCarousel();
    }

    function prevSlide() {
      currentSlide = (currentSlide - 1 + totalSlides) % totalSlides;
      updateCarousel();
    }

    function goToSlide(slideIndex) {
      currentSlide = slideIndex;
      updateCarousel();
    }

    nextBtn.addEventListener('click', nextSlide);
    prevBtn.addEventListener('click', prevSlide);

    indicators.forEach((indicator, index) => {
      indicator.addEventListener('click', () => goToSlide(index));
    });

    let autoPlayInterval = setInterval(nextSlide, 5000);

    const carouselContainer = document.querySelector('.carousel-container');
    carouselContainer.addEventListener('mouseenter', () => {
      clearInterval(autoPlayInterval);
    });

    carouselContainer.addEventListener('mouseleave', () => {
      autoPlayInterval = setInterval(nextSlide, 5000);
    });

    let startX = 0;
    let endX = 0;

    carouselContainer.addEventListener('touchstart', (e) => {
      startX = e.touches[0].clientX;
    });

    carouselContainer.addEventListener('touchend', (e) => {
      endX = e.changedTouches[0].clientX;
      const diff = startX - endX;
      
      if (Math.abs(diff) > 50) {
        if (diff > 0) {
          nextSlide();
        } else {
          prevSlide();
        }
      }
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'ArrowLeft') {
        prevSlide();
      } else if (e.key === 'ArrowRight') {
        nextSlide();
      }
    });

    document.querySelectorAll('.card').forEach(card => {
      card.addEventListener('click', function() {
        const url = this.dataset.url;
        const target = this.dataset.target;
        
        if (url) {
          if (target === '_blank') {
            window.open(url, '_blank');
          } else {
            window.location.href = url;
          }
        }
      });
    });
  </script>

  <!-- About Us Modal -->
      <div class="modal fade" id="aboutModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h1 class="modal-title fs-5" id="exampleModalLabel">About Us</h1>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <section id="about-us">
                <div style="max-width: 900px; margin: 0 auto;">
                  <p>
                    At <strong>PharmAssist</strong>, we believe that access to essential medicine should be simple, 
                    reliable, and stress-free. With the growing demand in physical pharmacies and the challenges of limited 
                    stock availability, our platform was built to make healthcare more accessible for everyone.
                  </p>
                  
                  <p>We provide a seamless online reservation system that allows customers to:</p>
                  <ul style="margin-left: 20px; line-height: 1.6;">
                    <li><strong>Track availability in real time</strong> across multiple pharmacy branches, so you'll always know where your prescribed medicine can be found.</li>
                    <li><strong>Reserve your medicines instantly</strong>, securing your spot without the hassle of long queues.</li>
                    <li><strong>Receive timely updates and support</strong>, with real-time communication before and after purchase.</li>
                  </ul>

                  <p>
                    By combining technology with trusted pharmacy care, we're not just simplifying the way you get your medicine—
                    we're also addressing wider issues of medicine accessibility in communities, both locally and globally. 
                    Our mission is to create smarter distribution models that bring medicines closer to the people who need them most.
                  </p>

                  <p>
                    At <strong>PharmAssist</strong>, we're more than just a platform. We're your partner in health—helping you 
                    save time, reduce stress, and focus on what matters most: getting better.
                  </p>
                </div>
              </section>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Contact Us Modal -->
      <div class="modal fade" id="contactModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h1 class="modal-title fs-5" id="exampleModalLabel">Contact Us</h1>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <p>E-mail: a.pharmasee@gmail.com</p> 
              <p>Phone Number: (+63) 961 492 9303</p>
              <p>Facebook Page: PharmAssist</p>
              <p>Instagram: @pharmassist</p>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
          </div>
        </div>
      </div>

</body>
</html>