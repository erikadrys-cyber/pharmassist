<?php
/**
 * EMAIL DIAGNOSTIC TEST
 * This file helps identify why emails are failing
 * Visit: http://localhost/new_pharmasee/test_email_diagnostic.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

date_default_timezone_set('Asia/Manila');

// Load PHPMailer
require __DIR__ . '/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

echo "<h1>📧 Email Diagnostic Test</h1>";
echo "<hr>";

// TEST 1: Check PHP version
echo "<h2>1️⃣  PHP & OpenSSL Check</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "OpenSSL Support: " . (extension_loaded('openssl') ? '✅ YES' : '❌ NO') . "<br>";
echo "cURL Support: " . (extension_loaded('curl') ? '✅ YES' : '❌ NO') . "<br>";
echo "Sockets Support: " . (extension_loaded('sockets') ? '✅ YES' : '❌ NO') . "<br>";

// TEST 2: Check file locations
echo "<h2>2️⃣  File Location Check</h2>";

$files_to_check = [
    'config/mail.php' => __DIR__ . '/config/mail.php',
    'helpers.php' => __DIR__ . '/helpers.php',
    'vendor/autoload.php' => __DIR__ . '/vendor/autoload.php',
];

foreach ($files_to_check as $name => $path) {
    $exists = file_exists($path);
    $status = $exists ? '✅ FOUND' : '❌ MISSING';
    echo "$name: $status (Path: $path)<br>";
}

// TEST 3: Try to load PHPMailer
echo "<h2>3️⃣  PHPMailer Check</h2>";
try {
    echo "✅ PHPMailer loaded successfully<br>";
} catch (Exception $e) {
    echo "❌ PHPMailer load error: " . $e->getMessage() . "<br>";
    die();
}

// TEST 4: Check mail config
echo "<h2>4️⃣  Mail Configuration Check</h2>";
try {
    $config_path = __DIR__ . '/config/mail.php';
    if (file_exists($config_path)) {
        $config = include $config_path;
        echo "✅ Config loaded successfully<br>";
        echo "Host: " . $config['host'] . "<br>";
        echo "Port: " . $config['port'] . "<br>";
        echo "Username: " . substr($config['username'], 0, 5) . "***@gmail.com<br>";
        echo "Verify SSL: " . ($config['verify_ssl'] === false ? 'Disabled ✅' : 'Enabled') . "<br>";
    } else {
        echo "❌ config/mail.php not found!<br>";
    }
} catch (Exception $e) {
    echo "❌ Config load error: " . $e->getMessage() . "<br>";
}

// TEST 5: Test SMTP connection
echo "<h2>5️⃣  SMTP Connection Test</h2>";
try {
    $config = include __DIR__ . '/config/mail.php';
    
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $config['host'];
    $mail->SMTPAuth = true;
    $mail->Username = $config['username'];
    $mail->Password = $config['password'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = $config['port'];
    $mail->Timeout = 10;
    
    // Apply SSL bypass
    if (!empty($config['verify_ssl']) && $config['verify_ssl'] === false) {
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true
            ]
        ];
        echo "✅ SSL verification disabled<br>";
    }
    
    // Try to connect (don't send)
    if (@$mail->smtpConnect()) {
        echo "✅ SMTP Connection Successful!<br>";
        $mail->smtpClose();
    } else {
        echo "❌ SMTP Connection Failed!<br>";
        echo "Error: " . $mail->ErrorInfo . "<br>";
    }
    
} catch (Exception $e) {
    echo "❌ SMTP Test Error: " . $e->getMessage() . "<br>";
}

// TEST 6: Send test email
echo "<h2>6️⃣  Test Email Send</h2>";
echo "<form method='POST'>";
echo "Test Email: <input type='email' name='test_email' required><br>";
echo "Test Name: <input type='text' name='test_name' value='Test User'><br>";
echo "<button type='submit'>Send Test Email</button>";
echo "</form>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_email'])) {
    try {
        $config = include __DIR__ . '/config/mail.php';
        
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $config['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['username'];
        $mail->Password = $config['password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $config['port'];
        $mail->Timeout = 15;
        
        // Apply SSL bypass
        if (!empty($config['verify_ssl']) && $config['verify_ssl'] === false) {
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer'       => false,
                    'verify_peer_name'  => false,
                    'allow_self_signed' => true
                ]
            ];
        }
        
        $mail->setFrom($config['from_email'], $config['from_name']);
        $mail->addAddress($_POST['test_email'], $_POST['test_name']);
        $mail->isHTML(true);
        $mail->Subject = 'PharmAssist Test Email';
        $mail->Body = '<h1>Test Email</h1><p>This is a test email from PharmAssist diagnostic tool.</p>';
        
        if ($mail->send()) {
            echo "<div style='background-color: #d4edda; padding: 15px; border-radius: 5px; margin-top: 10px;'>";
            echo "✅ <strong>Email sent successfully!</strong><br>";
            echo "To: " . htmlspecialchars($_POST['test_email']) . "<br>";
            echo "</div>";
        } else {
            echo "<div style='background-color: #f8d7da; padding: 15px; border-radius: 5px; margin-top: 10px;'>";
            echo "❌ <strong>Email failed to send!</strong><br>";
            echo "Error: " . $mail->ErrorInfo . "<br>";
            echo "</div>";
        }
    } catch (Exception $e) {
        echo "<div style='background-color: #f8d7da; padding: 15px; border-radius: 5px; margin-top: 10px;'>";
        echo "❌ <strong>Exception:</strong><br>";
        echo $e->getMessage() . "<br>";
        echo "</div>";
    }
}

echo "<hr>";
echo "<p style='color: #999;'>Last updated: " . date('Y-m-d H:i:s') . " (Manila Time)</p>";

?>
<style>
body { font-family: Arial; margin: 20px; }
h1, h2 { color: #333; }
input, button { padding: 8px; margin: 5px; }
button { background-color: #7393A7; color: white; border: none; border-radius: 5px; cursor: pointer; }
button:hover { background-color: #5f7a8d; }
</style>