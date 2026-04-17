<?php
include 'config/connection.php';
session_start();

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'customer') {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Guide | PharmAssist</title>

  <!-- Styles -->
  <link rel="stylesheet" href="plugins/sidebar.css?v=3">
  <link rel="stylesheet" href="plugins/footer.css?v=3">
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
  <link rel="icon" href="website_icon/favicon.png" type="image/png" sizes="64x64">
  <style>
    h2, h3 {
      font-family: "Bricolage Grotesque", sans-serif;
    }

    .page-wrapper {
      transform-origin: top center;
      transition: transform 0.2s ease-out;
    }

    .page-header {
      text-align: center;
      margin: 30px auto 40px auto;
      padding: 0;
    }

    .page-header h1 {
      color: #7393A7;
      font-family: "Bricolage Grotesque", sans-serif;
      font-weight: 600;
      font-size: 28px;
      margin-bottom: 5px;
    }

    .page-header p {
      color: #6C737E;
      font-family: "Bricolage Grotesque", sans-serif;
      font-size: 16px;
    }

    section.content {
      max-width: 900px;
      margin: 0 auto 40px auto;
      padding: 25px 35px;
      background: #fff;
      border-radius: 10px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }

    section.content h2 {
      color: #6C737E;
      margin-top: 0;
      font-family: "Bricolage Grotesque", sans-serif;
      font-weight: 600;
    }

    section.content ul {
      margin-left: 20px;
    }

    .rules {
      background: #F4F8FA;
      padding: 15px;
      border-left: 5px solid #7393A7;
      border-radius: 6px;
    }

    /* ── TTS styles ── */
    .tts-guide-bar {
      max-width: 900px;
      margin: 0 auto 16px auto;
      display: flex;
      align-items: center;
      gap: 10px;
      background: #F8FAFC;
      border: 1px solid #D9EAFD;
      border-radius: 10px;
      padding: 8px 14px;
    }

    .tts-guide-bar span {
      font-size: 13px;
      color: #7393A7;
      font-family: "Bricolage Grotesque", sans-serif;
      flex: 1;
    }

    .tts-guide-btn, .tts-stop-btn {
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

    .tts-guide-btn:hover { background: #BCCCDC; }
    .tts-stop-btn { background: #F8FAFC; }
    .tts-stop-btn:hover { background: #D9EAFD; }

    .tts-step-btn {
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
      margin-top: 8px;
      transition: background 0.2s ease;
    }

    .tts-step-btn:hover { background: #D9EAFD; }

    .tts-step-btn.reading {
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
      border-radius: 8px;
      padding: 12px 16px;
      margin-bottom: 25px;
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
      gap: 5px;
      font-size: 11px;
      padding: 6px 12px;
      border-radius: 16px;
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
      gap: 4px;
      font-size: 10px;
      padding: 4px 10px;
      border-radius: 16px;
      border: 1px solid #BCCCDC;
      background: #F8FAFC;
      color: #7393A7;
      cursor: pointer;
      font-family: "Bricolage Grotesque", sans-serif;
      margin-top: 10px;
      transition: background 0.2s ease;
    }

    .modal-tts-step-btn:hover { background: #D9EAFD; }

    .modal-tts-step-btn.reading {
      background: #D9EAFD;
      border-color: #9AA6B2;
      color: #2d3f50;
    }

    /* Step container with image in modal */
    .modal-step {
      margin-bottom: 25px;
      padding-bottom: 20px;
      border-bottom: 1px solid #E8EEF5;
    }

    .modal-step:last-child {
      border-bottom: none;
    }

    .modal-step h3 {
      color: #6C737E;
      font-size: 16px;
      margin-bottom: 12px;
      margin-top: 0;
    }

    .modal-step p {
      font-size: 14px;
      color: #5A6B7A;
      margin-bottom: 10px;
      line-height: 1.5;
    }

    .modal-step ul {
      margin-left: 18px;
      margin-bottom: 12px;
      font-size: 14px;
      color: #5A6B7A;
    }

    .modal-step ul li {
      margin-bottom: 5px;
    }

    .modal-rules {
      background: #F4F8FA;
      padding: 15px;
      border-left: 5px solid #7393A7;
      border-radius: 6px;
      margin-top: 20px;
    }

    .modal-rules h3 {
      margin-top: 0;
      color: #6C737E;
    }

    .modal-rules ul {
      margin-left: 18px;
      font-size: 13px;
      color: #5A6B7A;
    }

    .modal-rules ul li {
      margin-bottom: 6px;
    }

    @media (max-width: 768px) {
      .floating-buttons {
        bottom: 20px;
        right: 20px;
        gap: 10px;
      }

      .fab {
        width: 50px;
        height: 50px;
        font-size: 20px;
      }

      .modal-content {
        width: 95%;
        height: 85vh;
      }

      .modal-header {
        padding: 20px 25px;
      }

      .modal-header h2 {
        font-size: 20px;
      }

      .modal-inner {
        padding: 30px 20px;
      }

      .modal-close {
        font-size: 28px;
      }
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
      <div class="page-header">
        <h1>Welcome to PharmAssist</h1>
        <p>Your trusted online medicine reservation system</p>
      </div>

      <section class="content" id="about">
        <h2>About Us</h2>
        <p>
          At <strong>PharmAssist</strong>, we believe that access to essential medicine should be simple, 
          reliable, and stress-free. With the growing demand in physical pharmacies and the challenges of 
          limited stock availability, our platform was built to make healthcare more accessible for everyone.
        </p>
        <p>We provide a seamless online reservation system that allows customers to:</p>
        <ul>
          <li><strong>Track availability in real time</strong> across multiple pharmacy branches.</li>
          <li><strong>Reserve medicines instantly</strong>, securing your spot without the hassle of queues.</li>
          <li><strong>Receive timely updates and support</strong> before and after purchase.</li>
        </ul>
        <p>
          By combining technology with trusted pharmacy care, we're not just simplifying the way you get your medicine— 
          we're also addressing wider issues of medicine accessibility in communities. 
        </p>
        <p>
          At <strong>PharmAssist</strong>, we're more than just a platform. 
          We're your partner in health—helping you save time, reduce stress, and focus on what matters most: getting better.
        </p>
      </section>

      <!-- TTS bar above instructions -->
      <div class="tts-guide-bar">
        <i class="bi bi-volume-up-fill" style="color:#9AA6B2; font-size:16px;"></i>
        <span>Text to speech</span>
        <button class="tts-guide-btn" id="ttsReadGuide">
          <i class="bi bi-play-fill"></i> Read guide
        </button>
        <button class="tts-stop-btn" id="ttsStop">
          <i class="bi bi-stop-fill"></i> Stop
        </button>
      </div>

      <section class="content" id="instructions">
        <h2>How to Reserve Medicine</h2>
        <p>Please read the instructions carefully to understand how the reservation system works.</p>

        <div data-step="Step 1: Choose a Branch. The homepage shows three containers, one for each pharmacy branch. Each container shows the medicine name, price, purpose, stock availability, and a Reserve button.">
          <h3>Step 1: Choose a Branch</h3>
          <p>The homepage shows <strong>three containers</strong>, one for each pharmacy branch.</p>
          <ul>
            <li>Medicine name</li>
            <li>Price</li>
            <li>Purpose</li>
            <li>Stock availability</li>
            <li><strong>Reserve</strong> button</li>
          </ul>
          <button class="tts-step-btn">
            <i class="bi bi-volume-up"></i> Read step
          </button>
        </div>

        <div data-step="Step 2: Reserve a Medicine. Click the Reserve button, then fill in your Quantity, Full Name, Contact Number, and Email Address. Ensure your details are correct to avoid rejection.">
          <h3>Step 2: Reserve a Medicine</h3>
          <p>Click the <strong>Reserve</strong> button, then fill in:</p>
          <ul>
            <li>Quantity</li>
            <li>Full Name</li>
            <li>Contact Number</li>
            <li>Email Address</li>
          </ul>
          <p>⚠️ Ensure your details are correct to avoid rejection.</p>
          <button class="tts-step-btn">
            <i class="bi bi-volume-up"></i> Read step
          </button>
        </div>

        <div data-step="Step 3: Submit Your Reservation. Click Confirm. You will see the message: Your reservation is pending for approval.">
          <h3>Step 3: Submit Your Reservation</h3>
          <p>Click <strong>Confirm</strong>. You'll see "Your reservation is pending for approval."</p>
          <button class="tts-step-btn">
            <i class="bi bi-volume-up"></i> Read step
          </button>
        </div>

        <div data-step="Step 4: Wait for Admin Approval. If approved, you get a unique reservation code. If rejected, you will see the reason provided.">
          <h3>Step 4: Wait for Admin Approval</h3>
          <ul>
            <li><strong>Approved</strong> → You get a unique <strong>reservation code</strong>.</li>
            <li><strong>Rejected</strong> → You'll see the reason provided.</li>
          </ul>
          <button class="tts-step-btn">
            <i class="bi bi-volume-up"></i> Read step
          </button>
        </div>

        <div data-step="Step 5: Claim Your Reservation. Show your reservation code at the pharmacy. If you forget, find it under Notifications.">
          <h3>Step 5: Claim Your Reservation</h3>
          <ul>
            <li>Show your <strong>reservation code</strong> at the pharmacy.</li>
            <li>If you forget, find it under <strong>Notifications</strong>.</li>
          </ul>
          <button class="tts-step-btn">
            <i class="bi bi-volume-up"></i> Read step
          </button>
        </div>

        <div class="rules">
          <h3>Rules</h3>
          <ul>
            <li>Each reservation is <strong>branch-specific</strong>.</li>
            <li>Details must be accurate and complete.</li>
            <li>Reservation codes expire after <strong>3 days</strong>.</li>
            <li>Admins may adjust quantities if stock is limited.</li>
            <li>Fake reservations or repeated no-claims can lead to restrictions.</li>
            <li>Always check <strong>Notifications</strong> for updates.</li>
          </ul>
        </div>
      </section>
    </div>
  </section>

  <!-- Floating Action Buttons -->
  <div class="floating-buttons">
    <button class="fab" id="zoomInBtn" title="Zoom in">+</button>
    <button class="fab" id="zoomOutBtn" title="Zoom out">−</button>
    <button class="fab" id="guideBtn" title="View Guide">?</button>
  </div>

  <!-- Modal for Guide -->
  <div class="modal-overlay" id="guideModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Reservation Guide</h2>
        <button class="modal-close" id="closeBtn">&times;</button>
      </div>
      <div class="modal-inner" id="modalInner">
        
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

        <!-- Step 1 -->
        <div class="modal-step" data-step="Step 1: Choose a Branch. The homepage shows three containers, one for each pharmacy branch. Each container shows the medicine name, price, purpose, stock availability, and a Reserve button.">
          <h3>Step 1: Choose a Branch</h3>
          <p>The homepage shows <strong>three containers</strong>, one for each pharmacy branch.</p>
          <ul>
            <li>Medicine name</li>
            <li>Price</li>
            <li>Purpose</li>
            <li>Stock availability</li>
            <li><strong>Reserve</strong> button</li>
          </ul>
          <button class="modal-tts-step-btn">
            <i class="bi bi-volume-up"></i> Read step
          </button>
        </div>

        <!-- Step 2 -->
        <div class="modal-step" data-step="Step 2: Reserve a Medicine. Click the Reserve button, then fill in your Quantity, Full Name, Contact Number, and Email Address. Ensure your details are correct to avoid rejection.">
          <h3>Step 2: Reserve a Medicine</h3>
          <p>Click the <strong>Reserve</strong> button, then fill in:</p>
          <ul>
            <li>Quantity</li>
            <li>Full Name</li>
            <li>Contact Number</li>
            <li>Email Address</li>
          </ul>
          <p>⚠️ Ensure your details are correct to avoid rejection.</p>
          <button class="modal-tts-step-btn">
            <i class="bi bi-volume-up"></i> Read step
          </button>
        </div>

        <!-- Step 3 -->
        <div class="modal-step" data-step="Step 3: Submit Your Reservation. Click Confirm. You will see the message: Your reservation is pending for approval.">
          <h3>Step 3: Submit Your Reservation</h3>
          <p>Click <strong>Confirm</strong>. You'll see "Your reservation is pending for approval."</p>
          <button class="modal-tts-step-btn">
            <i class="bi bi-volume-up"></i> Read step
          </button>
        </div>

        <!-- Step 4 -->
        <div class="modal-step" data-step="Step 4: Wait for Admin Approval. If approved, you get a unique reservation code. If rejected, you will see the reason provided.">
          <h3>Step 4: Wait for Admin Approval</h3>
          <ul>
            <li><strong>Approved</strong> → You get a unique <strong>reservation code</strong>.</li>
            <li><strong>Rejected</strong> → You'll see the reason provided.</li>
          </ul>
          <button class="modal-tts-step-btn">
            <i class="bi bi-volume-up"></i> Read step
          </button>
        </div>

        <!-- Step 5 -->
        <div class="modal-step" data-step="Step 5: Claim Your Reservation. Show your reservation code at the pharmacy. If you forget, find it under Notifications.">
          <h3>Step 5: Claim Your Reservation</h3>
          <ul>
            <li>Show your <strong>reservation code</strong> at the pharmacy.</li>
            <li>If you forget, find it under <strong>Notifications</strong>.</li>
          </ul>
          <button class="modal-tts-step-btn">
            <i class="bi bi-volume-up"></i> Read step
          </button>
        </div>

        <!-- Rules -->
        <div class="modal-rules">
          <h3>Rules</h3>
          <ul>
            <li>Each reservation is <strong>branch-specific</strong>.</li>
            <li>Details must be accurate and complete.</li>
            <li>Reservation codes expire after <strong>3 days</strong>.</li>
            <li>Admins may adjust quantities if stock is limited.</li>
            <li>Fake reservations or repeated no-claims can lead to restrictions.</li>
            <li>Always check <strong>Notifications</strong> for updates.</li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <?php include 'footer.php'; ?>

  <script src="source/sidebar.js"></script>
  <script src="source/homepage.js"></script>
  <script>
  document.addEventListener("DOMContentLoaded", function () {

    let currentZoom = 100;
    const minZoom = 80;
    const maxZoom = 150;
    const zoomStep = 10;

    let currentStepBtn = null;
    let currentModalStepBtn = null;

    const zoomInBtn = document.getElementById('zoomInBtn');
    const zoomOutBtn = document.getElementById('zoomOutBtn');
    const guideBtn = document.getElementById('guideBtn');
    const pageWrapper = document.querySelector('.page-wrapper');
    const guideModal = document.getElementById('guideModal');
    const closeBtn = document.getElementById('closeBtn');
    
    // Ensure voices are loaded
    window.speechSynthesis.onvoiceschanged = function() {
      window.speechSynthesis.getVoices();
    };

    // ===== SPEAK FUNCTION (MAIN PAGE) =====
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
      if (currentStepBtn) { resetBtn(currentStepBtn); currentStepBtn = null; }
    }

    function setReading(btn) {
      if (currentStepBtn && currentStepBtn !== btn) resetBtn(currentStepBtn);
      currentStepBtn = btn;
      btn.classList.add('reading');
      btn.innerHTML = '<span class="tts-dot"></span> Reading...';
    }

    function resetBtn(btn) {
      btn.classList.remove('reading');
      btn.innerHTML = '<i class="bi bi-volume-up"></i> Read step';
    }

    // ===== SPEAK FUNCTION (MODAL) =====
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
      if (currentModalStepBtn) { resetModalBtn(currentModalStepBtn); currentModalStepBtn = null; }
    }

    function setModalReading(btn) {
      if (currentModalStepBtn && currentModalStepBtn !== btn) resetModalBtn(currentModalStepBtn);
      currentModalStepBtn = btn;
      btn.classList.add('reading');
      btn.innerHTML = '<span class="tts-dot"></span> Reading...';
    }

    function resetModalBtn(btn) {
      btn.classList.remove('reading');
      btn.innerHTML = '<i class="bi bi-volume-up"></i> Read step';
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

    // ===== MAIN PAGE TTS EVENT LISTENERS =====
    // Per-step Read buttons
    document.querySelectorAll('.tts-step-btn').forEach(btn => {
      btn.addEventListener('click', function () {
        const stepDiv = this.closest('[data-step]');
        const text = stepDiv ? stepDiv.getAttribute('data-step') : '';
        if (this.classList.contains('reading')) { stopTTS(); return; }
        setReading(this);
        speak(text, () => { resetBtn(this); currentStepBtn = null; });
      });
    });

    // Read entire guide
    document.getElementById('ttsReadGuide').addEventListener('click', function () {
      const steps = document.querySelectorAll('[data-step]');
      if (steps.length === 0) return;
      let index = 0;

      function readNext() {
        if (index >= steps.length) {
          if (currentStepBtn) { resetBtn(currentStepBtn); currentStepBtn = null; }
          return;
        }
        const step = steps[index];
        const btn = step.querySelector('.tts-step-btn');
        const text = step.getAttribute('data-step');
        if (btn) setReading(btn);
        index++;
        speak(text, readNext);
      }

      readNext();
    });

    // Stop
    document.getElementById('ttsStop').addEventListener('click', stopTTS);

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

    // Initialize zoom
    updateZoom();
  });
  </script>
</body>
</html>