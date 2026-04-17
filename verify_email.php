<?php
ob_start();

require 'vendor/autoload.php';

session_start();
include 'config/connection.php';
include 'helpers.php';

date_default_timezone_set('Asia/Manila');

// Keep errors out of AJAX responses (log instead)
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('display_errors', 0);
ini_set('error_log', __DIR__ . '/verify_email_errors.log');

// Check if user has temp data
if (!isset($_SESSION['temp_user_id'])) {
    header('Location: register.php');
    exit;
}

$error_message = '';
$success_message = '';

$user_id = $_SESSION['temp_user_id'];
$email = $_SESSION['temp_email'];
$first_name = $_SESSION['temp_first_name'];
$last_name = $_SESSION['temp_last_name'];

function json_response($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

// HANDLE FORM
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // VERIFY EMAIL
    if (isset($_POST['verify_email'])) {

        $otp_code = trim($_POST['otp_code'] ?? '');

        if (empty($otp_code)) {
            $error_message = 'Please enter the verification code.';
        } else {

            $otp_valid = validateOTP($user_id, $otp_code, 'email');

            if (!$otp_valid) {
                $error_message = 'Invalid or expired code. Please try again.';
            } else {

                $update_query = "UPDATE users SET email_verified = 1 WHERE user_id = ?";
                $stmt = $conn->prepare($update_query);

                if ($stmt) {
                    $stmt->bind_param('i', $user_id);

                    if ($stmt->execute()) {

                        $_SESSION['success_message'] = "Your email has been verified! Your account is now under review. Please wait for admin approval before reserving medicines.";

                        $stmt->close();

                        ob_end_clean();
                        header('Location: login.php');
                        exit;

                    } else {
                        $error_message = 'An error occurred. Please try again.';
                    }

                    $stmt->close();
                }
            }
        }
    }

    // RESEND OTP
    elseif (isset($_POST['resend_otp'])) {

        $is_ajax = isset($_POST['ajax']) && $_POST['ajax'] === '1';
        error_log("RESEND OTP requested (ajax=" . ($is_ajax ? '1' : '0') . ") user_id={$user_id} email={$email}");

        // Invalidate any previous unused codes for this user
        $invalidate_query = "UPDATE email_verification_codes SET is_used = 1 WHERE user_id = ? AND is_used = 0";
        $invalidate_stmt = $conn->prepare($invalidate_query);
        if ($invalidate_stmt) {
            $invalidate_stmt->bind_param('i', $user_id);
            $invalidate_stmt->execute();
            $invalidate_stmt->close();
        }

        $email_otp = generateOTP();
        $expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));

        $otp_query = "INSERT INTO email_verification_codes (user_id, code, expires_at) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($otp_query);

        if (!$stmt) {
            error_log('Prepare resend OTP insert failed: ' . $conn->error);
            $msg = 'An error occurred. Please try again.';
            if ($is_ajax) json_response(['ok' => false, 'message' => $msg], 500);
            $error_message = $msg;
        } else {
            $stmt->bind_param('iss', $user_id, $email_otp, $expires_at);

            if (!$stmt->execute()) {
                error_log('Execute resend OTP insert failed: ' . $stmt->error);
                $stmt->close();
                $msg = 'An error occurred. Please try again.';
                if ($is_ajax) json_response(['ok' => false, 'message' => $msg], 500);
                $error_message = $msg;
            } else {
                $stmt->close();

                $full_name = trim($first_name . ' ' . $last_name);
                if (sendEmailOTP($email, $full_name, $email_otp, 'email')) {
                    $msg = 'Verification code resent to your email.';
                    if ($is_ajax) json_response(['ok' => true, 'message' => $msg]);
                    $success_message = $msg;
                } else {
                    $details = isset($LAST_MAIL_ERROR) && $LAST_MAIL_ERROR ? (' (' . $LAST_MAIL_ERROR . ')') : '';
                    error_log('Resend OTP failed: ' . ($LAST_MAIL_ERROR ?: 'unknown error'));
                    $msg = 'Failed to send email. Please try again.' . $details;
                    if ($is_ajax) json_response(['ok' => false, 'message' => $msg], 500);
                    $error_message = $msg;
                }
            }
        }
    }
}

ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email | PharmAssist</title>
    <link rel="stylesheet" href="plugins/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,200..800&family=Tinos:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
    <link rel="icon" href="website_icon/favicon.png" type="image/png" sizes="64x64">
    <style>
        body {
            font-family: 'Tinos', serif;
        }

        .otp-input {
            font-size: 24px;
            letter-spacing: 10px;
            text-align: center;
            font-weight: bold;
            font-family: monospace;
        }

        .login-title {
            font-family: 'Bricolage Grotesque', sans-serif;
        }

        .notice {
            font-family: 'Tinos', serif;
        }

        .otp-info {
            background-color: #e8f4f8;
            border-left: 4px solid #7393A7;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            color: #555;
            font-family: 'Tinos', serif;
        }

        .otp-info p {
            font-family: 'Tinos', serif;
        }

        .otp-info strong {
            font-family: 'Bricolage Grotesque', sans-serif;
        }

        .resend-link {
            text-align: center;
            margin-top: 20px;
        }

        .resend-link button {
            background: none;
            border: none;
            color: #7393A7;
            cursor: pointer;
            text-decoration: underline;
            font-size: 14px;
            padding: 0;
            font-family: 'Bricolage Grotesque', sans-serif;
        }

        .resend-link button:hover {
            color: #5f7a8d;
        }

        .resend-link p {
            font-family: 'Tinos', serif;
        }

        .verify-btn {
            width: 100%;
            padding: 12px;
            background-color: #7393A7;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
            font-family: 'Bricolage Grotesque', sans-serif;
        }

        .verify-btn:hover:not(:disabled) {
            background-color: #5f7a8d;
        }

        .verify-btn:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }

        .form-login label {
            font-family: 'Bricolage Grotesque', sans-serif;
        }

        .signup-link a {
            font-family: 'Tinos', serif;
        }
    </style>
</head>
<body>
  <div id="login-page">
      <div id="logo">
            <img src="website_icon/web_logo.png" alt="PharmAssist Logo" />
        </div>
    <div class="login" style="max-width: 500px;">
    <h2 class="login-title"><i class="fas fa-envelope" style="color: #7393A7;"></i> Verify Email</h2>
    <p class="notice">Enter the code sent to your email</p>

    <?php if (isset($error_message) && !empty($error_message)): ?>
        <div style="color: #721c24; margin-bottom: 15px; text-align: left; padding: 12px 15px; border: 1px solid #f5c6cb; border-radius: 5px; background-color: #f8d7da; font-family: 'Tinos', serif;">
            <i class="fas fa-exclamation-circle" style="margin-right: 8px;"></i>
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($success_message) && !empty($success_message)): ?>
        <div style="color: #155724; margin-bottom: 15px; text-align: left; padding: 12px 15px; border: 1px solid #c3e6cb; border-radius: 5px; background-color: #d4edda; font-family: 'Tinos', serif;">
            <i class="fas fa-check-circle" style="margin-right: 8px;"></i>
            <?php echo htmlspecialchars($success_message); ?>
        </div>
    <?php endif; ?>

    <div class="otp-info">
        <p style="margin: 0;"><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></p>
        <p style="margin: 5px 0 0 0; font-size: 12px; color: #777;">We've sent a 6-digit verification code to your email. It expires in 10 minutes.</p>
    </div>

    <form class="form-login" method="POST">
      <label for="otp_code">Verification Code</label>
      <div class="input-email">
        <input type="text" name="otp_code" id="otp_code" placeholder="000000" maxlength="6" class="otp-input" required
               value="<?php echo isset($_POST['otp_code']) ? htmlspecialchars($_POST['otp_code']) : ''; ?>" 
               oninput="this.value = this.value.replace(/[^0-9]/g, '')">
      </div>
      
      <button type="submit" name="verify_email" class="verify-btn" id="verifyBtn">Verify Email</button>
    </form>

    <div class="resend-link">
      <p style="margin: 20px 0 10px 0; font-size: 12px; color: #999;">Didn't receive the code?</p>
      <form method="POST" style="display: inline;" id="resendForm">
        <button type="submit" name="resend_otp" class="resend-btn">Resend Code</button>
      </form>
      <div id="resendStatus" style="margin-top: 10px; font-size: 12px;"></div>
    </div>

    <div class="signup-link" style="text-align: center; margin: 20px 0 0 0;">
      <a href="register.php" style="color: #7393A7; text-decoration: none; font-size: 12px;">Back to Registration</a>
    </div>
  </div>
  <div class="background">
    <h1>Secure Your<br> Account Now</h1>
  </div>
</div>

<script>
// Resend via AJAX so the page doesn't "hang" while sending
const resendForm = document.getElementById('resendForm');
const resendBtn = document.querySelector('.resend-btn');
const resendStatus = document.getElementById('resendStatus');

if (resendForm && resendBtn && resendStatus) {
  let busy = false;
  resendForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (busy) return;
    busy = true;

    resendBtn.disabled = true;
    const oldText = resendBtn.textContent;
    resendBtn.textContent = 'Sending...';
    resendStatus.textContent = '';
    resendStatus.style.color = '';

    try {
      const formData = new FormData(resendForm);
      // Because we prevent the browser's native submit, the clicked button's
      // name/value is not included automatically. Add it explicitly so PHP hits
      // the resend handler.
      formData.append('resend_otp', '1');
      formData.append('ajax', '1');

      const res = await fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });

      const raw = await res.text();
      let data = null;
      try { data = JSON.parse(raw); } catch (_) { /* ignore */ }

      const message =
        (data && typeof data.message === 'string' && data.message) ? data.message :
        (raw && raw.trim()) ? raw.trim() :
        (res.ok ? 'Sent.' : 'Failed to send.');

      resendStatus.textContent = message;
      resendStatus.style.color = res.ok ? '#155724' : '#721c24';
    } catch (err) {
      resendStatus.textContent = 'Failed to send. Please try again.';
      resendStatus.style.color = '#721c24';
    } finally {
      // short anti-double-click delay
      setTimeout(() => {
        resendBtn.disabled = false;
        resendBtn.textContent = oldText;
        busy = false;
      }, 1200);
    }
  });
}
</script>
</body>
</html>