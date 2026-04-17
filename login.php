<?php
session_start();
include 'config/connection.php';

if (isset($_SESSION['success_message'])) {
    echo "<script>alert('" . $_SESSION['success_message'] . "');</script>";
    unset($_SESSION['success_message']);
}
    
if (isset($_POST['action']) && $_POST['action'] === 'login') {

    header('Content-Type: application/json');

    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
        exit;
    }

    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if ($password === $user['password'] || password_verify($password, $user['password'])) {
        
            // Check email verification (for regular customers only)
            if ($user['role'] === 'customer' || $user['role'] === 'user') {
                if ($user['email_verified'] === 0) {
                    echo json_encode([
                        'status' => 'error', 
                        'message' => 'Please verify your email first. Check your inbox for the verification code.'
                    ]);
                    exit;
                }

                // Check ID verification status
                if ($user['id_verification_status'] === 'rejected') {
                    $_SESSION['id'] = $user['user_id']; 
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['id_verification_status'] = $user['id_verification_status'];
                    $_SESSION['id_rejected_reason'] = $user['id_rejected_reason'];

                    echo json_encode([
                        'status' => 'warning', 
                        'message' => 'Your ID verification was rejected. Please resubmit your ID for review.',
                        'redirect' => 'homepage.php'
                    ]);
                    exit;
                }
                elseif ($user['id_verification_status'] === 'pending') {
                    $_SESSION['id'] = $user['user_id']; 
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['id_verification_status'] = $user['id_verification_status'];

                    echo json_encode([
                        'status' => 'warning', 
                        'message' => 'Your account is pending ID verification. You can browse but cannot reserve medicines yet.',
                        'redirect' => 'homepage.php'
                    ]);
                    exit;
                }
            }

            // Login successful
            $_SESSION['id'] = $user['user_id']; 
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['id_verification_status'] = $user['id_verification_status'];

            // Determine redirect URL based on role
            $redirectUrl = 'homepage.php';
            
            // For staff (admin, pharmacist, technician, assistant) - go to dashboard
            if (in_array($user['role'], ['manager1', 'manager2', 'p_assistant1','p_assistant2', 'p_technician1', 'p_technician2'])) {
                $redirectUrl = 'staff_dashboard.php';
            }
            if (in_array($user['role'], ['ceo'])) {
                $redirectUrl = 'super_admin_dashboard.php';
            }
            echo json_encode([
                'status' => 'success',
                'message' => 'Login successful! Redirecting...',
                'redirect' => $redirectUrl
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Incorrect password.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Username not found.']);
    }

    $stmt->close();
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | PharmAssist</title>
    <link rel="stylesheet" href="plugins/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque&family=Tinos:wght@400;700&display=swap" rel="stylesheet">
    <link rel="icon" href="website_icon/favicon.png" type="image/png" sizes="64x64">
</head>
<body>
    <div id="login-page">
        <div id="logo">
            <img src="website_icon/web_logo.png" alt="PharmAssist Logo" />
        </div>

        <div class="login">
            <h2 class="login-title">Login</h2>
            <p class="notice">Enter your details to sign in</p>

            <form class="form-login" method="POST">
                <label for="username">Username</label>
                <div class="input-username">
                    <i class="fa-solid fa-user icon"></i>
                    <input type="text" name="username" id="username" placeholder="Enter your username" required>
                </div>

               <label for="password">Password</label>
                <div class="input-password" style="position: relative;">
                <i class="fas fa-lock icon"></i>
                <input type="password" name="password" id="password" placeholder="Enter your password" required style="padding-right: 40px;">
                    <button type="button" class="toggle-password-btn" onclick="togglePasswordVisibility('password', 'toggleIcon')">
                    <i id="toggleIcon" class="far fa-eye"></i>
                </button>
                </div>

                <button type="submit">Sign in</button>
            </form>

            <div class="form-links">
                <a href="forgot_password.php" class="forgot-password">Forgot your password?</a>
            </div>

            <div class="signup-link" style="text-align: center; margin: 10px 0 0 0;">
                <span style="font-size: 12px; color: #666;">Don't have an account?
                <a href="register.php" style="display: inline; color: #7393A7; text-decoration: none; font-size: 12px; margin: 0;">Sign up here</a>
            </div>
        </div>
        
        <div class="background">
            <h1>Your Medicine,<br> Minutes Away</h1>
        </div>
    </div>

<script>
    function togglePasswordVisibility(inputId, iconId) {
    const passwordInput = document.getElementById(inputId);
    const toggleIcon = document.getElementById(iconId);
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
}
    document.querySelector('.form-login').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'login');
        
        fetch('login.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                window.location.href = data.redirect;
            } else if (data.status === 'warning') {
                alert(data.message);
                if (data.redirect) {
                    window.location.href = data.redirect;
                }
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Fetch Error:', error);
            alert('An error occurred. Please try again.');
        });
    });
</script>
</body>
</html>