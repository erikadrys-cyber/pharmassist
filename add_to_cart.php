<?php
include 'config/connection.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if ($_SESSION['role'] !== 'customer') {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

if (!isset($_SESSION['reservation_cart'])) {
    $_SESSION['reservation_cart'] = [];
}

// Get form data
$medicine_id = isset($_POST['medicine_id']) ? trim($_POST['medicine_id']) : '';
$medicine = isset($_POST['medicine']) ? trim($_POST['medicine']) : '';
$price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
$quantity = isset($_POST['quantity']) ? max(1, intval($_POST['quantity'])) : 1;
$requires_prescription = isset($_POST['requires_prescription']) ? trim($_POST['requires_prescription']) : 'no';
$discount_type = isset($_POST['discount_type']) ? trim($_POST['discount_type']) : 'none';

// Optional fields (not required for add to cart)
$image_path = isset($_POST['image_path']) ? trim($_POST['image_path']) : 'uploads/default.jpg';
$prescription_file = '';
$branch_id = null;
$branch_name = isset($_POST['branch_name']) ? trim($_POST['branch_name']) : '';
if (isset($_POST['branch_id']) && trim($_POST['branch_id']) !== '') {
    $branch_id = (int) $_POST['branch_id'];
}

if (!$branch_id && $branch_name !== '') {
    $stmt = $conn->prepare("SELECT branch_id FROM branches WHERE branch_name = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('s', $branch_name);
        $stmt->execute();
        $stmt->bind_result($resolvedBranchId);
        if ($stmt->fetch()) {
            $branch_id = (int) $resolvedBranchId;
        }
        $stmt->close();
    }
}

if (!$branch_id) {
    $branch_id = 1;
}

// Handle prescription file upload if provided
if (isset($_FILES['prescription']) && $_FILES['prescription']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['prescription'];
    $allowed_types = ['image/jpeg', 'image/png', 'application/pdf'];
    
    if (in_array($file['type'], $allowed_types) && $file['size'] <= 10 * 1024 * 1024) {
        $upload_dir = 'uploads/prescriptions/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_name = uniqid('prescription_') . '_' . basename($file['name']);
        $file_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($file['tmp_name'], $file_path)) {
            $prescription_file = $file_path;
        }
    }
}

// Validate required fields
if (empty($medicine) || $price <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid medicine data']);
    exit();
}

// Check if medicine already exists in cart for the same branch
$existingIndex = -1;
foreach ($_SESSION['reservation_cart'] as $index => $item) {
    if ($item['medicine_id'] === $medicine_id && ($item['branch_id'] ?? null) === $branch_id) {
        $existingIndex = $index;
        break;
    }
}

// Create cart item
$cart_item = [
    'medicine_id' => $medicine_id,
    'medicine' => $medicine,
    'price_per_piece' => $price,
    'quantity' => $quantity,
    'requires_prescription' => $requires_prescription,
    'prescription_file' => $prescription_file,
    'image_path' => $image_path,
    'discount_type' => $discount_type,
    'pieces_per_box' => 100,
    'unit_type' => 'piece',
    'branch_id' => $branch_id,
    'branch_name' => $branch_name
];

if ($existingIndex >= 0) {
    // Update existing item
    $_SESSION['reservation_cart'][$existingIndex]['quantity'] += $quantity;
    if (!empty($prescription_file)) {
        $_SESSION['reservation_cart'][$existingIndex]['prescription_file'] = $prescription_file;
    }
} else {
    // Add new item
    $_SESSION['reservation_cart'][] = $cart_item;
}

echo json_encode(['success' => true, 'message' => 'Added to cart successfully', 'cart_count' => count($_SESSION['reservation_cart'])]);
exit();
?>