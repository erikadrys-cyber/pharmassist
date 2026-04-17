<?php
/**
 * Medicine Scanner - Backend API
 * Provides medicine details for detected medicines
 * 
 * Usage in medicine-scanner.js:
 * const medicineData = await fetchMedicineData('Ibuprofen');
 */

include 'config/connection.php';
session_start();

// Enable CORS for scanner requests
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: public, max-age=3600');

$action = isset($_GET['action']) ? $_GET['action'] : '';
$medicineName = isset($_GET['name']) ? trim($_GET['name']) : '';

/* ═══════════════════════════════════════════════════════════
   1. GET MEDICINE BY NAME (for scanner)
   ═══════════════════════════════════════════════════════════ */
if ($action === 'get_by_name' && !empty($medicineName)) {
    try {
        // Query medicine by name
        $query = "SELECT 
                    m.id, m.medicine_name, m.category, m.strength,
                    m.form, m.price, m.quantity, m.prescription_required,
                    m.image_path, m.description, m.branch,
                    b.branch_name, b.branch_address
                  FROM medicine m
                  JOIN branches b ON m.branch = b.branch_name
                  WHERE LOWER(m.medicine_name) LIKE LOWER(?)
                  LIMIT 10";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception($conn->error);
        }
        
        $searchTerm = '%' . $medicineName . '%';
        $stmt->bind_param('s', $searchTerm);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $medicines = [];
        while ($row = $result->fetch_assoc()) {
            $medicines[] = [
                'id' => $row['id'],
                'name' => $row['medicine_name'],
                'category' => $row['category'],
                'strength' => $row['strength'],
                'form' => $row['form'],
                'price' => floatval($row['price']),
                'quantity' => intval($row['quantity']),
                'prescription_required' => $row['prescription_required'] === 'yes',
                'branch' => $row['branch_name'],
                'branch_address' => $row['branch_address'],
                'available' => intval($row['quantity']) > 0,
                'low_stock' => intval($row['quantity']) > 0 && intval($row['quantity']) < 5
            ];
        }
        
        echo json_encode([
            'success' => true,
            'count' => count($medicines),
            'medicines' => $medicines
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

/* ═══════════════════════════════════════════════════════════
   2. GET ALL MEDICINES FOR DATABASE SYNC
   ═══════════════════════════════════════════════════════════ */
elseif ($action === 'get_all') {
    try {
        $query = "SELECT DISTINCT m.medicine_name, m.category, m.strength, 
                         m.form, m.prescription_required
                  FROM medicine m
                  ORDER BY m.medicine_name ASC";
        
        $result = $conn->query($query);
        $medicines = [];
        
        while ($row = $result->fetch_assoc()) {
            $medicines[$row['medicine_name']] = [
                'strength' => $row['strength'],
                'form' => $row['form'],
                'category' => $row['category'],
                'prescription' => $row['prescription_required'] === 'yes'
            ];
        }
        
        echo json_encode([
            'success' => true,
            'count' => count($medicines),
            'medicines' => $medicines
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

/* ═══════════════════════════════════════════════════════════
   3. SEARCH MEDICINE WITH FILTERS
   ═══════════════════════════════════════════════════════════ */
elseif ($action === 'search') {
    try {
        $name = isset($_GET['name']) ? trim($_GET['name']) : '';
        $branch = isset($_GET['branch']) ? trim($_GET['branch']) : '';
        $available_only = isset($_GET['available_only']) ? $_GET['available_only'] === '1' : false;
        
        $conditions = [];
        $params = [];
        $types = '';
        
        if (!empty($name)) {
            $conditions[] = "LOWER(m.medicine_name) LIKE LOWER(?)";
            $params[] = '%' . $name . '%';
            $types .= 's';
        }
        
        if (!empty($branch)) {
            $conditions[] = "b.branch_name = ?";
            $params[] = $branch;
            $types .= 's';
        }
        
        if ($available_only) {
            $conditions[] = "m.quantity > 0";
        }
        
        $query = "SELECT m.*, b.branch_name, b.branch_address
                  FROM medicine m
                  JOIN branches b ON m.branch = b.branch_name";
        
        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }
        
        $query .= " ORDER BY m.medicine_name ASC LIMIT 20";
        
        $stmt = $conn->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        $medicines = [];
        while ($row = $result->fetch_assoc()) {
            $medicines[] = [
                'id' => $row['id'],
                'name' => $row['medicine_name'],
                'category' => $row['category'],
                'strength' => $row['strength'],
                'form' => $row['form'],
                'price' => floatval($row['price']),
                'quantity' => intval($row['quantity']),
                'branch' => $row['branch_name'],
                'available' => intval($row['quantity']) > 0
            ];
        }
        
        echo json_encode([
            'success' => true,
            'count' => count($medicines),
            'medicines' => $medicines
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

/* ═══════════════════════════════════════════════════════════
   4. DEFAULT - RETURN ERROR
   ═══════════════════════════════════════════════════════════ */
else {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid action. Use: get_by_name, get_all, or search'
    ]);
}

$conn->close();
?>