<?php
session_start();
include 'config/connection.php';

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'];
    $new_password = $_POST['new_password'];
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("SELECT user_id, reset_token_expiration FROM password_resets WHERE reset_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $reset_request = $result->fetch_assoc();
        $user_id = $reset_request['user_id'];
        $expiration_time = $reset_request['reset_token_expiration'];

        if (strtotime($expiration_time) > time()) {
  
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $stmt->bind_param("si", $hashed_password, $user_id);

            if ($stmt->execute()) {
 
                $stmt = $conn->prepare("DELETE FROM password_resets WHERE reset_token = ?");
                $stmt->bind_param("s", $token);
                $stmt->execute();

                $success_message = "Password has been reset successfully. Redirecting to login...";
                
                // Redirect after 2 seconds
                header("refresh:2;url=login.php");
            } else {
                $error_message = "Failed to reset password. Please try again.";
            }
        } else {
            $error_message = "The reset link is invalid or has expired.";
        }
    } else {
        $error_message = "The reset link is invalid or has expired.";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="icon" href="website_icon/webicon.png" type="image/png" sizes="64x64">
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
        <form action="update_password.php" method="POST" class="bg-body-tertiary px-5 py-5 rounded-4 d-flex flex-column">
            <div class="mb-3 text-center">
                <h2>Reset Password</h2>
                <label for="email">Enter your new password</label>
            </div>
            
            <?php if (!empty($success_message)): ?>
                <div class="success-message">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="error-message">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <input type="hidden" name="token" value="<?php echo isset($_GET['token']) ? htmlspecialchars($_GET['token']) : ''; ?>"> 
            <input type="password" class="form-control" name="new_password" id="new_password" placeholder="New Password" required>
            <button type="submit" class="btn btn-primary mt-3">Reset Password</button>
        </form>
    </div>
</body>
</html>