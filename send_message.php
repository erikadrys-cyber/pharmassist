<?php
session_start();
require_once 'config/connection.php';

if (!isset($_SESSION['id'])) {
    header('Location: login.php'); 
    exit;
}

$userId = $_SESSION['id'];
$username = $_SESSION['username'] ?? 'User';
$userEmail = $_SESSION['email'] ?? '';

$message_sent = false;
$error_message = '';

// Process form submission - Handle admin message form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($conn, trim($_POST['name'] ?? ''));
    $email = mysqli_real_escape_string($conn, trim($_POST['email'] ?? ''));
    $subject = mysqli_real_escape_string($conn, trim($_POST['subject'] ?? ''));
    $message = mysqli_real_escape_string($conn, trim($_POST['message'] ?? ''));
    
    // Validate all fields are filled
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error_message = 'Please fill in all fields.';
    } else {
        // Insert message into database
        $sql = "INSERT INTO contact_messages (user_id, name, email, subject, message, created_at) 
                VALUES ('$user_id', '$name', '$email', '$subject', '$message', NOW())";
        
        if (mysqli_query($conn, $sql)) {
            $message_sent = "Message sent successfully!";
            // Clear form by redirecting
            echo "<script>
                setTimeout(function() {
                    window.location.href = 'send_message.php?success=1';
                }, 2000);
            </script>";
        } else {
            $error_message = "Error: " . mysqli_error($conn);
        }
    }
}

if (isset($_GET['success'])) {
    $message_sent = "Message sent successfully!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Message | PharmAssist</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,200..800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@300&family=Tinos&display=swap" rel="stylesheet">
    <link href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css' rel='stylesheet'>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    
    <link rel="stylesheet" href="plugins/sidebar.css?v=3">
    <link rel="stylesheet" href="plugins/sendmessage.css?v=3">
    <link rel="icon" href="website_icon/favicon.png" type="image/png" sizes="64x64">
    <style>
      .page-wrapper {
        transform-origin: top center;
        transition: transform 0.2s ease-out;
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
    </style>
</head>
<body>

<!-- Sidebar -->
<?php include 'sidebar.php'; ?>

<!-- Main Content -->
<section class="home-section">
    <div class="home-content">
        <i class='bx bx-menu'></i>
        <span><a href ="homepage.php" style="text-decoration: none; color: white; font-size: 1.5rem;" class="text fw-semibold">PharmAssist</span></a>
    </div>

    <div class="page-wrapper">
        <div class="page-container">
            <?php if ($message_sent): ?>
                <div class="success-message"><?= htmlspecialchars($message_sent) ?></div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="error-message"><?= htmlspecialchars($error_message) ?></div>
            <?php endif; ?>

            <section id="contact">
                <div class="contact-box">
                    <h2>Send Us Message</h2>
                    <p class="contact-lead">Concerns and Feedbacks</p>
                    <p>Place your concerns and feedbacks here. Our company will do our best to address your concerns.</p>
                    
                    <form class="contact-form" method="POST" action="" id="contactForm">
                        <div class="form-item">
                            <input id="contact-name" name="name" type="text" placeholder=" " value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : htmlspecialchars($username) ?>" required>
                            <label for="contact-name">Username</label>
                        </div>

                        <div class="form-item">
                            <input id="contact-email" name="email" type="email" placeholder=" " value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : htmlspecialchars($userEmail) ?>" required>
                            <label for="contact-email">E-Mail</label>
                        </div>

                        <div class="form-item">
                            <input id="contact-subject" name="subject" type="text" placeholder=" " value="<?= isset($_POST['subject']) ? htmlspecialchars($_POST['subject']) : '' ?>" required>
                            <label for="contact-subject">Subject</label>
                        </div>

                        <div class="form-item">
                            <textarea id="contact-message" name="message" placeholder=" " rows="4" required><?= isset($_POST['message']) ? htmlspecialchars($_POST['message']) : '' ?></textarea>
                            <label for="contact-message">Message</label>
                        </div>

                        <button class="form-button" type="submit">Send Message</button>
                    </form>
                </div>
            </section>
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
      <h2>Message Guide</h2>
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

      <!-- Overview -->
      <div class="modal-step" data-step="Send Message page. This page allows you to send your concerns and feedbacks directly to PharmAssist support team.">
        <h3>Overview</h3>
        <p>This page is where you can send your concerns, feedbacks, and suggestions to the PharmAssist team. We value your input and will do our best to address your concerns.</p>
        <button class="modal-tts-step-btn">
          <i class="bi bi-volume-up"></i> Read step
        </button>
      </div>

      <!-- Form Fields -->
      <div class="modal-step" data-step="Form Fields. Full Name field: Enter your complete name. Email field: Enter your valid email address. Subject field: Enter what your message is about. Message field: Write your detailed message or feedback.">
        <h3>Understanding the Form Fields</h3>
        <ul>
          <li><strong>Full Name:</strong> Your complete name. This is automatically filled with your registered name.</li>
          <li><strong>Email:</strong> Your email address. This is automatically filled with your registered email.</li>
          <li><strong>Subject:</strong> A brief title describing what your message is about (e.g., "Bug Report", "Feature Request", "General Feedback")</li>
          <li><strong>Message:</strong> Your detailed message, concern, or feedback. Be clear and descriptive so we can better help you.</li>
        </ul>
        <button class="modal-tts-step-btn">
          <i class="bi bi-volume-up"></i> Read step
        </button>
      </div>

      <!-- How to Use -->
      <div class="modal-step" data-step="How to send a message. Step 1: Check that your name and email are correct. Modify them if needed. Step 2: Enter a subject for your message. Step 3: Write your detailed message or concern in the message field. Step 4: Click the Send Message button. Step 5: You will see a success message when your message is sent.">
        <h3>How to Send a Message</h3>
        <ul>
          <li><strong>Step 1:</strong> Verify your name and email are correct. You can edit them if needed.</li>
          <li><strong>Step 2:</strong> Enter a subject line that briefly describes your concern or feedback</li>
          <li><strong>Step 3:</strong> Write your detailed message in the message field. Be specific so we understand your concern.</li>
          <li><strong>Step 4:</strong> Click the "Send Message" button to submit your message</li>
          <li><strong>Step 5:</strong> A success notification will appear when your message is sent</li>
        </ul>
        <button class="modal-tts-step-btn">
          <i class="bi bi-volume-up"></i> Read step
        </button>
      </div>

      <!-- Tips -->
      <div class="modal-step" data-step="Tips for sending a message. Include specific details about your issue. Be polite and clear in your message. Describe steps to reproduce if reporting a bug. Include your order number if your message is about a reservation. Use a clear subject line.">
        <h3>Tips for Better Support</h3>
        <ul>
          <li>Be as specific as possible about your issue or suggestion</li>
          <li>If reporting a problem, describe the steps that led to the issue</li>
          <li>If your message is about a reservation, include the reservation ID</li>
          <li>Use a clear, descriptive subject line</li>
          <li>Keep your message polite and professional</li>
          <li>Our support team will respond as soon as possible</li>
        </ul>
        <button class="modal-tts-step-btn">
          <i class="bi bi-volume-up"></i> Read step
        </button>
      </div>

     
    </div>
  </div>
</div>

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
</script>

<script>
    document.querySelectorAll('.form-item input, .form-item textarea').forEach(input => {
        const label = input.nextElementSibling;

        if (input.value.trim() !== '') {
            label.style.top = '-18px';
            label.style.fontSize = '12px';
            label.style.color = '#7393A7';
            label.style.fontWeight = '600';
            label.style.background = 'white';
            label.style.padding = '0 5px';
        }

        input.addEventListener('focus', () => {
            label.style.top = '-18px';
            label.style.fontSize = '12px';
            label.style.color = '#7393A7';
            label.style.fontWeight = '600';
            label.style.background = 'white';
            label.style.padding = '0 5px';
        });

        input.addEventListener('blur', () => {
            if (input.value.trim() === '') {
                if (input.tagName.toLowerCase() === 'textarea') {
                    label.style.top = '75%';
                } else {
                    label.style.top = '50%';
                }
                label.style.fontSize = '16px';
                label.style.color = '#B5CFD8';
                label.style.fontWeight = '500';
                label.style.background = 'transparent';
                label.style.padding = '0';
            }
        });
    });
</script>

<script src="sidebar/sidebar.js"></script>
<script src="source/homepage.js"></script>
</body>
</html>