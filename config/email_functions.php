<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Send OTP verification email for email verification during registration
 */
if (!function_exists('sendEmailOTP')) {
function sendEmailOTP($email, $fullname, $otp, $type = 'email') {
    require __DIR__ . '/../vendor/autoload.php';
    
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = getenv('MAIL_HOST')     ?: 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = getenv('MAIL_USERNAME') ?: 'a.pharmasee@gmail.com';
        $mail->Password   = getenv('MAIL_PASSWORD') ?: 'ujct nsjw ptzq ahnk';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = getenv('MAIL_PORT')     ?: 587;
        
        // Recipients
        $mail->setFrom(getenv('MAIL_USERNAME') ?: 'a.pharmasee@gmail.com', 'PharmAssist Support');
        $mail->addAddress($email, $fullname);
        $mail->addReplyTo(getenv('MAIL_USERNAME') ?: 'a.pharmasee@gmail.com', 'PharmAssist Support');
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'PharmAssist - Email Verification Code';
        $mail->Body    = createOTPEmail($fullname, $otp);
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Email OTP Error: " . $mail->ErrorInfo);
        return false;
    }
}
}

/**
 * Send ID verification notification (approval/rejection)
 */
function sendIDVerificationNotification($email, $fullname, $status, $rejection_reason = '') {
    require __DIR__ . '/../vendor/autoload.php';
    
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = getenv('MAIL_HOST')     ?: 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = getenv('MAIL_USERNAME') ?: 'a.pharmasee@gmail.com';
        $mail->Password   = getenv('MAIL_PASSWORD') ?: 'ujct nsjw ptzq ahnk';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = getenv('MAIL_PORT')     ?: 587;
        
        // Recipients
        $mail->setFrom(getenv('MAIL_USERNAME') ?: 'a.pharmasee@gmail.com', 'PharmAssist Support');
        $mail->addAddress($email, $fullname);
        $mail->addReplyTo(getenv('MAIL_USERNAME') ?: 'a.pharmasee@gmail.com', 'PharmAssist Support');
        
        // Content
        $mail->isHTML(true);
        
        // Subject and body based on status
        if ($status === 'approved') {
            $mail->Subject = 'PharmaSee - ID Verification Approved';
            $mail->Body    = createApprovalEmail($fullname);
        } elseif ($status === 'rejected') {
            $mail->Subject = 'PharmAssist - ID Verification Rejected';
            $mail->Body    = createRejectionEmail($fullname, $rejection_reason);
        } else {
            return false;
        }
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("ID Verification Email Error: " . $mail->ErrorInfo);
        return false;
    }
}

// ... rest of your HTML functions remain the same

/**
 * Create OTP verification email HTML
 */
function createOTPEmail($fullname, $otp) {
    $html = '
    <html>
        <body style="font-family: Arial, sans-serif; background-color: #f5f5f5; padding: 20px;">
            <div style="background-color: white; padding: 30px; border-radius: 8px; max-width: 500px; margin: 0 auto;">
                <h2 style="color: #7393A7; text-align: center;">Email Verification</h2>
                <p>Hello <strong>' . htmlspecialchars($fullname) . '</strong>,</p>
                <p>Thank you for registering with PharmAssist! Please verify your email address using the code below:</p>
                
                <div style="background-color: #f0f0f0; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: center; border: 2px dashed #7393A7;">
                    <p style="margin: 0; font-size: 12px; color: #999; text-transform: uppercase; letter-spacing: 2px;">Your Verification Code</p>
                    <h1 style="margin: 15px 0 0 0; color: #2c3e50; letter-spacing: 5px; font-size: 36px;">' . htmlspecialchars($otp) . '</h1>
                </div>
                
                <p style="color: #666; font-size: 14px;">This code will expire in <strong>10 minutes</strong>. Do not share this code with anyone.</p>
                
                <p>If you did not create this account, you can safely ignore this email.</p>
                
                <hr style="border: none; border-top: 1px solid #ddd; margin: 20px 0;">
                <p style="color: #999; font-size: 12px; text-align: center;">PharmAssist Team<br>
                <a href="https://pharmassist.site" style="color: #7393A7; text-decoration: none;">Visit our website</a></p>
            </div>
        </body>
    </html>';
    
    return $html;
}

/**
 * Create ID approval email HTML
 */
function createApprovalEmail($fullname) {
    $html = '
    <html>
        <body style="font-family: Arial, sans-serif; background-color: #f5f5f5; padding: 20px;">
            <div style="background-color: white; padding: 30px; border-radius: 8px; max-width: 500px; margin: 0 auto;">
                <h2 style="color: #7393A7;">✓ ID Verification Approved</h2>
                <p>Hello <strong>' . htmlspecialchars($fullname) . '</strong>,</p>
                <p>Great news! Your ID has been verified and approved. Your account is now fully activated.</p>
                
                <div style="background-color: #d4edda; padding: 15px; border-left: 4px solid #28a745; margin: 20px 0; border-radius: 4px;">
                    <p style="color: #155724; margin: 0;"><strong>You can now access all features of PharmaSee!</strong></p>
                </div>
                
                <p>You can now log in and start using PharmaSee services.</p>
                <p>If you have any questions or need assistance, please don\'t hesitate to contact our support team.</p>
                
                <hr style="border: none; border-top: 1px solid #ddd; margin: 20px 0;">
                <p style="color: #999; font-size: 12px;">PharmaSee Team</p>
            </div>
        </body>
    </html>';
    
    return $html;
}

/**
 * Create ID rejection email HTML
 */
function createRejectionEmail($fullname, $rejection_reason) {
    $html = '
    <html>
        <body style="font-family: Arial, sans-serif; background-color: #f5f5f5; padding: 20px;">
            <div style="background-color: white; padding: 30px; border-radius: 8px; max-width: 500px; margin: 0 auto;">
                <h2 style="color: #7393A7;">✗ ID Verification Rejected</h2>
                <p>Hello <strong>' . htmlspecialchars($fullname) . '</strong>,</p>
                <p>Unfortunately, your ID submission was not approved for the following reason:</p>
                
                <div style="background-color: #f8d7da; padding: 15px; border-left: 4px solid #dc3545; margin: 20px 0; border-radius: 4px;">
                    <p style="color: #721c24; margin: 0;"><strong>Reason:</strong></p>
                    <p style="color: #721c24; margin: 5px 0 0 0;">' . nl2br(htmlspecialchars($rejection_reason)) . '</p>
                </div>
                
                <p>Please resubmit a clearer photo of your valid ID. Your account will remain on hold until your ID is verified.</p>
                <p>If you have any questions or need assistance, please contact our support team.</p>
                
                <hr style="border: none; border-top: 1px solid #ddd; margin: 20px 0;">
                <p style="color: #999; font-size: 12px;">PharmAssist Team</p>
            </div>
        </body>
    </html>';
    
    return $html;
}

?>