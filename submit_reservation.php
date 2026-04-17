<?php
include 'config/connection.php';
session_start();
require_once 'check_role.php';

header('Content-Type: application/json; charset=UTF-8');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

function generateReservationCode($conn) {
    do {
        $year = date('Y');
        $randNum = strtoupper(substr(bin2hex(random_bytes(5)), 0, 8));
        $code = "RES-{$year}-{$randNum}";
        $check    = $conn->prepare('SELECT reservation_id FROM reservations WHERE code = ? LIMIT 1');
        $check->bind_param('s', $code);
        $check->execute();
        $check->store_result();
        $exists = $check->num_rows > 0;
        $check->close();
    } while ($exists);
    return $code;
}

$mode    = $_POST['mode'] ?? 'single';
$user_id = (int) $_SESSION['id'];

/* ── shared email lookup ── */
$email = $_SESSION['email'] ?? '';
if ($email === '') {
    $eq = $conn->prepare('SELECT email FROM users WHERE user_id = ?');
    if ($eq) {
        $eq->bind_param('i', $user_id);
        $eq->execute();
        $er = $eq->get_result()->fetch_assoc();
        $eq->close();
        $email = $er['email'] ?? '';
    }
}

/* ══════════════════════════════════════
   CART MODE
   One `code` shared across all items
══════════════════════════════════════ */
if ($mode === 'cart') {
    if (!isset($_SESSION['reservation_cart']) || empty($_SESSION['reservation_cart'])) {
        echo json_encode(['success' => false, 'message' => 'Cart is empty']);
        exit;
    }

    $first_name         = trim($_POST['first_name']    ?? '');
    $last_name          = trim($_POST['last_name']     ?? '');
    $contact            = trim($_POST['contact']       ?? '');
    $notes              = trim($_POST['notes']         ?? '');
    $cart_discount_type = trim($_POST['discount_type'] ?? 'none');

    // DEBUG: Log what was received
    error_log("DEBUG submit_reservation (cart mode) - first_name: '{$first_name}', last_name: '{$last_name}', contact: '{$contact}'");
    error_log("DEBUG POST data: " . json_encode($_POST));

    if ($first_name === '' || $last_name === '' || $contact === '') {
        echo json_encode(['success' => false, 'message' => 'First name, last name, and contact are required.']);
        exit;
    }

    if (in_array($cart_discount_type, ['pwd', 'elderly'], true) &&
        (empty($_FILES['id_upload']) || $_FILES['id_upload']['error'] !== UPLOAD_ERR_OK)) {
        echo json_encode(['success' => false, 'message' => 'A valid PWD/Elderly ID upload is required when claiming a discount.']);
        exit;
    }

    // Handle PWD/Elderly ID upload
    $id_upload_filename = '';
    if (!empty($_FILES['id_upload']) && $_FILES['id_upload']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/id_uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $ext = pathinfo($_FILES['id_upload']['name'], PATHINFO_EXTENSION);
        $id_upload_filename = 'ID_UPLOAD_' . time() . '_' . uniqid() . '.' . $ext;
        if (!move_uploaded_file($_FILES['id_upload']['tmp_name'], $upload_dir . $id_upload_filename)) {
            echo json_encode(['success' => false, 'message' => 'Could not save ID upload file.']);
            exit;
        }
    }

    // Generate ONE shared code for the entire cart checkout
    $shared_code = generateReservationCode($conn);

    $conn->begin_transaction();
    $successCount = 0;
    $errors       = [];

    foreach ($_SESSION['reservation_cart'] as $index => $item) {
        $medicine   = trim($item['medicine']       ?? '');
        $price      = floatval($item['price_per_piece'] ?? 0);
        $reserve_by = trim($item['reserve_by']     ?? 'piece');
        $multiplier = ($reserve_by === 'box') ? 100 : (($reserve_by === '10pieces') ? 10 : 1);
        $quantity   = (int)($item['quantity']      ?? 1) * $multiplier;
        $discount_type = $cart_discount_type;
        $branch_id  = isset($item['branch_id']) ? (int) $item['branch_id'] : null;

        if ($medicine === '' || $quantity < 1) {
            $errors[] = "Invalid item: $medicine";
            continue;
        }

        // Per-item prescription upload
        $item_prescription = trim($item['prescription_file'] ?? '');
        if (empty($item_prescription) &&
            isset($_FILES["prescription_$index"]) &&
            $_FILES["prescription_$index"]['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $ext = pathinfo($_FILES["prescription_$index"]['name'], PATHINFO_EXTENSION);
            $item_prescription = 'PRESC_' . time() . '_' . uniqid() . '.' . $ext;
            if (!move_uploaded_file($_FILES["prescription_$index"]['tmp_name'], $upload_dir . $item_prescription)) {
                $errors[] = "Could not save prescription for $medicine";
                continue;
            }
        }

        $time_slot = '';
        $sql = "INSERT INTO reservations
                    (code, user_id, first_name, last_name, medicine, price, quantity,
                     notes, contact_no, prescription, discount, email, id_upload, time_slot, branch_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $errors[] = 'Database error: ' . $conn->error;
            continue;
        }

        $stmt->bind_param(
            'sisssdissssssii',
            $shared_code,
            $user_id,
            $first_name,
            $last_name,
            $medicine,
            $price,
            $quantity,
            $notes,
            $contact,
            $item_prescription,
            $discount_type,
            $email,
            $id_upload_filename,
            $time_slot,
            $branch_id
        );

        if ($stmt->execute()) {
            $successCount++;
        } else {
            $errors[] = $stmt->error;
        }
        $stmt->close();
    }

    if ($successCount > 0) {
        $conn->commit();
        $_SESSION['reservation_cart'] = [];
        echo json_encode([
            'success' => true,
            'code'    => $shared_code,
            'message' => "$successCount reservation(s) submitted successfully"
        ]);
    } else {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to submit: ' . implode(', ', $errors)]);
    }
    exit;
}

/* ══════════════════════════════════════
   SINGLE MODE (medicines.php)
   One item = its own unique code
══════════════════════════════════════ */
$first_name = '';
$last_name  = '';
$contact    = trim($_POST['contact']  ?? '');
$medicine   = trim($_POST['medicine'] ?? '');
$price      = floatval($_POST['price']    ?? 0);
$quantity   = (int)($_POST['quantity']    ?? 1);
$notes      = trim($_POST['notes']        ?? '');
$discount_type = trim($_POST['discount_type'] ?? '');
$branch_id  = isset($_POST['branch_id']) ? (int) $_POST['branch_id'] : null;

if (!empty($_POST['first_name'])) {
    $first_name = trim($_POST['first_name']);
    $last_name  = trim($_POST['last_name'] ?? '');
} else {
    $fullname   = trim($_POST['fullname'] ?? '');
    $nameParts  = preg_split('/\s+/', $fullname);
    $first_name = $nameParts[0] ?? '';
    $last_name  = count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 1)) : '';
}

if ($first_name === '' || $contact === '') {
    echo json_encode(['success' => false, 'message' => 'Name and contact are required.']);
    exit;
}
if ($medicine === '' || $quantity < 1) {
    echo json_encode(['success' => false, 'message' => 'Invalid medicine or quantity.']);
    exit;
}

$prescription_filename = '';
if (!empty($_FILES['prescription']) && $_FILES['prescription']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = 'uploads/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
    $ext = pathinfo($_FILES['prescription']['name'], PATHINFO_EXTENSION);
    $prescription_filename = 'PRESC_' . time() . '_' . uniqid() . '.' . $ext;
    if (!move_uploaded_file($_FILES['prescription']['tmp_name'], $upload_dir . $prescription_filename)) {
        echo json_encode(['success' => false, 'message' => 'Could not save prescription file.']);
        exit;
    }
} elseif (!empty($_FILES['prescription']['error']) &&
          (int)$_FILES['prescription']['error'] !== UPLOAD_ERR_NO_FILE) {
    echo json_encode(['success' => false, 'message' => 'Prescription upload failed.']);
    exit;
}

$single_code = generateReservationCode($conn);

$time_slot = '';
$sql = "INSERT INTO reservations
            (code, user_id, first_name, last_name, medicine, price, quantity,
             notes, contact_no, prescription, discount, email, time_slot, branch_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit;
}

$stmt->bind_param(
    'siissdissssssi',
    $single_code,
    $user_id,
    $first_name,
    $last_name,
    $medicine,
    $price,
    $quantity,
    $notes,
    $contact,
    $prescription_filename,
    $discount_type,
    $email,
    $time_slot,
    $branch_id
);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'code' => $single_code]);
} else {
    echo json_encode(['success' => false, 'message' => $stmt->error]);
}

$stmt->close();
$conn->close();
?>