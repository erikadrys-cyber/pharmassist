<?php
// ============================================================
// SIMPLIFIED EMAIL HELPERS - All-in-one with debugging
// Timezone set to Manila, config hardcoded
// ============================================================

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Set timezone
date_default_timezone_set('Asia/Manila');

// Define constants
if (!defined('OTP_LENGTH')) define('OTP_LENGTH', 6);
if (!defined('OTP_EXPIRY_MINUTES')) define('OTP_EXPIRY_MINUTES', 10);
if (!defined('MAX_FILE_SIZE')) define('MAX_FILE_SIZE', 5 * 1024 * 1024);
if (!defined('ALLOWED_EXTENSIONS')) define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'pdf']);
if (!defined('UPLOAD_DIR')) define('UPLOAD_DIR', 'uploads/id_photos/');

// ============================================================
// EMAIL CONFIGURATION (Hardcoded for XAMPP local testing)
// ============================================================
$MAIL_CONFIG = [
    'host'       => getenv('MAIL_HOST')     ?: 'smtp.gmail.com',
    'port'       => getenv('MAIL_PORT')     ?: 587,
    'username'   => getenv('MAIL_USERNAME') ?: 'a.pharmasee@gmail.com',
    'password'   => getenv('MAIL_PASSWORD') ?: 'ujct nsjw ptzq ahnk',
    'from_email' => getenv('MAIL_USERNAME') ?: 'a.pharmasee@gmail.com',
    'from_name'  => 'PharmAssist Support',
    'timeout'    => 30,
];

// Stores last PHPMailer error for UI/logging (best-effort).
$LAST_MAIL_ERROR = '';

/**
 * Send email via SMTP with extensive debugging
 */
function sendEmailViaSMTP($recipient_email, $recipient_name, $subject, $html_message) {
    global $MAIL_CONFIG, $LAST_MAIL_ERROR;
    $LAST_MAIL_ERROR = '';
    
    error_log("\n========== EMAIL SENDING START ==========");
    error_log("Timestamp: " . date('Y-m-d H:i:s'));
    error_log("Recipient: " . $recipient_email);
    error_log("Subject: " . $subject);
    
    try {
        require __DIR__ . '/vendor/autoload.php';
        
        $mail = new PHPMailer(true);
        
        error_log("✓ PHPMailer loaded");
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = $MAIL_CONFIG['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $MAIL_CONFIG['username'];
        $mail->Password = $MAIL_CONFIG['password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 587;
        $mail->Timeout = $MAIL_CONFIG['timeout'];
        
        error_log("SMTP Config:");
        error_log("  Host: " . $MAIL_CONFIG['host']);
        error_log("  Port: " . $MAIL_CONFIG['port']);
        error_log("  Encryption: STARTTLS");
        error_log("  Username: " . substr($MAIL_CONFIG['username'], 0, 3) . "***");
        
        // CRITICAL: SSL Options for local development
        $mail->SMTPOptions = [];
        error_log("✓ SSL verification DISABLED (for local dev)");
        
        // Debug logging slows things down and isn't needed for normal use.
        $mail->SMTPDebug = 2;
        $mail->Debugoutput = function($str, $level) {
            error_log("SMTP DEBUG: " . $str);
        };
        
        // Recipients
        $mail->setFrom($MAIL_CONFIG['from_email'], $MAIL_CONFIG['from_name']);
        $mail->addAddress($recipient_email, $recipient_name);
        $mail->addReplyTo($MAIL_CONFIG['from_email'], $MAIL_CONFIG['from_name']);
        
        error_log("✓ Recipients configured");
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $html_message;
        $mail->AltBody = strip_tags($html_message);
        
        error_log("✓ Message content set");
        
        // Attempt to send
        error_log("→ Attempting SMTP connection and send...");
        
        if ($mail->send()) {
            error_log("✓✓✓ EMAIL SENT SUCCESSFULLY!");
            error_log("========== EMAIL SENDING SUCCESS ==========\n");
            return true;
        } else {
            $LAST_MAIL_ERROR = (string)$mail->ErrorInfo;
            error_log("❌ Send failed: " . $mail->ErrorInfo);
            error_log("========== EMAIL SENDING FAILED ==========\n");
            return false;
        }
        
    } catch (Exception $e) {
        $LAST_MAIL_ERROR = (string)$e->getMessage();
        error_log("❌ Exception: " . $e->getMessage());
        error_log("========== EMAIL SENDING EXCEPTION ==========\n");
        return false;
    } catch (Error $e) {
        $LAST_MAIL_ERROR = (string)$e->getMessage();
        error_log("❌ Fatal Error: " . $e->getMessage());
        error_log("File: " . $e->getFile() . " Line: " . $e->getLine());
        error_log("========== EMAIL SENDING ERROR ==========\n");
        return false;
    }
}

/**
 * Generate OTP
 */
function generateOTP($length = OTP_LENGTH) {
    $otp = str_pad(random_int(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
    error_log("OTP Generated: '$otp'");
    return $otp;
}

/**
 * Send OTP Email
 */
function sendEmailOTP($email, $fullname, $otp_code, $type = 'email') {
    global $LAST_MAIL_ERROR;
    $otp_code = (string)$otp_code;
    if (!preg_match('/^\d{6}$/', $otp_code)) {
        $LAST_MAIL_ERROR = 'Invalid OTP value passed to sendEmailOTP';
        error_log("❌ sendEmailOTP called with non-6-digit OTP. fullname='{$fullname}' otp_code='{$otp_code}' type='{$type}'");
        return false;
    }

    $subject = "PharmAssist - Verification Code";
    
    $message = "
    <html>
        <body style='font-family: Arial, sans-serif; background-color: #f5f5f5; padding: 20px;'>
            <div style='background-color: white; padding: 30px; border-radius: 8px; max-width: 500px; margin: 0 auto;'>
                <h2 style='color: #7393A7;'>Email Verification</h2>
                <p>Hello <strong>" . htmlspecialchars($fullname) . "</strong>,</p>
                <p>Thank you for registering with PharmAssist! To complete your registration, please verify your email using the code below:</p>
                
                <div style='background-color: #7393A7; color: white; padding: 15px; text-align: center; border-radius: 5px; margin: 20px 0;'>
                    <h1 style='letter-spacing: 5px; margin: 0;'>" . htmlspecialchars($otp_code) . "</h1>
                </div>
                
                <p><strong>This code expires in 10 minutes.</strong></p>
                <p>If you didn't request this code, please ignore this email.</p>
                
                <hr style='border: none; border-top: 1px solid #ddd; margin: 20px 0;'>
                <p style='color: #999; font-size: 12px;'>PharmAssist Team</p>
            </div>
        </body>
    </html>";

    return sendEmailViaSMTP($email, $fullname, $subject, $message);
}

/**
 * Send ID Verification Notification
 */
function sendIDVerificationNotification($email, $fullname, $status, $rejection_reason = '') {
    if ($status === 'approved') {
        $subject = "PharmAssist - ID Verification Approved";
        $message = "
        <html>
            <body style='font-family: Arial, sans-serif; background-color: #f5f5f5; padding: 20px;'>
                <div style='background-color: white; padding: 30px; border-radius: 8px; max-width: 500px; margin: 0 auto;'>
                    <h2 style='color: #28a745;'>✓ ID Verification Approved</h2>
                    <p>Hello <strong>" . htmlspecialchars($fullname) . "</strong>,</p>
                    <p>Great news! Your ID has been verified and approved. Your account is now fully activated.</p>
                    <p>You can now log in and start using PharmAssist services.</p>
                    <hr style='border: none; border-top: 1px solid #ddd; margin: 20px 0;'>
                    <p style='color: #999; font-size: 12px;'>PharmAssist Team</p>
                </div>
            </body>
        </html>";
    } else {
        $subject = "PharmAssist - ID Verification Rejected";
        $message = "
        <html>
            <body style='font-family: Arial, sans-serif; background-color: #f5f5f5; padding: 20px;'>
                <div style='background-color: white; padding: 30px; border-radius: 8px; max-width: 500px; margin: 0 auto;'>
                    <h2 style='color: #dc3545;'>✗ ID Verification Rejected</h2>
                    <p>Hello <strong>" . htmlspecialchars($fullname) . "</strong>,</p>
                    <p>Unfortunately, your ID submission was not approved.</p>
                    <p><strong>Reason:</strong> " . htmlspecialchars($rejection_reason) . "</p>
                    <p>Please resubmit a clearer photo of your valid ID.</p>
                    <hr style='border: none; border-top: 1px solid #ddd; margin: 20px 0;'>
                    <p style='color: #999; font-size: 12px;'>PharmAssist Team</p>
                </div>
            </body>
        </html>";
    }

    return sendEmailViaSMTP($email, $fullname, $subject, $message);
}

/**
 * Validate OTP
 */
function validateOTP($user_id, $code, $type = 'email') {
    global $conn;
    
    $table = 'email_verification_codes';
    $code = (string)trim($code);
    $user_id = (int)$user_id;
    
    if (strlen($code) < 6 && is_numeric($code)) {
        $code = str_pad($code, 6, '0', STR_PAD_LEFT);
    }
    
    $current_time = date('Y-m-d H:i:s');
    
    $query = "SELECT code_id, code, expires_at, is_used 
              FROM {$table} 
              WHERE user_id = ? AND is_used = 0 
              ORDER BY created_at DESC LIMIT 1";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) return false;
    
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        return false;
    }
    
    $row = $result->fetch_assoc();
    $stored_code = (string)trim($row['code']);
    $code_id = (int)$row['code_id'];
    $expires_at = $row['expires_at'];
    
    $stmt->close();
    
    // Check expiry
    $expires_dt = DateTime::createFromFormat('Y-m-d H:i:s', $expires_at);
    $current_dt = DateTime::createFromFormat('Y-m-d H:i:s', $current_time);
    
    if ($current_dt >= $expires_dt) return false;
    
    // Check code
    if ($stored_code !== $code) return false;
    
    // Mark as used
    $update_query = "UPDATE {$table} SET is_used = 1 WHERE code_id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param('i', $code_id);
    $update_stmt->execute();
    $update_stmt->close();
    
    return true;
}

/**
 * Validate ID Photo Upload
 */
function validateIDPhotoUpload($file) {
    $errors = [];
    
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Please upload a valid file.';
        return $errors;
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        $errors[] = 'File size must not exceed 5MB.';
    }
    
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_ext, ALLOWED_EXTENSIONS)) {
        $errors[] = 'Only JPG, JPEG, PNG, and PDF files are allowed.';
    }
    
    $mime_type = mime_content_type($file['tmp_name']);
    $allowed_mimes = ['image/jpeg', 'image/png', 'application/pdf'];
    if (!in_array($mime_type, $allowed_mimes)) {
        $errors[] = 'Invalid file type. Please upload an image or PDF.';
    }
    
    return $errors;
}

/**
 * Save ID Photo
 */
function saveIDPhoto($file) {
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }
    
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = 'id_' . time() . '_' . uniqid() . '.' . $file_ext;
    $filepath = UPLOAD_DIR . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return $filename;
    }
    
    return false;
}

/**
 * Utility functions
 */
function sanitize_input($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

?>