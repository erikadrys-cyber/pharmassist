<?php
include 'config/connection.php';
session_start();

if (!isset($_SESSION['id']) || 
    ($_SESSION['role'] !== 'p_technician1' && 
     $_SESSION['role'] !== 'p_technician2' && 
     $_SESSION['role'] !== 'manager1' && 
     $_SESSION['role'] !== 'manager2')) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Always respond with JSON — the JS in transaction_action.php calls
// response.json(), so returning HTML <script> tags causes a JSON parse
// error which triggers the "An error occurred" alert even though the
// DB update already succeeded.
header('Content-Type: application/json');

$code   = trim($_GET['code'] ?? '');
$action = $_GET['action'] ?? '';

if ($code === '' || !in_array($action, ['approve', 'reject'], true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit();
}

if ($action === 'approve') {

    $stmt = $conn->prepare("UPDATE reservations SET status='Approved' WHERE code = ?");
    $stmt->bind_param("s", $code);

    if ($stmt->execute()) {
        $stmt->close();
        echo json_encode([
            'success' => true,
            'message' => "Reservation approved! Code: {$code}"
        ]);
    } else {
        $err = $stmt->error;
        $stmt->close();
        echo json_encode(['success' => false, 'message' => 'DB error: ' . $err]);
    }

} elseif ($action === 'reject') {

    $reason = trim($_POST['reason'] ?? $_GET['reason'] ?? '');

    if ($reason === '') {
        echo json_encode(['success' => false, 'message' => 'A rejection reason is required.']);
        exit();
    }

    $stmt = $conn->prepare("UPDATE reservations SET status='Rejected', remarks=? WHERE code = ?");
    $stmt->bind_param("ss", $reason, $code);

    if ($stmt->execute()) {
        $stmt->close();
        echo json_encode(['success' => true, 'message' => 'Reservation rejected.']);
    } else {
        $err = $stmt->error;
        $stmt->close();
        echo json_encode(['success' => false, 'message' => 'DB error: ' . $err]);
    }
}

$conn->close();
?>