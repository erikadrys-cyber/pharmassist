<?php
ob_start();
session_start();
include 'config/connection.php';
include 'helpers.php';

if (!isset($_SESSION['id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['id'];
$error_message = '';
$success_message = '';

// Get current user's ID status
$user_query = "SELECT email, fullname, id_verification_status, id_rejected_reason, id_photo FROM users WHERE user_id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user_result = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user_result) {
    header('Location: login.php');
    exit;
}

// Only allow resubmission if rejected or pending
if ($user_result['id_verification_status'] === 'approved') {
    header('Location: homepage.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['id_photo'])) {
        // Validate file
        $validation_errors = validateIDPhotoUpload($_FILES['id_photo']);
        
        if (!empty($validation_errors)) {
            $error_message = implode(', ', $validation_errors);
        } else {
            // Save the file
            $filename = saveIDPhoto($_FILES['id_photo']);
            
            if ($filename) {
                // Delete old photo if exists
                if ($user_result['id_photo'] && file_exists('uploads/id_photos/' . $user_result['id_photo'])) {
                    unlink('uploads/id_photos/' . $user_result['id_photo']);
                }
                
                // Update database
                $update_query = "UPDATE users SET id_photo = ?, id_verification_status = 'pending', id_rejected_reason = NULL WHERE user_id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param('si', $filename, $user_id);
                
                if ($update_stmt->execute()) {
                    $success_message = 'ID photo submitted successfully! Please wait for admin review.';
                    $update_stmt->close();
                    
                    // Refresh user data
                    $stmt = $conn->prepare($user_query);
                    $stmt->bind_param('i', $user_id);
                    $stmt->execute();
                    $user_result = $stmt->get_result()->fetch_assoc();
                    $stmt->close();
                    
                    // Update session
                    $_SESSION['id_verification_status'] = 'pending';
                } else {
                    $error_message = 'Failed to save ID photo. Please try again.';
                    $update_stmt->close();
                }
            } else {
                $error_message = 'Failed to upload file. Please try again.';
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
    <title>Resubmit ID | PharmAssist</title>
    <link rel="stylesheet" href="plugins/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,200..800&family=Tinos:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
    <link rel="icon" href="website_icon/favicon.png" type="image/png" sizes="64x64">
    <style>
        body {
            font-family: 'Tinos', serif;
        }

        .login-title {
            font-family: 'Bricolage Grotesque', sans-serif;
        }

        .status-badge {
            display: inline-block;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
            margin: 20px 0;
            font-size: 14px;
        }

        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }

        .reason-box {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            color: #856404;
            font-family: 'Tinos', serif;
        }

        .reason-box strong {
            color: #856404;
            font-weight: 600;
        }

        .file-upload-area {
            border: 2px dashed #7393A7;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background-color: #f9f9f9;
            margin: 20px 0;
        }

        .file-upload-area:hover {
            border-color: #5f7a8d;
            background-color: #f0f4f7;
        }

        .file-upload-area.dragover {
            border-color: #28a745;
            background-color: #d4edda;
        }

        .file-upload-area i {
            font-size: 48px;
            color: #7393A7;
            margin-bottom: 10px;
            display: block;
        }

        .file-upload-area p {
            margin: 10px 0;
            color: #555;
            font-family: 'Tinos', serif;
        }

        .file-upload-area .upload-text {
            font-weight: 600;
            color: #7393A7;
            font-family: 'Bricolage Grotesque', sans-serif;
        }

        .file-upload-area .upload-subtext {
            font-size: 12px;
            color: #999;
        }

        #id_photo {
            display: none;
        }

        .file-info {
            margin-top: 15px;
            padding: 12px;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 5px;
            color: #155724;
            display: none;
            font-family: 'Tinos', serif;
        }

        .file-info.show {
            display: block;
        }

        .submit-btn {
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
            margin-top: 20px;
        }

        .submit-btn:hover:not(:disabled) {
            background-color: #5f7a8d;
        }

        .submit-btn:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }

        .help-text {
            background-color: #e8f4f8;
            border-left: 4px solid #7393A7;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            color: #555;
            font-family: 'Tinos', serif;
            font-size: 14px;
        }

        .help-text strong {
            color: #333;
            font-weight: 600;
        }

        .help-text ul {
            margin: 10px 0 0 20px;
        }

        .help-text li {
            margin-bottom: 5px;
        }

        .back-link {
            margin-top: 20px;
            text-align: center;
        }

        .back-link a {
            color: #7393A7;
            text-decoration: none;
            font-size: 14px;
            font-family: 'Bricolage Grotesque', sans-serif;
        }

        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
  <div id="login-page">
      <div id="logo">
            <img src="website_icon/web_logo.png" alt="PharmAssist Logo" />
        </div>
    <div class="login" style="max-width: 600px;">
        
        <h2 class="login-title"><i class="fas fa-id-card" style="color: #7393A7;"></i> Resubmit ID Photo</h2>
        <p class="notice">Upload a new ID photo for verification</p>

        <?php if (!empty($error_message)): ?>
            <div style="color: #721c24; margin-bottom: 15px; text-align: left; padding: 12px 15px; border: 1px solid #f5c6cb; border-radius: 5px; background-color: #f8d7da; font-family: 'Tinos', serif;">
                <i class="fas fa-exclamation-circle" style="margin-right: 8px;"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div style="color: #155724; margin-bottom: 15px; text-align: left; padding: 12px 15px; border: 1px solid #c3e6cb; border-radius: 5px; background-color: #d4edda; font-family: 'Tinos', serif;">
                <i class="fas fa-check-circle" style="margin-right: 8px;"></i>
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <!-- Status Section -->
        <div>
            <p style="margin-bottom: 10px; color: #555; font-size: 14px;">
                <strong style="color: #333;">Current Status:</strong>
            </p>
            <span class="status-badge status-<?php echo htmlspecialchars($user_result['id_verification_status']); ?>">
                <?php echo strtoupper(htmlspecialchars($user_result['id_verification_status'])); ?>
            </span>
        </div>

        <!-- Rejection Reason (if rejected) -->
        <?php if ($user_result['id_verification_status'] === 'rejected' && $user_result['id_rejected_reason']): ?>
            <div class="reason-box">
                <p style="margin: 0;"><strong>Reason for Rejection:</strong></p>
                <p style="margin: 10px 0 0 0;"><?php echo nl2br(htmlspecialchars($user_result['id_rejected_reason'])); ?></p>
            </div>
        <?php endif; ?>

        <!-- Help Text -->
        <div class="help-text">
            <strong>Tips for successful ID verification:</strong>
            <ul>
                <li>Ensure the entire ID is visible in the photo</li>
                <li>Make sure the image is clear and well-lit</li>
                <li>Verify your ID is not expired</li>
                <li>Avoid glare and shadows on the ID</li>
                <li>File size must not exceed 5MB</li>
                <li>Accepted formats: JPG, JPEG, PNG, PDF</li>
            </ul>
        </div>

        <!-- Form -->
        <form method="POST" enctype="multipart/form-data">
            <label style="display: block; margin-bottom: 10px; color: #333; font-weight: 600; font-family: 'Bricolage Grotesque', sans-serif;">Upload ID Photo (JPEG/PNG/PDF) *</label>
            
            <div class="file-upload-area" id="uploadArea">
                <i class="fas fa-cloud-upload-alt"></i>
                <p class="upload-text">Click to upload or drag & drop</p>
                <p class="upload-subtext">Max. 5MB • JPG, JPEG, PNG, PDF</p>
            </div>

            <input type="file" name="id_photo" id="id_photo" accept=".jpg,.jpeg,.png,.pdf" required>

            <div class="file-info" id="fileInfo">
                <i class="fas fa-check-circle"></i> 
                <span id="fileName"></span> ready to upload
            </div>
            
            <button type="submit" class="submit-btn">Submit ID Photo</button>
        </form>

        <div class="back-link">
            <a href="homepage.php">← Back to Dashboard</a>
        </div>
    </div>
    <div class="background">
        <h1>Verify Your<br> Identity</h1>
    </div>
  </div>

<script>
const uploadArea = document.getElementById('uploadArea');
const fileInput = document.getElementById('id_photo');
const fileInfo = document.getElementById('fileInfo');
const fileName = document.getElementById('fileName');

// Click to upload
uploadArea.addEventListener('click', () => fileInput.click());

// Drag and drop
uploadArea.addEventListener('dragover', (e) => {
    e.preventDefault();
    uploadArea.classList.add('dragover');
});

uploadArea.addEventListener('dragleave', () => {
    uploadArea.classList.remove('dragover');
});

uploadArea.addEventListener('drop', (e) => {
    e.preventDefault();
    uploadArea.classList.remove('dragover');
    fileInput.files = e.dataTransfer.files;
    updateFileInfo();
});

// File input change
fileInput.addEventListener('change', updateFileInfo);

function updateFileInfo() {
    if (fileInput.files.length > 0) {
        const file = fileInput.files[0];
        fileName.textContent = file.name;
        fileInfo.classList.add('show');
    } else {
        fileInfo.classList.remove('show');
    }
}
</script>
</body>
</html>