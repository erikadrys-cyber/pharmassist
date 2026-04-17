<?php
// PHPMailer Autoloader - MUST be at the very top!
require 'vendor/autoload.php';

session_start();
include 'config/connection.php';
include 'helpers.php';

if (!function_exists('sendEmailOTP')) {
    error_log("CRITICAL: sendEmailOTP function NOT FOUND!");
    die("ERROR: Email function not loaded properly");
}
// ENABLE ALL ERROR REPORTING

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/registration_errors.log');

$error_message = '';
$success_message = '';

// Log everything
error_log("\n\n=== NEW PAGE LOAD ===");
error_log("REQUEST METHOD: " . $_SERVER['REQUEST_METHOD']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("POST request received");
    error_log("POST keys: " . implode(', ', array_keys($_POST)));

    // Don't rely on the submit button name being present; submitting via Enter
    // may omit it, which would skip validation entirely.
    error_log("STARTING VALIDATION");
        
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        error_log("Form Data - First_name: '$first_name' | Last_name: '$last_name' | Username: '$username' | Email: '$email'");
        error_log("Password length: " . strlen($password) . " | Match: " . ($password === $confirm_password ? 'YES' : 'NO'));
        error_log("Terms checkbox: " . (isset($_POST['terms']) ? 'CHECKED' : 'NOT CHECKED'));
        error_log("ID Photo: " . (isset($_FILES['id_photo']) ? 'EXISTS' : 'MISSING'));
        
        if (isset($_FILES['id_photo'])) {
            error_log("File details - Name: " . $_FILES['id_photo']['name'] . " | Size: " . $_FILES['id_photo']['size'] . " | Error: " . $_FILES['id_photo']['error']);
        }

        // Validation
        if (empty($first_name) || empty($last_name)) {
            $error_message = 'First name and last name are required.';
            error_log("ERROR: Empty first name or last name");
        } elseif (empty($username)) {
            $error_message = 'Username is required.';
            error_log("ERROR: Empty username");
        } elseif (!$email) {
            $error_message = 'Please enter a valid email address.';
            error_log("ERROR: Invalid email - raw input was: '" . ($_POST['email'] ?? '') . "'");
        } elseif (empty($password)) {
            $error_message = 'Password is required.';
            error_log("ERROR: Empty password");
        } elseif (strlen($password) < 8) {
            $error_message = 'Password must be at least 8 characters long.';
            error_log("ERROR: Password too short");
        } elseif (preg_match('/\s/', $password)) {
            $error_message = 'Password must not contain spaces.';
            error_log("ERROR: Password contains spaces");
        } elseif (!preg_match('/[a-z]/', $password)) {
            $error_message = 'Password must contain at least one lowercase letter.';
            error_log("ERROR: Password missing lowercase");
        } elseif (!preg_match('/[A-Z]/', $password)) {
            $error_message = 'Password must contain at least one uppercase letter.';
            error_log("ERROR: Password missing uppercase");
        } elseif (!preg_match('/[0-9]/', $password)) {
            $error_message = 'Password must contain at least one number.';
            error_log("ERROR: Password missing number");
        } elseif (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            $error_message = 'Password must contain at least one special character.';
            error_log("ERROR: Password missing special character");
        } elseif ($password !== $confirm_password) {
            $error_message = 'Passwords do not match.';
            error_log("ERROR: Passwords don't match");
        } elseif (!isset($_POST['terms'])) {
            $error_message = 'You must agree to the Terms and Conditions.';
            error_log("ERROR: Terms not checked");
        } elseif (!isset($_FILES['id_photo']) || $_FILES['id_photo']['error'] === UPLOAD_ERR_NO_FILE) {
            $error_message = 'Please upload your ID photo.';
            error_log("ERROR: No ID photo uploaded");
        } else {
            error_log("✓ All basic validations passed!");
            
            // Check if email already exists
            error_log("Checking if email exists in database...");
            $check_email_query = "SELECT user_id, email_verified FROM users WHERE email = ? LIMIT 1";
            $check_stmt = $conn->prepare($check_email_query);
            
            if (!$check_stmt) {
                error_log("ERROR preparing email check: " . $conn->error);
                $error_message = 'Database error: ' . $conn->error;
            } else {
                $check_stmt->bind_param('s', $email);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                $check_stmt->close();
                
                if ($check_result->num_rows > 0) {
                    $existing = $check_result->fetch_assoc();
                    $existing_user_id = (int)$existing['user_id'];
                    $existing_email_verified = (int)$existing['email_verified'];

                    if ($existing_email_verified === 1) {
                        $error_message = 'This email is already registered.';
                        error_log("ERROR: Email already exists (verified)");
                    } else {
                        // Email not verified yet — treat as not finalized.
                        // Clean up the pending record so user can register again and get a new code.
                        error_log("Pending/unverified email found. Deleting old pending user_id={$existing_user_id} and old OTP codes.");

                        $del_codes = $conn->prepare("DELETE FROM email_verification_codes WHERE user_id = ?");
                        if ($del_codes) {
                            $del_codes->bind_param('i', $existing_user_id);
                            $del_codes->execute();
                            $del_codes->close();
                        }

                        $del_user = $conn->prepare("DELETE FROM users WHERE user_id = ?");
                        if ($del_user) {
                            $del_user->bind_param('i', $existing_user_id);
                            $del_user->execute();
                            $del_user->close();
                        }

                        error_log("✓ Old pending registration removed; continuing with new registration");
                    }
                }

                if (empty($error_message)) {
                    error_log("✓ Email is available");
                    
                    // Check if username already exists
                    error_log("Checking if username exists in database...");
                    $check_username_query = "SELECT user_id, email_verified FROM users WHERE username = ? LIMIT 1";
                    $check_username_stmt = $conn->prepare($check_username_query);
                    
                    if (!$check_username_stmt) {
                        error_log("ERROR preparing username check: " . $conn->error);
                        $error_message = 'Database error: ' . $conn->error;
                    } else {
                        $check_username_stmt->bind_param('s', $username);
                        $check_username_stmt->execute();
                        $check_username_result = $check_username_stmt->get_result();
                        $check_username_stmt->close();
                        
                        if ($check_username_result->num_rows > 0) {
                            $existing_u = $check_username_result->fetch_assoc();
                            $existing_user_id = (int)$existing_u['user_id'];
                            $existing_email_verified = (int)$existing_u['email_verified'];

                            if ($existing_email_verified === 1) {
                                $error_message = 'This username is already taken.';
                                error_log("ERROR: Username already exists (verified account)");
                            } else {
                                // Username exists but tied to an unverified email — allow reuse by removing pending record.
                                error_log("Pending/unverified username found. Deleting old pending user_id={$existing_user_id} and old OTP codes.");

                                $del_codes = $conn->prepare("DELETE FROM email_verification_codes WHERE user_id = ?");
                                if ($del_codes) {
                                    $del_codes->bind_param('i', $existing_user_id);
                                    $del_codes->execute();
                                    $del_codes->close();
                                }

                                $del_user = $conn->prepare("DELETE FROM users WHERE user_id = ?");
                                if ($del_user) {
                                    $del_user->bind_param('i', $existing_user_id);
                                    $del_user->execute();
                                    $del_user->close();
                                }

                                error_log("✓ Old pending registration removed; continuing with new registration");
                            }
                        }

                        if (empty($error_message)) {
                            error_log("✓ Username is available");
                            
                            // Validate ID photo
                            error_log("Validating ID photo...");
                            $file_errors = validateIDPhotoUpload($_FILES['id_photo']);
                            
                            if (!empty($file_errors)) {
                                $error_message = implode(' ', $file_errors);
                                error_log("ERROR: File validation failed - " . implode(', ', $file_errors));
                            } else {
                                error_log("✓ File validation passed");
                                
                                // Save ID photo
                                error_log("Saving ID photo...");
                                $id_filename = saveIDPhoto($_FILES['id_photo']);
                                
                                if (!$id_filename) {
                                    $error_message = 'Failed to upload ID photo.';
                                    error_log("ERROR: Failed to save file");
                                } else {
                                    error_log("✓ File saved as: " . $id_filename);
                                    
                                    // Create account
                                    error_log("Creating user account...");
                                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                                    
                                    $query = "INSERT INTO users (first_name, last_name, email, username, password, id_photo, id_verified, email_verified, is_active) 
                                             VALUES (?, ?, ?, ?, ?, ?, 'pending', 0, 0)";
                                    $stmt = $conn->prepare($query);
                                    
                                    if (!$stmt) {
                                        error_log("ERROR: Prepare user insert failed: " . $conn->error);
                                        $error_message = 'Database error: ' . $conn->error;
                                    } else {
                                        $stmt->bind_param('ssssss', $first_name, $last_name, $email, $username, $hashed_password, $id_filename);
                                        
                                        if (!$stmt->execute()) {
                                            error_log("ERROR: Execute user insert failed: " . $stmt->error);
                                            $error_message = 'Failed to create user: ' . $stmt->error;
                                        } else {
                                            $user_id = $conn->insert_id;
                                            error_log("✓ User created successfully with ID: " . $user_id);
                                            
                                            // Generate OTP
                                            error_log("Generating OTP...");
                                            $email_otp = generateOTP();
                                            $expires_at = date('Y-m-d H:i:s', strtotime('+' . OTP_EXPIRY_MINUTES . ' minutes'));
                                            error_log("✓ OTP generated: " . $email_otp . " | Expires: " . $expires_at);
                                            
                                            // Save OTP to database
                                            error_log("Saving OTP to database...");
                                            $otp_query = "INSERT INTO email_verification_codes (user_id, code, expires_at) VALUES (?, ?, ?)";
                                            $otp_stmt = $conn->prepare($otp_query);
                                            
                                            if (!$otp_stmt) {
                                                error_log("ERROR: Prepare OTP insert failed: " . $conn->error);
                                                $error_message = 'Failed to save verification code: ' . $conn->error;
                                                
                                                // Delete user
                                                $del = $conn->prepare("DELETE FROM users WHERE user_id = ?");
                                                $del->bind_param('i', $user_id);
                                                $del->execute();
                                                $del->close();
                                                error_log("User deleted due to OTP save failure");
                                            } else {
                                                $otp_stmt->bind_param('iss', $user_id, $email_otp, $expires_at);
                                                
                                                if (!$otp_stmt->execute()) {
                                                    error_log("ERROR: Execute OTP insert failed: " . $otp_stmt->error);
                                                    $error_message = 'Failed to save verification code: ' . $otp_stmt->error;
                                                    
                                                    // Delete user
                                                    $del = $conn->prepare("DELETE FROM users WHERE user_id = ?");
                                                    $del->bind_param('i', $user_id);
                                                    $del->execute();
                                                    $del->close();
                                                    error_log("User deleted due to OTP save failure");
                                                } else {
                                                    error_log("✓ OTP saved successfully to database");
                                                    
                                                    // Send email
                                                    error_log("Sending verification email to: " . $email);
                                                    $full_name = trim($first_name . ' ' . $last_name);
                                                    if (sendEmailOTP($email, $full_name, $email_otp, 'email')) {
                                                        error_log("✓ Email sent successfully!");
                                                        
                                                        // Set session and redirect
                                                        $_SESSION['temp_user_id'] = $user_id;
                                                        $_SESSION['temp_email'] = $email;
                                                        $_SESSION['temp_first_name'] = $first_name;
                                                        $_SESSION['temp_last_name'] = $last_name;

                                                        error_log("✓ Session set, redirecting to verify_email.php");
                                                        header('Location: verify_email.php');
                                                        exit;
                                                    } else {
                                                        error_log("ERROR: Email failed to send");
                                                        $error_message = 'Email failed to send. Please contact support.';
                                                        
                                                        // Delete user
                                                        $del = $conn->prepare("DELETE FROM users WHERE user_id = ?");
                                                        $del->bind_param('i', $user_id);
                                                        $del->execute();
                                                        $del->close();
                                                        error_log("User deleted due to email send failure");
                                                    }
                                                }
                                                $otp_stmt->close();
                                            }
                                        }
                                        $stmt->close();
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        
        if (!empty($error_message)) {
            error_log("FINAL ERROR MESSAGE: " . $error_message);
        }
}

error_log("=== PAGE RENDERING ===\n");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up | PharmAssist</title>
    <link rel="stylesheet" href="plugins/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,200..800&family=Tinos:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
    <link rel="icon" href="website_icon/favicon.png" type="image/png" sizes="64x64">
    <style>
        /* Custom scrollbar for the register panel (.login.register-container).
           Avoid max-height < 100vh on this column: .background is 100vh, so a
           shorter left column exposes the default white body as a bottom strip. */
        .register-container::-webkit-scrollbar {
            width: 6px;
        }
        .register-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        .register-container::-webkit-scrollbar-thumb {
            background: #7393A7;
            border-radius: 10px;
        }
        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
        }
        .file-input-wrapper input[type=file] {
            position: absolute;
            left: -9999px;
        }
        .file-input-label {
            display: block;
            padding: 12px 15px;
            background-color: #f0f0f0;
            border: 2px dashed #7393A7;
            border-radius: 5px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            color: #666;
        }
        .file-input-label:hover {
            background-color: #e8f0f6;
            border-color: #5f7a8d;
        }
        .file-name {
            margin-top: 8px;
            font-size: 12px;
            color: #27ae60;
            font-weight: 500;
        }
        .preview-container {
            margin-top: 15px;
            padding: 12px;
            background-color: #f9f9f9;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            display: none;
            text-align: center;
        }
        .preview-container.show {
            display: block;
            animation: slideDown 0.3s ease;
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
        .preview-title {
            font-size: 12px;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .preview-image {
            max-width: 100%;
            max-height: 300px;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            object-fit: contain;
        }
        .preview-pdf {
            padding: 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 6px;
            color: white;
            font-size: 14px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .preview-pdf i {
            font-size: 48px;
            display: block;
            margin-bottom: 10px;
        }
        .remove-file-btn {
            margin-top: 10px;
            padding: 6px 12px;
            background-color: #ff6b6b;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            transition: background-color 0.2s ease;
        }
        .remove-file-btn:hover {
            background-color: #ff5252;
        }
        .password-strength {
            margin-top: 6px;
            font-size: 12px;
            font-weight: 600;
            line-height: 1.2;
        }
        .name-row {
            width: 100%;
            display: flex;
            gap: 12px;
        }
        .name-row .name-col {
            flex: 1;
            min-width: 0;
        }
        .name-row label {
            margin-left: 0;
        }
    </style>
</head>
<body>
  <div id="login-page">
    <div class="login register-container">
    <h2 class="login-title">Register</h2>
    <p class="notice">Create an account</p>

    <?php if (isset($error_message) && !empty($error_message)): ?>
        <div style="color: #721c24; margin-bottom: 15px; text-align: left; padding: 12px 15px; border: 1px solid #f5c6cb; border-radius: 5px; background-color: #f8d7da;">
            <i class="fas fa-exclamation-circle" style="margin-right: 8px;"></i>
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <form class="form-login" method="POST" enctype="multipart/form-data">
      <div class="name-row">
        <div class="name-col">
          <label for="first_name">First Name</label>
          <div class="input-first_name">
            <input type="text" name="first_name" id="first_name" placeholder="Enter your first name" required
                   value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>">
          </div>
        </div>
        <div class="name-col">
          <label for="last_name">Last Name</label>
          <div class="input-last_name">
            <input type="text" name="last_name" id="last_name" placeholder="Enter your last name" required
                   value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">
          </div>
        </div>
      </div>

      <label for="username">Username</label>
      <div class="input-username">
        <input type="text" name="username" id="username" placeholder="Enter your username" required
               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
      </div>

      <label for="email">Email</label>
      <div class="input-email">
        <input type="email" name="email" id="email" placeholder="Enter your email" required
               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
      </div>

      <label for="password">Password</label>
      <div class="input-password">
        <input type="password" name="password" id="password" placeholder="Enter your password" required minlength="8"
               pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z0-9])\S{8,}"
               title="At least 8 characters, with uppercase, lowercase, number, and special character (no spaces).">
        <button type="button" class="toggle-password-btn" onclick="togglePasswordVisibility('password', 'toggleIcon1')">
          <i id="toggleIcon1" class="far fa-eye"></i>
        </button>
      </div>
      <div class="password-strength" id="strengthText" aria-live="polite"></div>
      <small style="color: #999; display: block; margin-top: 6px;">
        Password must be at least 8 characters and include uppercase, lowercase, number, and special character (no spaces).
      </small>

      <label for="confirm_password">Confirm Password</label>
      <div class="input-password">
        <input type="password" name="confirm_password" id="confirm_password" placeholder="Re-enter your password" required minlength="8">
        <button type="button" class="toggle-password-btn" onclick="togglePasswordVisibility('confirm_password', 'toggleIcon2')">
          <i id="toggleIcon2" class="far fa-eye"></i>
        </button>
      </div>

      <label for="id_photo">Upload ID Photo (JPEG/PNG/PDF) *</label>
      <div class="file-input-wrapper">
        <input type="file" name="id_photo" id="id_photo" accept=".jpg,.jpeg,.png,.pdf" required onchange="updateFileName(this)">
        <label for="id_photo" class="file-input-label">
          <i class="fas fa-cloud-upload-alt" style="font-size: 24px; margin-bottom: 8px; display: block;"></i>
          Click to upload or drag & drop your ID photo (Max: 5MB)
        </label>
        <div class="file-name" id="fileName"></div>
      </div>

      <!-- Preview Container -->
      <div class="preview-container" id="previewContainer">
        <div class="preview-title">Preview</div>
        <div id="previewContent"></div>
        <button type="button" class="remove-file-btn" onclick="removeFile()">
          <i class="fas fa-trash-alt" style="margin-right: 5px;"></i>Remove File
        </button>
      </div>

      <small style="color: #999; display: block; margin-top: 8px;">Accepted formats: JPG, JPEG, PNG, PDF</small>
      
      <div class="checkbox">
        <input type="checkbox" name="terms" id="terms" required>
        <label for="terms" style="font-size: 12px;">I agree to the <a href="terms.php" target="_blank" style="font-size: 12px;">Terms and Conditions</a></label>
      </div>

      <button type="submit" name="register" style="width: 100%;">Sign up</button>
    </form>
    
    <div class="signup-link" style="text-align: center; margin: 10px 0 0 0;">
      <span style="font-size: 12px; color: #666;">Already have an account? <a href="login.php" style="display: inline; color: #7393A7; text-decoration: none; font-size: 12px; margin: 0;">Login here</a></span>
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

function updateFileName(input) {
  const fileName = document.getElementById('fileName');
  const previewContainer = document.getElementById('previewContainer');
  const previewContent = document.getElementById('previewContent');
  
  if (input.files && input.files[0]) {
    const file = input.files[0];
    const size = (file.size / 1024 / 1024).toFixed(2);
    fileName.textContent = `✓ ${file.name} (${size}MB)`;
    
    // Show preview based on file type
    const fileType = file.type;
    
    if (fileType.startsWith('image/')) {
      // For images, create a preview
      const reader = new FileReader();
      reader.onload = function(e) {
        previewContent.innerHTML = `<img src="${e.target.result}" alt="ID Preview" class="preview-image">`;
        previewContainer.classList.add('show');
      };
      reader.readAsDataURL(file);
    } else if (fileType === 'application/pdf') {
      // For PDF files, show a PDF icon
      previewContent.innerHTML = `
        <div class="preview-pdf">
          <i class="fas fa-file-pdf"></i>
          <div>${file.name}</div>
          <div style="font-size: 12px; margin-top: 8px; opacity: 0.9;">PDF Document Ready for Upload</div>
        </div>
      `;
      previewContainer.classList.add('show');
    }
  } else {
    fileName.textContent = '';
    previewContainer.classList.remove('show');
    previewContent.innerHTML = '';
  }
}

function removeFile() {
  const fileInput = document.getElementById('id_photo');
  const fileName = document.getElementById('fileName');
  const previewContainer = document.getElementById('previewContainer');
  const previewContent = document.getElementById('previewContent');
  
  fileInput.value = '';
  fileName.textContent = '';
  previewContainer.classList.remove('show');
  previewContent.innerHTML = '';
}

// Password strength indicator
const passwordInput = document.getElementById("password");
const strengthText = document.getElementById("strengthText");

if (passwordInput && strengthText) {
  const updatePasswordStrength = () => {
    const password = passwordInput.value;
    let strength = 0;

    if (password.length >= 8) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/[a-z]/.test(password)) strength++;
    if (/[0-9]/.test(password)) strength++;
    if (/[^A-Za-z0-9]/.test(password)) strength++;

    if (!password) {
      strengthText.textContent = "";
      strengthText.style.color = "";
      return;
    }

    if (strength <= 2) {
      strengthText.textContent = "Weak";
      strengthText.style.color = "red";
    } else if (strength === 3 || strength === 4) {
      strengthText.textContent = "Medium";
      strengthText.style.color = "orange";
    } else {
      strengthText.textContent = "Strong";
      strengthText.style.color = "green";
    }
  };

  passwordInput.addEventListener("input", updatePasswordStrength);
  updatePasswordStrength();
}
</script>
</body>
</html>
