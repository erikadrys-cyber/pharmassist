<?php
ob_start();
session_start();
include 'config/connection.php';
require_once 'check_role.php';

requireLogin();
requireRole(['manager1','manager2', 'p_technician1','p_technician2', 'p_assistant1', 'p_assistant2']);

// ===================== HANDLE UPDATE (AJAX FIRST) =====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_medicine'])) {

    $medicine_id = intval($_POST['medicine_id']);
    $medicine_name = trim($_POST['medicine_name']);
    $batch_no = $_POST['batch_no'];
    $category = $_POST['category'];
    $price = $_POST['price'];
    $prescription_required = isset($_POST['prescription_required']) && $_POST['prescription_required'] !== ''
        ? $_POST['prescription_required']
        : 'no';
    $quantity = (int) $_POST['quantity'];
    $expiration_date = $_POST['expiration_date'];

    // get current image
    $fetch_stmt = $conn->prepare("SELECT image_path FROM medicine WHERE medicine_id = ?");
    $fetch_stmt->bind_param("i", $medicine_id);
    $fetch_stmt->execute();
    $current = $fetch_stmt->get_result()->fetch_assoc();
    $image_path = $current['image_path'];

    // upload new image
    if (isset($_FILES['medicineImage']) && $_FILES['medicineImage']['error'] === 0) {
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

        $fileName = time() . "_" . basename($_FILES["medicineImage"]["name"]);
        $targetFilePath = $targetDir . $fileName;

        move_uploaded_file($_FILES["medicineImage"]["tmp_name"], $targetFilePath);
        $image_path = $targetFilePath;
    }

    $stmt = $conn->prepare("
        UPDATE medicine 
        SET medicine_name=?, batch_no=?, category=?, price=?, prescription_required=?, quantity=?, expiration_date=?, image_path=? 
        WHERE medicine_id=?
    ");

    $stmt->bind_param(
        "sssdssssi",
        $medicine_name,
        $batch_no,
        $category,
        $price,
        $prescription_required,
        $quantity,
        $expiration_date,
        $image_path,
        $medicine_id
    );

    if ($stmt->execute()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Medicine updated successfully!']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }

    exit(); // 🔥 STOP HTML FROM LOADING
}

// ===================== NORMAL PAGE LOAD =====================
include 'admin_sidebar.php';

$currentRole = getCurrentUserRole();

$userInfo = getUserInfo($_SESSION['id']);
$branch_id = $userInfo['branch_id'] ?? null;

$branch_stmt = $conn->prepare("SELECT branch_name FROM branches WHERE branch_id = ?");
$branch_stmt->bind_param("i", $branch_id);
$branch_stmt->execute();
$branch_result = $branch_stmt->get_result()->fetch_assoc();

$branch_name = $branch_result['branch_name'] ?? 'Unknown';

// ===================== DELETE =====================
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);

    $conn->query("DELETE FROM branch_inventory WHERE medicine_id = $delete_id");
    $conn->query("DELETE FROM medicine WHERE medicine_id = $delete_id");

    echo "<script>alert('Medicine deleted successfully!'); window.location='med_inventory.php';</script>";
    exit();
}

// ===================== ADD =====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['update_medicine'])) {

    $medicine_name = trim($_POST['medicine_name']);
    $batch_no = $_POST['batch_no'];
    $category = $_POST['category'];
    $price = $_POST['price'];
    $branch = $branch_name;
    $prescription_required = isset($_POST['prescription_required']) && $_POST['prescription_required'] !== ''
        ? $_POST['prescription_required']
        : 'no';
    $quantity = (int) $_POST['quantity'];
    $expiration_date = $_POST['expiration_date'];
    $image_path = '';

    if (isset($_FILES['medicineImage']) && $_FILES['medicineImage']['error'] === 0) {
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

        $fileName = time() . "_" . basename($_FILES["medicineImage"]["name"]);
        $targetFilePath = $targetDir . $fileName;

        move_uploaded_file($_FILES["medicineImage"]["tmp_name"], $targetFilePath);
        $image_path = $targetFilePath;
    }

    if (!empty($medicine_name)) {

        $stmt = $conn->prepare("
          INSERT INTO medicine 
          (medicine_name, batch_no, category, price, branch, branch_id, prescription_required, quantity, expiration_date, image_path) 
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
          "sssdsissss",
          $medicine_name,
          $batch_no,
          $category,
          $price,
          $branch,
          $branch_id,
          $prescription_required,
          $quantity,
          $expiration_date,
          $image_path
        );

        if ($stmt->execute()) {
            header("Location: med_inventory.php");
            exit();
        }
    }
}

// ===================== FETCH =====================
$stmt = $conn->prepare("
    SELECT m.*, b.branch_name 
    FROM medicine m
    JOIN branches b ON m.branch_id = b.branch_id
    WHERE m.branch_id = ?
    ORDER BY m.created_at DESC
");

$stmt->bind_param("i", $branch_id);
$stmt->execute();
$medicines = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Dashboard | PharmAssist</title>

  <link rel="stylesheet" href="plugins/branches.css?v=2">
  <link rel="stylesheet" href="plugins/admin_sidebar.css?v=2">
  <link rel="stylesheet" href="plugins/footer.css">
  <link rel="stylesheet" href="plugins/carousel.css">
  <link rel="stylesheet" href="dashboard/dashboard.css?v=2">
  <link rel="icon" href="website_icon/favicon.png" type="image/png" sizes="64x64">

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <!-- Icons + Bootstrap -->
  <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,200..800&family=Tinos:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" crossorigin="anonymous"/>
  <link rel="stylesheet" href="dashboard/dashboard.css">

  <style>
    body {
    font-family: 'Tinos', serif;
    background-color: #E8ECF1;
    margin: 0;
}
    .home-content, .text, .section-title { color: white; font-size: 18px; font-weight: 600; font-family: 'Bricolage Grotesque', sans-serif; }

    .action-buttons {
      display: flex;
      gap: 5px;
      align-items: center;
      justify-content: center;
    }

    .action-buttons .btn {
      flex: 1;
      min-width: 70px;
      padding: 6px 12px !important;
      font-size: 13px !important;
      white-space: nowrap;
    }

    .action-buttons .btn-warning {
      background-color: #FBC687 !important;
      border: none !important;
      color: white !important;
    }

    .action-buttons .btn-warning:hover {
      background-color: #ff8c1a !important;
    }

    .action-buttons .btn-danger {
      background-color: #E5707E !important;
      border: none !important;
      color: white !important;
    }

    .action-buttons .btn-danger:hover {
      background-color: #a0271e !important;
    }

    /* Modal Styles */
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      animation: fadeIn 0.3s ease-in-out;
    }

    .modal.show {
      display: flex;
      justify-content: center;
      align-items: center;
    }

    .modal-content {
      background-color: #fefefe;
      padding: 30px;
      border-radius: 10px;
      width: 90%;
      max-width: 600px;
      max-height: 90vh;
      overflow-y: auto;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      animation: slideIn 0.3s ease-in-out;
    }

    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    @keyframes slideIn {
      from {
        transform: translateY(-50px);
        opacity: 0;
      }
      to {
        transform: translateY(0);
        opacity: 1;
      }
    }

    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      border-bottom: 1px solid #e0e0e0;
      padding-bottom: 15px;
    }

    .modal-header h2 {
      margin: 0;
      color: #7393a7;
      font-size: 24px;
    }

    .close-modal {
      font-size: 28px;
      font-weight: bold;
      color: #aaa;
      cursor: pointer;
      background: none;
      border: none;
      padding: 0;
      line-height: 1;
    }

    .close-modal:hover {
      color: #000;
    }

    .modal-body {
      margin: 20px 0;
    }

    .modal-footer {
      margin-top: 20px;
      display: flex;
      gap: 10px;
      justify-content: flex-end;
      padding-top: 15px;
      border-top: 1px solid #e0e0e0;
    }

    .image-preview {
      max-width: 100%;
      max-height: 200px;
      margin-top: 10px;
      border-radius: 5px;
    }

    .form-group {
      margin-bottom: 15px;
    }

    .form-label {
      font-weight: 500;
      margin-bottom: 5px;
      color: #333;
    }

    .form-control {
      border: 1px solid #ddd;
      border-radius: 5px;
      padding: 10px;
      width: 100%;
      font-size: 14px;
    }

    .form-control:focus {
      border-color: #7393a7;
      box-shadow: 0 0 5px rgba(115, 147, 167, 0.3);
      outline: none;
    }

    .radio-group {
      display: flex;
      gap: 20px;
    }

    .radio-group label {
      display: flex;
      align-items: center;
      gap: 5px;
      cursor: pointer;
    }

    .quantity-wrapper {
      display: flex;
      align-items: center;
      gap: 5px;
    }

    .quantity-wrapper button {
      width: 40px;
      height: 40px;
      border: 1px solid #7393a7;
      background-color: #7393a7;
      color: white;
      cursor: pointer;
      border-radius: 5px;
      font-weight: bold;
      font-size: 18px;
      transition: background-color 0.3s ease;
    }

    .quantity-wrapper button:hover {
      background-color: #5f7d8f;
      border-color: #5f7d8f;
    }

    .quantity-wrapper input {
      width: 60px;
      text-align: center;
      border: 1px solid #ddd;
      padding: 8px;
      border-radius: 5px;
    }

    .btn-modal-submit {
      background-color: #7393a7 !important;
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 5px;
      cursor: pointer;
      font-weight: 500;
    }

    .btn-modal-submit:hover {
      background-color: #5f7d8f !important;
    }

    .btn-modal-cancel {
      background-color: #999 !important;
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 5px;
      cursor: pointer;
      font-weight: 500;
    }

    .btn-modal-cancel:hover {
      background-color: #777 !important;
    }

    .branch-badge {
      background: #7393A7;
      color: white;
      padding: 4px 12px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
      margin-left: auto;
    }
  </style>
</head>
<body>
  <section class="home-section">
  <div class="home-content">
    <i class='bx bx-menu'></i>
    <span class="text">PharmAssist</span>
    <span class="branch-badge">📍 <?= $branch_name ?></span>
  </div>

  <div class="dashboard-container">
    <div class="dashboard-grid">
      <div>
        <div class="section-container">
          <div class="section-header d-flex justify-content-between align-items-center">
          <div>
        <h2 class="section-title">Medicine Inventory</h2>
        <p class="section-subtitle">Manage all available medicines in the pharmacy</p>
        </div>
      <div>
      <a href="generate_stock_report.php" target="_blank" class="btn btn-primary" style="background-color: #7393A7 !important; border:none; font-weight:500;">
      <i class="bi bi-file-earmark-pdf"></i> Generate Stock Report
    </a>
  </div>
</div>
          <table class="data-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Medicine Name</th>
                <th>Batch No</th>
                <th>Exp Date</th>
                <th>Category</th>
                <th>Price</th>
                <th>Branch</th>
                <th>Prescription</th>
                <th>Quantity</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php
                $count = 1; 
                if($medicines->num_rows > 0):
                    while($row = $medicines->fetch_assoc()):
              ?>
                  <tr>
                    <th scope="row"><?= $count++; ?></th>
                    <td><?= htmlspecialchars($row['medicine_name']); ?></td>
                    <td><?= htmlspecialchars($row['batch_no']); ?></td>
                    <td>
                      <?= $row['expiration_date'] ?>

                      <?php if (strtotime($row['expiration_date']) < time()) { ?>
                      <br><span style="color:red; font-weight:bold;">Expired</span>
                      <?php } ?>
                    </td>
                    <td><span class="prescription-badge"><?= htmlspecialchars($row['category']); ?></span></td>
                    <td>₱<?= number_format($row['price'], 2); ?></td>
                    <td><span class="branch-inline"><?= htmlspecialchars($row['branch_name']); ?></span></td>
                    <td><?= $row['prescription_required'] == 'yes' ? 'Required' : 'Not Required'; ?></td>
                    <td><?= htmlspecialchars($row['quantity']); ?></td>
                    <td>
                      <div class="action-buttons">
                        <button class="btn btn-warning btn-sm" onclick="openEditModal(<?= htmlspecialchars(json_encode($row)); ?>)">
                          <i class="bi bi-pencil"></i> Edit
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="confirmDelete(<?= $row['medicine_id']; ?>)">
                          <i class="bi bi-trash"></i> Delete
                        </button>
                      </div>
                    </td>
                  </tr>
              <?php 
                  endwhile;
                else:
              ?>
                  <tr>
                    <td colspan="8" class="text-center">No medicines available</td> 
                  </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div>
        <div class="section-container">
          <div class="section-header">
            <h2 class="section-title">Add New Medicine</h2>
            <p class="section-subtitle">Add a new medicine to the inventory</p>
          </div>

          <form method="POST" action="med_inventory.php" enctype="multipart/form-data">

            <div class="form-group">
              <label class="form-label" for="medicine_name">Medicine Name</label>
              <input type="text" class="form-control" id="medicine_name" name="medicine_name" required />
            </div>
            
            <div class="form-group">
              <label class="form-label" for="batch_no">Batch No</label>
              <input type="text" class="form-control" id="batch_no" name="batch_no" required />
            </div>

            <div class="form-group">
              <label>Expiration Date</label>
              <input type="date" name="expiration_date" required>
            </div>

            <div class="form-group">
              <label class="form-label" for="category">Category</label>
              <div class="dropdown">
                <select class="form-control" id="category" name="category" required>
                  <option disabled selected>Select category</option>
                  <option value="Antibiotics">Antibiotics</option>
                   <option value="Antiepileptic">Antiepileptic</option>
                  <option value="Antidepressants">Antidepressants</option>
                  <option value="Antihistamines">Antihistamines</option>
                  <option value="Antipsychotics">Antipsychotics</option>
                  <option value="Antivirals">Antivirals</option>
                  <option value="Blood-thinners">Blood-thinners</option>
                  <option value="Cardiovascular Drugs">Cardiovascular Drugs</option>
                  <option value="Diuretics">Diuretics</option>
                  <option value="Fever">Fever</option>
                  <option value="Fungal Infection">Fungal Infection</option>
                  <option value="Hypertension">Hypertension</option>
                  <option value="Hypnotics & Sedatives">Hypnotics & Sedatives</option>
                  <option value="Pain Relievers">Pain Relievers</option>
                  <option value="Rescue Inhalers">Rescue Inhalers</option>
                </select>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label" for="price">Price (₱)</label>
              <input type="number" step="0.01" min="0" class="form-control" id="price" name="price" required />
            </div>

            <div class="form-group">
              <label class="form-label" for="branch">Branch</label>
              <div class="dropdown">
                <input type="hidden" name="branch" value="<?= $branch_name ?>">
                <input type="text" class="form-control" value="<?= $branch_name ?>" disabled>
                  <option disabled selected>Select branch</option>
                </select>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Prescription Required</label>
              <div class="radio-group">
              <label>
                <input type="radio" id="prescription_yes" name="prescription_required" value="yes" required /> Yes
              </label>
              <label>
                <input type="radio" id="prescription_no" name="prescription_required" value="no" /> No
            </label>
          </div>
        </div>

            <div class="form-group">
              <label class="form-label" for="quantity">Quantity</label>
              <div class="quantity-wrapper">
                <button type="button" onclick="updateQuantity(-1)">−</button>
                <input type="number" id="quantity" name="quantity" value="1" min="0" required />
                <button type="button" onclick="updateQuantity(1)">+</button>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label" for="medicineImageInput">Upload Medicine Image</label>
              <input type="file" class="form-control" id="medicineImageInput" name="medicineImage" onchange="medicinePicture(event)" accept="image/*" required />
            </div>

            <div class="image-preview-container">
              <img id="medicineImagePreview" class="image-preview" alt="Medicine Image Preview" />
            </div>

            <button class="btn-primary" style="background-color: #7393a7 !important;" type="submit">Add Medicine</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Edit Modal -->
<div id="editModal" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <h2>Edit Medicine</h2>
      <button class="close-modal" onclick="closeEditModal()">&times;</button>
    </div>
    
    <form id="editForm" enctype="multipart/form-data" onsubmit="updateMedicine(event)">
      <div class="modal-body">
        <input type="hidden" id="edit_medicine_id" name="medicine_id">
        <input type="hidden" name="update_medicine" value="1">

        <div class="form-group">
          <label class="form-label" for="edit_medicine_name">Medicine Name</label>
          <input type="text" class="form-control" id="edit_medicine_name" name="medicine_name" required />
        </div>

        <div class="form-group">
          <label class="form-label" for="edit_batch_no">Batch No</label>
          <input type="text" class="form-control" id="edit_batch_no" name="batch_no" required />
        </div>

        <div class="form-group">
          <label class="form-label" for="edit_expiration_date">Expiration Date</label>
          <input type="date" class="form-control" id="edit_expiration_date" name="expiration_date" required />
        </div>

        <div class="form-group">
          <label class="form-label" for="edit_category">Category</label>
          <select class="form-control" id="edit_category" name="category" required>
            <option disabled selected>Select category</option>
            <option value="Antibiotics">Antibiotics</option>
            <option value="Antiepileptic">Antiepileptic</option>
            <option value="Antidepressants">Antidepressants</option>
            <option value="Antihistamines">Antihistamines</option>
            <option value="Antipsychotics">Antipsychotics</option>
            <option value="Antivirals">Antivirals</option>
            <option value="Blood-thinners">Blood-thinners</option>
            <option value="Cardiovascular Drugs">Cardiovascular Drugs</option>
            <option value="Diuretics">Diuretics</option>
            <option value="Fever">Fever</option>
            <option value="Fungal Infection">Fungal Infection</option>
            <option value="Hypertension">Hypertension</option>
            <option value="Hypnotics &amp; Sedatives">Hypnotics &amp; Sedatives</option>
            <option value="Pain Relievers">Pain Relievers</option>
            <option value="Rescue Inhalers">Rescue Inhalers</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="edit_price">Price (₱)</label>
          <input type="number" step="0.01" min="0" class="form-control" id="edit_price" name="price" required />
        </div>

        <div class="form-group">
          <label class="form-label">Branch</label>
          <input type="hidden" name="branch" value="<?= htmlspecialchars($branch_name) ?>">
          <input type="text" class="form-control" value="<?= htmlspecialchars($branch_name) ?>" disabled>
        </div>

        <div class="form-group">
          <label class="form-label">Prescription Required</label>
          <div class="radio-group">
            <label>
              <input type="radio" id="edit_prescription_yes" name="prescription_required" value="yes" required /> Yes
            </label>
            <label>
              <input type="radio" id="edit_prescription_no" name="prescription_required" value="no" required /> No
            </label>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="edit_quantity">Quantity</label>
          <div class="quantity-wrapper">
            <button type="button" onclick="updateModalQuantity(-1)">−</button>
            <input type="number" id="edit_quantity" name="quantity" min="0" required />
            <button type="button" onclick="updateModalQuantity(1)">+</button>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="edit_medicineImage">Upload Medicine Image (Optional)</label>
          <input type="file" class="form-control" id="edit_medicineImage" name="medicineImage" onchange="medicinePictureModal(event)" accept="image/*" />
        </div>

        <div class="image-preview-container">
          <img id="edit_medicineImagePreview" class="image-preview" alt="Medicine Image Preview" />
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn-modal-cancel" onclick="closeEditModal()">Cancel</button>
        <button type="submit" class="btn-modal-submit">Update Medicine</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEditModal(medicineData) {
  document.getElementById('edit_medicine_id').value = medicineData.medicine_id;
  document.getElementById('edit_medicine_name').value = medicineData.medicine_name;
  document.getElementById('edit_batch_no').value = medicineData.batch_no ?? '';
  document.getElementById('edit_expiration_date').value = medicineData.expiration_date ?? '';
  document.getElementById('edit_category').value = medicineData.category;
  document.getElementById('edit_price').value = medicineData.price;
  document.getElementById('edit_quantity').value = medicineData.quantity;
  
  // reset first (VERY IMPORTANT)
  document.getElementById('edit_prescription_yes').checked = false;
  document.getElementById('edit_prescription_no').checked = false;

// normalize value
  let presc = (medicineData.prescription_required || '').toString().trim().toLowerCase();

  if (presc === 'yes') {
    document.getElementById('edit_prescription_yes').checked = true;
  } else {
    document.getElementById('edit_prescription_no').checked = true;
  }

  if (medicineData.image_path) {
    document.getElementById('edit_medicineImagePreview').src = medicineData.image_path;
    document.getElementById('edit_medicineImagePreview').style.display = 'block';
  } else {
    document.getElementById('edit_medicineImagePreview').style.display = 'none';
  }

  document.getElementById('editModal').classList.add('show');
}

function closeEditModal() {
  document.getElementById('editModal').classList.remove('show');
  document.getElementById('editForm').reset();
}

function updateMedicine(event) {
  event.preventDefault();

  const form = document.getElementById('editForm');
  const formData = new FormData(form);

  const yesRadio = document.getElementById('edit_prescription_yes');
  const noRadio = document.getElementById('edit_prescription_no');

  if (yesRadio.checked) {
    formData.set('prescription_required', 'yes');
  } else if (noRadio.checked) {
    formData.set('prescription_required', 'no');
  } else {
    alert('Please choose if prescription is required.');
    return;
  }

  formData.set('update_medicine', '1');

  fetch('med_inventory.php', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      alert(data.message);
      closeEditModal();
      window.location.reload();
    } else {
      alert(data.message);
    }
  })
  .catch(error => {
    console.error('Error:', error);
    alert('An error occurred while updating the medicine.');
  });
}

window.onclick = function(event) {
  const modal = document.getElementById('editModal');
  if (event.target === modal) {
    closeEditModal();
  }
}

function confirmDelete(id) {
  if (confirm("Are you sure you want to delete this medicine? This will also remove it from the inventory and stock reports.")) {
    window.location.href = "med_inventory.php?delete_id=" + id;
  }
}

function medicinePicture(event) {
  const preview = document.getElementById("medicineImagePreview");
  const file = event.target.files[0];
  if (file) {
    const reader = new FileReader();
    reader.onload = (e) => {
      preview.src = e.target.result;
      preview.style.display = "block";
    };
    reader.readAsDataURL(file);
  }
}

function medicinePictureModal(event) {
  const preview = document.getElementById("edit_medicineImagePreview");
  const file = event.target.files[0];
  if (file) {
    const reader = new FileReader();
    reader.onload = (e) => {
      preview.src = e.target.result;
      preview.style.display = "block";
    };
    reader.readAsDataURL(file);
  }
}

function updateQuantity(change) {
  const qtyInput = document.getElementById("quantity");
  let value = parseInt(qtyInput.value) || 0;
  value += change;
  if (value < 0) value = 0;
  qtyInput.value = value;
}

function updateModalQuantity(change) {
  const qtyInput = document.getElementById("edit_quantity");
  let value = parseInt(qtyInput.value) || 0;
  value += change;
  if (value < 0) value = 0;
  qtyInput.value = value;
}
</script>

<script src="source/sidebar.js"></script>
<script src="source/homepage.js"></script>
</body>
</html>