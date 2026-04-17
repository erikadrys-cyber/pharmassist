<?php
session_start();
include 'config/connection.php';
include 'admin_sidebar.php';
require_once 'check_role.php';
 
requireLogin();
requireRole(['ceo', 'manager1', 'manager2', 'p_technician1', 'p_technician2']);
 
$currentRole = getCurrentUserRole();

$userInfo = getUserInfo($_SESSION['id']);
$branch_id = $userInfo['branch_id'] ?? null;
$branch_name = ($branch_id == 1) ? "Branch 1" : "Branch 2";

// Fetch all pending reservations for this branch
$stmt = $conn->prepare("SELECT * FROM reservations WHERE status='Pending' AND branch_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $branch_id);
$stmt->execute();
$result = $stmt->get_result();
$allRows = $result->fetch_all(MYSQLI_ASSOC);

// Group ALL rows by code so we can pass them to the modal
$groupedByCode = [];
foreach ($allRows as $row) {
    $code = $row['code'] ?? 'NO_CODE';
    $groupedByCode[$code][] = $row;
}

// Deduplicate displayed rows — show only the first row per code in the main table
// (the rest are visible via the modal)
$seenCodes = [];
$displayRows = [];
foreach ($allRows as $row) {
    $code = $row['code'] ?? null;
    if ($code && in_array($code, $seenCodes)) continue;
    if ($code) $seenCodes[] = $code;
    $displayRows[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reservations | PharmAssist</title>

    <link rel="stylesheet" href="plugins/admin_sidebar.css?v=2">
    <link rel="stylesheet" href="dashboard/dashboard.css?v=2">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@400;600&family=Tinos:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Tinos', serif;
            background-color: #E8ECF1;
            margin: 0;
        }

        .home-content, .text, .section-title { color: white; font-size: 18px; font-weight: 600; font-family: 'Bricolage Grotesque', sans-serif; }

        .table-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
            border: 1px solid #b5cfd8;
            padding: 30px;
            margin: 30px;
        }

        .table-container h2 {
            font-family: 'Tinos', serif;
            color: #6C737E;
            font-weight: 700;
            text-align: center;
            margin-bottom: 25px;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: #fff;
            border-radius: 10px;
            overflow: hidden;
        }

        th {
            background-color: #7393A7;
            color: white;
            text-align: left;
            padding: 12px;
            font-weight: 600;
        }

        /* Center specific columns */
        th:nth-child(4), th:nth-child(5) { text-align: center; }
        td:nth-child(4), td:nth-child(5) { text-align: center; }

        td {
            padding: 12px;
            border-bottom: 1px solid #e8ecf1;
            color: #333;
            font-family: "Bricolage Grotesque", sans-serif;
        }

        tr:nth-child(even) { background: #f6faff; }

        /* Buttons Styling */
        .btn {
            padding: 8px 14px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 600;
            transition: 0.3s;
            margin: 0 2px;
        }

        .btn.approve { background: #84B179; color: white; }
        .btn.approve:hover { background: #4a9266; transform: translateY(-1px); }

        .btn.reject { background: #C83F12; color: white; }
        .btn.reject:hover { background: #aa3310; transform: translateY(-1px); }

        .btn-view {
            background-color: #4A90E2;
            color: white !important;
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.8rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-items {
            background-color: #7393A7;
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            border: none;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: 0.3s;
        }
        .btn-items:hover { background-color: #5a7a8e; transform: translateY(-1px); }

        .text-none {
            color: #BDC3C7;
            font-size: 0.85rem;
            font-weight: 600;
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

        .item-count-badge {
            background: #e8f4fd;
            color: #4A90E2;
            border: 1px solid #b5d8f0;
            border-radius: 12px;
            padding: 2px 8px;
            font-size: 0.75rem;
            font-weight: 700;
            margin-left: 5px;
        }

        /* ── Modal Styles ── */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.45);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal-overlay.active { display: flex; }

        .modal-box {
            background: white;
            border-radius: 16px;
            width: 90%;
            max-width: 700px;
            max-height: 85vh;
            display: flex;
            flex-direction: column;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }

        .modal-header {
            background: #7393A7;
            color: white;
            padding: 18px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
        }
        .modal-header h3 {
            margin: 0;
            font-family: 'Bricolage Grotesque', sans-serif;
            font-size: 1rem;
            font-weight: 700;
        }
        .modal-close {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            cursor: pointer;
            font-size: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: 0.2s;
        }
        .modal-close:hover { background: rgba(255,255,255,0.35); }

        .modal-body {
            padding: 20px 24px;
            overflow-y: auto;
        }

        .modal-meta {
            background: #f6faff;
            border: 1px solid #e0eaf4;
            border-radius: 10px;
            padding: 12px 16px;
            margin-bottom: 18px;
            font-family: 'Bricolage Grotesque', sans-serif;
            font-size: 0.875rem;
            color: #555;
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        .modal-meta span strong { color: #333; }

        .modal-table {
            width: 100%;
            border-collapse: collapse;
            font-family: 'Bricolage Grotesque', sans-serif;
            font-size: 0.875rem;
        }
        .modal-table th {
            background: #edf2f7;
            color: #555;
            padding: 10px 12px;
            text-align: left;
            font-weight: 700;
            border-bottom: 2px solid #d0dde8;
        }
        .modal-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #eef2f7;
            color: #333;
        }
        .modal-table tr:last-child td { border-bottom: none; }
        .modal-table tr:nth-child(even) td { background: #f9fbff; }

        .modal-total {
            margin-top: 16px;
            text-align: right;
            font-family: 'Bricolage Grotesque', sans-serif;
            font-weight: 700;
            font-size: 1rem;
            color: #333;
            border-top: 2px solid #e0eaf4;
            padding-top: 12px;
        }
        .modal-total span { color: #59AC77; font-size: 1.1rem; }
    </style>
</head>

<body>
    <section class="home-section">
        <div class="home-content">
            <i class='bx bx-menu'></i>
            <span class="text">PharmAssist</span>
            <span class="branch-badge">📍 <?= $branch_name ?></span>
        </div>

        <div class="table-container">
            <h2 style="font-family: 'Bricolage Grotesque';">Reservation Actions</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Medicine</th>
                        <th>All Items</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="reservationTable">
                    <?php if (!empty($displayRows)): ?>
                        <?php foreach ($displayRows as $row): ?>
                        <?php
                            $code = $row['code'] ?? null;
                            $groupItems = $code ? ($groupedByCode[$code] ?? [$row]) : [$row];
                            $itemCount = count($groupItems);
                            // JSON-encode group for the modal
                            $groupJson = htmlspecialchars(json_encode($groupItems), ENT_QUOTES, 'UTF-8');
                        ?>
                        <tr id="row-<?= $row['reservation_id'] ?>" data-code="<?= htmlspecialchars($code ?? '') ?>">
                            <td><?= $row['reservation_id'] ?></td>
                            <td><?= htmlspecialchars(trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''))) ?></td>
                            <td><?= htmlspecialchars($row['medicine']) ?></td>
                            <td>
                                <button class="btn-items" onclick='openItemsModal(<?= $groupJson ?>)'>
                                    <i class="bi bi-box-seam"></i> View
                                    <?php if ($itemCount > 1): ?>
                                        <span class="item-count-badge"><?= $itemCount ?></span>
                                    <?php endif; ?>
                                </button>
                            </td>
                            <td>
                                <button class="btn approve" onclick="updateStatus('<?= htmlspecialchars($code ?? $row['reservation_id']) ?>', 'approve', <?= $itemCount ?>)">Approve</button>
                                <button style="background-color: #E5707E" class="btn reject"  onclick="updateStatus('<?= htmlspecialchars($code ?? $row['reservation_id']) ?>', 'reject',  <?= $itemCount ?>)">Reject</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 40px; color: #999;">
                                <i class="bi bi-inbox" style="font-size: 2rem; display: block; margin-bottom: 10px;"></i>
                                No pending reservations found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- ── Items Modal ── -->
    <div class="modal-overlay" id="itemsModal" onclick="closeModalOnOverlay(event)">
        <div class="modal-box">
            <div class="modal-header">
                <h3><i class="bi bi-box-seam me-2"></i> Order Items</h3>
                <button class="modal-close" onclick="closeItemsModal()"><i class="bi bi-x"></i></button>
            </div>
            <div class="modal-body" id="itemsModalBody">
                <!-- Populated by JS -->
            </div>
        </div>
    </div>

    <script>
    // ── Modal ──────────────────────────────────────────────────────────────────
    function openItemsModal(items) {
        const first = items[0];
        const fullName = ((first.first_name ?? '') + ' ' + (first.last_name ?? '')).trim();
        const code = first.code ?? 'N/A';
        const grandTotal = items.reduce((s, r) => s + (parseFloat(r.price) * parseInt(r.quantity)), 0);

        let rows = items.map(r => {
            const lineTotal = (parseFloat(r.price) * parseInt(r.quantity)).toFixed(2);
            const rxBadge = r.prescription
                ? `<a href="uploads/${r.prescription}" target="_blank" class="btn-view"><i class="bi bi-eye-fill"></i> View</a>`
                : `<span class="text-none">NONE</span>`;
            return `
                <tr>
                    <td>#${r.reservation_id}</td>
                    <td>${escHtml(r.medicine ?? '')}</td>
                    <td style="text-align:center">${r.quantity}</td>
                    <td style="text-align:right">₱${parseFloat(r.price).toFixed(2)}</td>
                    <td style="text-align:right">₱${lineTotal}</td>
                    <td style="text-align:center">${rxBadge}</td>
                </tr>`;
        }).join('');

        document.getElementById('itemsModalBody').innerHTML = `
            <div class="modal-meta">
                <span><strong>Patient:</strong> ${escHtml(fullName)}</span>
                <span><strong>Code:</strong> ${escHtml(code)}</span>
                <span><strong>Items:</strong> ${items.length}</span>
            </div>
            <table class="modal-table">
                <thead>
                    <tr>
                        <th>Res. ID</th>
                        <th>Medicine</th>
                        <th style="text-align:center">Qty</th>
                        <th style="text-align:right">Unit Price</th>
                        <th style="text-align:right">Subtotal</th>
                        <th style="text-align:center">Prescription</th>
                    </tr>
                </thead>
                <tbody>${rows}</tbody>
            </table>
            <div class="modal-total">
                Order Total: <span>₱${grandTotal.toFixed(2)}</span>
            </div>`;

        document.getElementById('itemsModal').classList.add('active');
    }

    function closeItemsModal() {
        document.getElementById('itemsModal').classList.remove('active');
    }

    function closeModalOnOverlay(e) {
        if (e.target === document.getElementById('itemsModal')) closeItemsModal();
    }

    function escHtml(str) {
        return String(str)
            .replace(/&/g,'&amp;').replace(/</g,'&lt;')
            .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    // ── Approve / Reject ───────────────────────────────────────────────────────
    // Now works by code (affects all items in the same order at once)
    function updateStatus(code, action, itemCount) {
        let url = `update_status.php?code=${encodeURIComponent(code)}&action=${action}`;
        const plural = itemCount > 1 ? ` (${itemCount} items)` : '';

        if (action === 'reject') {
            let reason = prompt("Enter reason for rejection:");
            if (reason === null) return;
            if (reason.trim() === "") {
                alert("Please provide a reason for rejection.");
                return;
            }
            url += `&reason=${encodeURIComponent(reason)}`;
        } else {
            if (!confirm(`Approve this reservation${plural}?`)) return;
        }

        fetch(url)
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    // Remove all table rows that share this code
                    document.querySelectorAll(`tr[data-code="${CSS.escape(code)}"]`).forEach(row => {
                        row.style.transition = '0.5s';
                        row.style.opacity = '0';
                        setTimeout(() => row.remove(), 500);
                    });
                } else {
                    alert("Error: " + data.message);
                }
            })
            .catch(err => {
                console.error('Error:', err);
                alert("An error occurred while processing the request.");
            });
    }
    </script>

    <script src="source/sidebar.js"></script>
    <script src="source/homepage.js"></script>
</body>
</html>