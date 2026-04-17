<?php
include 'config/connection.php';
session_start();

if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION['role'] !== 'ceo') {
    header("Location: homepage.php");
    exit();
}

$check_branches = $conn->query("SELECT COUNT(*) as count FROM branches");
$count = $check_branches->fetch_assoc()['count'];

if ($count == 0) {
    $existing_branches = [
        ['Branch 1', '123 Main Street, Downtown District, Makati City'],
        ['Branch 2', '456 Parian Road, Parian, Taguig City'],
        ['Branch 3', '789 National Highway, Crossing, Quiapo Manila']
    ];
    
    foreach ($existing_branches as $branch) {
        $stmt = $conn->prepare("INSERT INTO branches (branch_name, branch_address) VALUES (?, ?)");
        $stmt->bind_param("ss", $branch[0], $branch[1]);
        $stmt->execute();
        $stmt->close();
    }
}

// Add Branch
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $branch_name = trim($_POST['branch_name']);
    $branch_address = trim($_POST['branch_address']);
    
    if (!empty($branch_name) && !empty($branch_address)) {
        $stmt = $conn->prepare("INSERT INTO branches (branch_name, branch_address) VALUES (?, ?)");
        $stmt->bind_param("ss", $branch_name, $branch_address);
        
        if ($stmt->execute()) {
            $success_message = "Branch added successfully!";
        } else {
            $error_message = "Error adding branch: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Delete Branch
if (isset($_GET['delete'])) {
    $branch_id = (int)$_GET['delete'];
    
    // Check if branch has medicines
    $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM medicine WHERE branch = (SELECT branch_name FROM branches WHERE branch_id = ?)");
    $check_stmt->bind_param("i", $branch_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result()->fetch_assoc();
    
    if ($result['count'] > 0) {
        $error_message = "Cannot delete branch. It has " . $result['count'] . " medicine(s) associated with it.";
    } else {
        $delete_stmt = $conn->prepare("DELETE FROM branches WHERE branch_id = ?");
        $delete_stmt->bind_param("i", $branch_id);
        
        if ($delete_stmt->execute()) {
            $success_message = "Branch deleted successfully!";
        } else {
            $error_message = "Error deleting branch: " . $delete_stmt->error;
        }
        $delete_stmt->close();
    }
    $check_stmt->close();
}

// Update Branch
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $branch_id = (int)$_POST['branch_id'];
    $branch_name = trim($_POST['branch_name']);
    $branch_address = trim($_POST['branch_address']);
    
    if (!empty($branch_name) && !empty($branch_address)) {
        // Get old branch name to update medicines
        $get_old_name = $conn->prepare("SELECT branch_name FROM branches WHERE branch_id = ?");
        $get_old_name->bind_param("i", $branch_id);
        $get_old_name->execute();
        $old_branch = $get_old_name->get_result()->fetch_assoc();
        $old_name = $old_branch['branch_name'];
        
        // Update branch
        $stmt = $conn->prepare("UPDATE branches SET branch_name = ?, branch_address = ? WHERE branch_id = ?");
        $stmt->bind_param("ssi", $branch_name, $branch_address, $branch_id);
        
        if ($stmt->execute()) {
            // Update medicine table if branch name changed
            if ($old_name !== $branch_name) {
                $update_medicines = $conn->prepare("UPDATE medicine SET branch_name = ? WHERE branch_id = ?");
                $update_medicines->bind_param("si", $branch_name, $branch_id);
                $update_medicines->execute();
                $update_medicines->close();
            }
            
            $success_message = "Branch updated successfully!";
        } else {
            $error_message = "Error updating branch: " . $stmt->error;
        }
        $stmt->close();
        $get_old_name->close();
    }
}

// Fetch all branches
$branches = $conn->query("SELECT * FROM branches ORDER BY branch_id ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Manage Branches | PharmAssist</title>

  <link rel="stylesheet" href="plugins/admin_sidebar.css?v=2">
  <link rel="stylesheet" href="plugins/footer.css">
  <link rel="icon" href="website_icon/favicon.png" type="image/png" sizes="64x64">

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,200..800&family=Tinos:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet"/>
  <link rel="icon" href="website_icon/favicon.png" type="image/png" sizes="64x64">
  <style>
    body {
            font-family: 'Tinos', serif;
            background-color: #E8ECF1;
            margin: 0;
        }
    .home-content, .text, .section-title { color: white; font-size: 18px; font-weight: 600; font-family: 'Bricolage Grotesque', sans-serif; }

    .home-section {
      position: relative;
      background: #E8ECF1;
      min-height: 100vh;
      top: 0;
      left: 78px;
      width: calc(100% - 78px);
      transition: all 0.5s ease;
      z-index: 2;
    }

    .sidebar.open ~ .home-section {
      left: 250px;
      width: calc(100% - 250px);
    }

    /* Top Bar */
.home-section .home-content {
  height: 60px;
  display: flex;
  align-items: center;
  background: linear-gradient(to right, #7393A7, #B5CFD8);
  padding: 0 20px;
  position: fixed;
  top: 0;
  left: 250px;
  width: calc(100% - 250px);
  transition: all 0.4s ease;
  z-index: 90;
}

.sidebar.close ~ .home-section .home-content {
  left: 78px;
  width: calc(100% - 78px);
}

    .branch-management-container {
      padding: 30px;
      max-width: 1400px;
      margin: 0 auto;
    }

    .page-header {
      background: white;
      padding: 25px 30px;
      border-radius: 12px;
      margin-bottom: 30px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
      margin-top: 60px;
    }

    .page-header h1 {
      color: #7393A7;
      font-size: 2rem;
      font-weight: 700;
      margin: 0 0 10px 0;
    }

    .page-header p {
      color: #6C737E;
      margin: 0;
    }

    .alert {
      border-radius: 10px;
      padding: 15px 20px;
      margin-bottom: 20px;
      border: none;
    }

    .alert-success {
      background-color: #d4edda;
      color: #155724;
    }

    .alert-danger {
      background-color: #f8d7da;
      color: #721c24;
    }

    .add-branch-form {
      background: white;
      padding: 30px;
      border-radius: 12px;
      margin-bottom: 30px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }

    .add-branch-form h3 {
      color: #7393A7;
      font-weight: 600;
      margin-bottom: 20px;
    }

    .form-group {
      margin-bottom: 20px;
    }

    .form-label {
      color: #333;
      font-weight: 500;
      margin-bottom: 8px;
      display: block;
    }

    .form-control {
      width: 100%;
      padding: 12px 15px;
      border: 1px solid #ddd;
      border-radius: 8px;
      font-size: 14px;
      font-family: 'Bricolage Grotesque', sans-serif;
      transition: border-color 0.3s ease;
    }

    .form-control:focus {
      outline: none;
      border-color: #7393A7;
      box-shadow: 0 0 0 3px rgba(115, 147, 167, 0.1);
    }

    .btn-add-branch {
      background: linear-gradient(135deg, #7393A7 0%, #5f7a8d 100%);
      color: white;
      padding: 12px 30px;
      border: none;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      width: 100%;
      font-size: 15px;
    }

    .btn-add-branch:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(115, 147, 167, 0.3);
    }

    .branches-table-container {
      background: white;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }

    .branches-table-container h3 {
      color: #7393A7;
      font-weight: 600;
      margin-bottom: 20px;
    }

    .branches-table {
      width: 100%;
      border-collapse: collapse;
    }

    .branches-table thead {
      background: #F5F5F5;
    }

    .branches-table th {
      padding: 15px;
      text-align: left;
      color: #333;
      font-weight: 600;
      font-size: 14px;
      border-bottom: 2px solid #E8ECF1;
    }

    .branches-table td {
      padding: 15px;
      border-bottom: 1px solid #E8ECF1;
      color: #666;
    }

    .branches-table tbody tr:hover {
      background: #FAFAFA;
    }

    .branch-badge {
      background: #E8ECF1;
      color: #7393A7;
      padding: 6px 12px;
      border-radius: 20px;
      font-weight: 600;
      font-size: 12px;
    }

    .medicine-count {
      background: #FFF3CD;
      color: #856404;
      padding: 6px 12px;
      border-radius: 6px;
      font-size: 13px;
      font-weight: 500;
    }

    .btn-edit, .btn-delete {
      padding: 8px 15px;
      border: none;
      border-radius: 6px;
      font-weight: 500;
      font-size: 13px;
      cursor: pointer;
      margin-right: 8px;
      transition: all 0.3s ease;
    }

    .btn-edit {
      background: #E8F4F8;
      color: #7393A7;
    }

    .btn-edit:hover {
      background: #7393A7;
      color: white;
      transform: translateY(-2px);
    }

    .btn-delete {
      background: #FFE8E8;
      color: #BE3144;
    }

    .btn-delete:hover {
      background: #BE3144;
      color: white;
      transform: translateY(-2px);
    }

    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0,0,0,0.5);
      animation: fadeIn 0.3s ease;
    }

    .modal-content {
      background-color: white;
      margin: 5% auto;
      padding: 30px;
      border-radius: 12px;
      width: 90%;
      max-width: 600px;
      box-shadow: 0 5px 25px rgba(0,0,0,0.2);
      animation: slideDown 0.3s ease;
    }

    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    @keyframes slideDown {
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
      padding-bottom: 15px;
      border-bottom: 2px solid #E8ECF1;
    }

    .modal-header h3 {
      color: #7393A7;
      margin: 0;
    }

    .close {
      color: #aaa;
      font-size: 28px;
      font-weight: bold;
      cursor: pointer;
      transition: color 0.3s ease;
    }

    .close:hover {
      color: #000;
    }

    .btn-update {
      background: #7393A7;
      color: white;
      padding: 12px 30px;
      border: none;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      width: 100%;
    }

    .btn-update:hover {
      background: #5f7a8d;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
    }
  </style>
</head>
<?php include 'super_admin_sidebar.php'; ?>

<body>
  <section class="home-section">
  <div class="home-content">
    <i class='bx bx-menu'></i>
    <span class="text">PharmAssist</span>
  </div>

    <div class="branch-management-container">
      <!-- Header -->
      <div class="page-header">
        <h1 style="font-family: 'Bricolage Grotesque';"><i class="bi bi-building"></i> Branch Management</h1>
        <p>Add, edit, or remove pharmacy branches. Changes will reflect across the system.</p>
      </div>

      <?php if (isset($success_message)): ?>
        <div class="alert alert-success">
          <i class="bi bi-check-circle"></i> <?php echo $success_message; ?>
        </div>
      <?php endif; ?>

      <?php if (isset($error_message)): ?>
        <div class="alert alert-danger">
          <i class="bi bi-exclamation-triangle"></i> <?php echo $error_message; ?>
        </div>
      <?php endif; ?>

      <!-- Add Branch Form -->
      <div class="add-branch-form">
        <h3 style="font-family: 'Bricolage Grotesque';"><i class="bi bi-plus-circle"></i> Add New Branch</h3>
        <form method="POST" action="super_admin_manage_branches.php">
          <input type="hidden" name="action" value="add">
          
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label">Branch Name</label>
                <input type="text" class="form-control" name="branch_name" placeholder="e.g., Branch 4" required>
              </div>
            </div>
            
            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label">Branch Address</label>
                <input type="text" class="form-control" name="branch_address" placeholder="e.g., 123 Main St, City" required>
              </div>
            </div>
          </div>

          <button type="submit" class="btn-add-branch">
            <i class="bi bi-plus-lg"></i> Add Branch
          </button>
        </form>
      </div>

      <!-- Branches Table -->
      <div class="branches-table-container">
        <h3 style="font-family: 'Bricolage Grotesque';"><i class="bi bi-list-ul"></i> Existing Branches</h3>
        
        <?php if ($branches->num_rows > 0): ?>
          <table class="branches-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Branch Name</th>
                <th>Address</th>
                <th>Medicines</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php while($branch = $branches->fetch_assoc()): 
                // Count medicines in this branch
                $count_stmt = $conn->prepare("SELECT COUNT(*) as count FROM medicine WHERE branch_id = ?");
                $count_stmt->bind_param("i", $branch['branch_id']);
                $count_stmt->execute();
                $medicine_count = $count_stmt->get_result()->fetch_assoc()['count'];
                $count_stmt->close();
              ?>
                <tr>
                  <td><span class="branch-badge"><?php echo $branch['branch_id']; ?></span></td>
                  <td><strong><?php echo htmlspecialchars($branch['branch_name']); ?></strong></td>
                  <td><?php echo htmlspecialchars($branch['branch_address']); ?></td>
                  <td><span class="medicine-count"><?php echo $medicine_count; ?> medicine(s)</span></td>
                  <td>
                    <button class="btn-edit" onclick="openEditModal(<?php echo $branch['branch_id']; ?>, '<?php echo htmlspecialchars($branch['branch_name']); ?>', '<?php echo htmlspecialchars($branch['branch_address']); ?>')">
                      <i class="bi bi-pencil"></i> Edit
                    </button>
                    <button class="btn-delete" onclick="confirmDelete(<?php echo $branch['branch_id']; ?>, '<?php echo htmlspecialchars($branch['branch_name']); ?>', <?php echo $medicine_count; ?>)">
                      <i class="bi bi-trash"></i> Delete
                    </button>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        <?php else: ?>
          <p style="text-align: center; color: #6C737E; padding: 20px;">No branches found. Add your first branch above!</p>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- Edit Modal -->
  <div id="editModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h3><i class="bi bi-pencil-square"></i> Edit Branch</h3>
        <span class="close" onclick="closeEditModal()">&times;</span>
      </div>
      <form method="POST" action="super_admin_manage_branches.php">
        <input type="hidden" name="action" value="update">
        <input type="hidden" id="edit_branch_id" name="branch_id">
        
        <div class="form-group">
          <label class="form-label">Branch Name</label>
          <input type="text" class="form-control" id="edit_branch_name" name="branch_name" required>
        </div>
        
        <div class="form-group">
          <label class="form-label">Branch Address</label>
          <input type="text" class="form-control" id="edit_branch_address" name="branch_address" required>
        </div>

        <button type="submit" class="btn-update">
          Update Branch
        </button>
      </form>
    </div>
  </div>

  <script src="source/sidebar.js"></script>
  <script>
    function openEditModal(id, name, address) {
      document.getElementById('edit_branch_id').value = id;
      document.getElementById('edit_branch_name').value = name;
      document.getElementById('edit_branch_address').value = address;
      document.getElementById('editModal').style.display = 'block';
    }

    function closeEditModal() {
      document.getElementById('editModal').style.display = 'none';
    }

    function confirmDelete(id, name, medicineCount) {
      if (medicineCount > 0) {
        alert(`Cannot delete "${name}". It has ${medicineCount} medicine(s) associated with it.\n\nPlease reassign or delete those medicines first.`);
        return;
      }
      
      if (confirm(`Are you sure you want to delete "${name}"?\n\nThis action cannot be undone.`)) {
        window.location.href = `super_admin_manage_branches.php?delete=${id}`;
      }
    }

    window.onclick = function(event) {
      const modal = document.getElementById('editModal');
      if (event.target == modal) {
        closeEditModal();
      }
    }
  </script>

  <script src="source/sidebar.js"></script>
  <script src="source/homepage.js"></script>
</body>
</html>