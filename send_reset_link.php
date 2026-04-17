<?php
session_start();

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// DB connection (guarded so the page doesn't fatal if MySQL is down)
$conn = null;
try {
    include 'config/connection.php';
} catch (Throwable $e) {
    $error_message = "Database connection failed. Please start MySQL in XAMPP and try again.";
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/vendor/autoload.php';

$success_message = '';
$error_message = $error_message ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$conn instanceof mysqli) {
        if ($error_message === '') {
            $error_message = "Database connection failed. Please start MySQL in XAMPP and try again.";
        }
    } else {
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $user_id = $user['user_id'];

            $token = bin2hex(random_bytes(50));
            $expiration = date("Y-m-d H:i:s", strtotime('+5 min'));

            $insertStmt = $conn->prepare(
                "INSERT INTO password_resets (user_id, reset_token, reset_token_expiration) VALUES (?, ?, ?)"
            );
            $insertStmt->bind_param("iss", $user_id, $token, $expiration);

            if (!$insertStmt->execute()) {
                $error_message = "Unable to create reset request. Please try again.";
            } else {
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'a.pharmasee@gmail.com';
                    $mail->Password = 'ovep bfuv ywcm gvse';
                    $mail->Port = 587;
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->SMTPAutoTLS = true;
                    $mail->CharSet = 'UTF-8';

                    // Common fix for Windows/XAMPP local dev where CA bundle is missing.
                    // Remove once your PHP/OpenSSL CA certs are configured properly.
                    $mail->SMTPOptions = [
                        'ssl' => [
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                            'allow_self_signed' => true,
                        ],
                    ];

                    $mail->setFrom('a.pharmasee@gmail.com', 'PharmAssist');
                    $mail->addAddress($email);

                    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                    $resetUrl = $scheme . '://' . $host . '/PharmAssist/update_password.php?token=' . urlencode($token);

                    $mail->isHTML(true);
                    $mail->Subject = 'Password Reset Request';
                    $mail->Body = "
                        <p>Click the link below to reset your password. This link is valid for 5 minutes:</p>
                        <a href='{$resetUrl}'>Reset Password</a>
                    ";

                    $mail->send();

                    $success_message = "A password reset link has been sent to your email. Redirecting to login...";
                    header("refresh:3;url=login.php");
                } catch (Exception $e) {
                    $error_message = "Message could not be sent. Mailer Error: " . ($mail->ErrorInfo ?: $e->getMessage());
                }
            }

            $insertStmt->close();
        } else {
            $error_message = "No account found with that email address.";
        }

        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forgot Password | PharmAssist</title>
  <link rel="icon" href="website_icon/favicon.png" type="image/png" sizes="64x64">
  <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
      rel="stylesheet"
      integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
      crossorigin="anonymous"
  />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="plugins/forgot_password.css" />
  <link rel="icon" href="website_icon/favicon.png" type="image/png" sizes="64x64">
</head>
<body style="background-image: url('img/loginbg.jpg');">
  <div class="container-fluid p-0 m-0 vh-100 d-flex justify-content-center align-items-center">
    <form action="send_reset_link.php" method="POST" class="bg-body-tertiary px-5 py-5 rounded-4 d-flex flex-column">
        <div class="mb-3 text-center">
           <h2>Forgot Password</h2>
           <label for="email">Enter your email to reset password</label>
        </div>
        
        <?php if (!empty($success_message)): ?>
            <div class="success-message">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <input type="email" class="form-control" name="email" id="email" autocomplete="off" placeholder="Email" required>
        <button type="submit" class="btn btn-primary mt-3">Send Reset Link</button>
        <a href="login.php" class="text-center mt-3" target="_self">Back to login</a>
    </form>
  </div>
</body>
</html>