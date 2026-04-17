<?php
session_start();
include 'config/connection.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Complete | PharmAssist</title>
    <link rel="stylesheet" href="plugins/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,200..800&family=Tinos:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
    <link rel="icon" href="website_icon/favicon.png" type="image/png" sizes="64x64">
    <style>
        .success-container {
            text-align: center;
        }
        .success-icon {
            font-size: 80px;
            color: #28a745;
            margin: 30px 0;
            animation: slideDown 0.6s ease-out;
        }
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .success-message {
            color: #333;
            font-size: 18px;
            margin: 20px 0;
            line-height: 1.6;
        }
        .info-box {
            background-color: #e8f5e9;
            border-left: 4px solid #28a745;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
            text-align: left;
            color: #2e7d32;
        }
        .info-box h3 {
            margin: 0 0 10px 0;
            color: #1b5e20;
        }
        .info-box ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        .info-box li {
            margin: 8px 0;
        }
        .btn-container {
            margin-top: 30px;
        }
        .btn-login {
            background: linear-gradient(135deg, #7393A7 0%, #5f7a8d 100%);
            color: white;
            padding: 15px 40px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            font-size: 16px;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(115, 147, 167, 0.3);
            color: white;
            text-decoration: none;
        }
    </style>
</head>
<body>
  <div id="login-page">
      <div id="logo">
            <img src="website_icon/web_logo.png" alt="PharmAssist Logo" />
        </div>
    <div class="login success-container">
        <div class="success-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        
        <h2 class="login-title">Registration Successful!</h2>
        
        <div class="success-message">
            <p>Welcome to PharmAssist!</p>
            <p>Your account has been created and verified.</p>
        </div>

        <div class="info-box">
            <h3><i class="fas fa-info-circle"></i> What's Next?</h3>
            <ul>
                <li><strong>ID Review:</strong> Your submitted ID is now under review by our manager.</li>
                <li><strong>Email Notification:</strong> You'll receive an email once your ID is approved or if we need more information.</li>
                <li><strong>Account Status:</strong> You can log in, but your account features may be limited until ID verification is complete.</li>
            </ul>
        </div>

        <div class="info-box" style="background-color: #fff3e0; border-left-color: #ff9800; color: #e65100;">
            <h3 style="color: #bf360c;"><i class="fas fa-clock"></i> Verification Timeline</h3>
            <p style="margin: 0;">Our team typically reviews ID submissions within 24 hours during business days. You'll be notified via email as soon as your verification is complete.</p>
        </div>

        <div class="btn-container">
            <a href="login.php" class="btn-login">
                <i class="fas fa-sign-in-alt" style="margin-right: 8px;"></i> Go to Login
            </a>
        </div>

        <div class="signup-link" style="margin-top: 30px;">
            <span style="font-size: 12px; color: #999;">
                Questions? <a href="contact.php" style="color: #7393A7; text-decoration: none;">Contact Support</a>
            </span>
        </div>
    </div>
    <div class="background">
        <h1>Welcome to<br> PharmAssist!</h1>
    </div>
  </div>
</body>
</html>