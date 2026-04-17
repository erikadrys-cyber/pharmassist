<?php
session_start();
require 'config/connection.php';
include 'admin_sidebar.php';
require_once 'check_role.php';
 
// Check if logged in and has required role (admin or super_admin only)
requireLogin();
requireRole(['manager1', 'manager2', 'ceo']);
 
$currentRole = getCurrentUserRole();

$userInfo = getUserInfo($_SESSION['id']);
$branch_id = $userInfo['branch_id'] ?? null;
$branch_name = ($branch_id == 1) ? "Branch 1" : "Branch 2";
 
$flash = $err = '';
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'users';

/* =========================
   SEND MESSAGE TO SUPER ADMIN
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_to_super_admin'])) {
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    $adminId = (int)$_SESSION['id'];

    if ($subject === '' || $message === '') {
        $err = "Subject and message are required.";
        $activeTab = 'superadmin';
    } else {
        $name  = $_SESSION['name']  ?? 'Admin';
        $email = $_SESSION['email'] ?? 'a.pharmasee@gmail.com';

        $stmt = $conn->prepare("
            INSERT INTO admin_messages
              (admin_id, name, email, subject, message, branch_id, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, 'new', NOW())
        ");
        $stmt->bind_param("issssi", $adminId, $name, $email, $subject, $message, $branch_id);
        $stmt->execute();
        $stmt->close();

        $flash = "Message sent to Super Admin.";
        $activeTab = 'superadmin';
    }
}

/* =========================
   REPLY TO USER (contact_messages)
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_id'])) {
    $replyId  = (int)$_POST['reply_id'];
    $replyMsg = trim($_POST['reply_message'] ?? '');
    $replySub = trim($_POST['reply_subject']  ?? '');
    $activeTab = 'users';

    if ($replyMsg === '' || $replySub === '') {
        $err = 'Subject and message are required.';
    } else {
        /* Look up user email & name via users table join */
        $q = $conn->prepare("
    SELECT name, email, subject
    FROM contact_messages
    WHERE user_id = ?
    LIMIT 1
");
        $q->bind_param("i", $replyId);
        $q->execute();
        $res = $q->get_result();

        if ($res->num_rows !== 1) {
            $err = 'Message not found.';
        } else {
            $orig = $res->fetch_assoc();

            require __DIR__ . '/vendor/autoload.php';
            $cfg  = include __DIR__ . '/config/mail.php';
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);

            try {
                $mail->isSMTP();
                $mail->Host       = $cfg['host'];
                $mail->SMTPAuth   = true;
                $mail->Username   = $cfg['username'];
                $mail->Password   = $cfg['password'];
                $mail->SMTPSecure = $cfg['encryption'];
                $mail->Port       = $cfg['port'];

                $mail->setFrom($cfg['from_email'], $cfg['from_name']);
                $mail->addAddress($orig['email'], $orig['name']);

                $mail->Subject = $replySub;
                $mail->isHTML(true);
                $mail->Body    = nl2br(htmlspecialchars($replyMsg));
                $mail->AltBody = $replyMsg;

                $mail->send();

                $adminId = (int)$_SESSION['id'];
                $upd = $conn->prepare("
                    UPDATE contact_messages
                    SET admin_reply=?, status='replied', replied_by=?, replied_at=NOW()
                    WHERE user_id=?
                ");
                $upd->bind_param("sii", $replyMsg, $adminId, $replyId);
                $upd->execute();
                $upd->close();

                $flash = 'Reply sent to ' . htmlspecialchars($orig['email']) . '.';
            } catch (Exception $e) {
                $err = 'Email failed: ' . $mail->ErrorInfo;
            }
        }
        $q->close();
    }
}

/* =========================
   FETCH DATA
========================= */

// User messages (contact_messages) joined with users table
$userMessages = [];
$stmt = $conn->prepare("
    SELECT user_id, name, email, subject, message,
           status, admin_reply, created_at, replied_at
    FROM contact_messages
    WHERE branch_id = ?
    ORDER BY created_at DESC
");
$stmt->bind_param("i", $branch_id);
$stmt->execute();
$rs = $stmt->get_result();

while ($row = $rs->fetch_assoc()) $userMessages[] = $row;

// Admin ↔ Super Admin messages
$adminMessages = [];
$stmt2 = $conn->prepare("SELECT * FROM admin_messages WHERE branch_id = ? ORDER BY created_at DESC");
$stmt2->bind_param("i", $branch_id);
$stmt2->execute();
$rs2 = $stmt2->get_result();
while ($row = $rs2->fetch_assoc()) $adminMessages[] = $row;

// Count unread per tab for badges
$newUserCount  = count(array_filter($userMessages,  fn($m) => $m['status'] === 'new'));
$newAdminCount = count(array_filter($adminMessages, fn($m) => $m['status'] === 'new'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Admin | Messages</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,200..800&family=Tinos:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" crossorigin="anonymous">
<link rel="stylesheet" href="plugins/style.css">
<link rel="stylesheet" href="plugins/admin_sidebar.css?v=4">
<link rel="icon" href="website_icon/favicon.png" type="image/png" sizes="64x64">
<style>
/* ── Base ── */
body {
    font-family: 'Tinos', serif;
    background-color: #E8ECF1;
    margin: 0;
}

/* ── Layout (mirrors super_admin_messages.php) ── */
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
.home-section .home-content {
    height: 60px;
    display: flex;
    align-items: center;
    background: linear-gradient(to right, #7393A7, #B5CFD8);
    padding: 0 20px;
    position: fixed;
    top: 0;
    left: 78px;
    width: calc(100% - 78px);
    transition: all 0.4s ease;
    z-index: 90;
}
.sidebar.open ~ .home-section .home-content {
    left: 250px;
    width: calc(100% - 250px);
}
.home-content i  { color: white; font-size: 20px; cursor: pointer; margin-right: 15px; }
.home-content .text { color: white; font-size: 18px; font-weight: 600; font-family: 'Bricolage Grotesque', sans-serif; }

/* ── Container ── */
.container {
    max-width: 1100px;
    margin: 0 auto;
    padding: 30px 20px;
}

/* ── Tabs ── */
.tabs-wrapper {
    display: flex;
    gap: 8px;
    margin-bottom: 0;
    margin-top: 90px;
}
.tab-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 11px 22px;
    border: none;
    border-radius: 10px 10px 0 0;
    background: #d0dae3;
    color: #5f7a8d;
    font-family: 'Bricolage Grotesque', sans-serif;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: background 0.2s, color 0.2s;
    position: relative;
}
.tab-btn:hover { background: #c3d2dc; }
.tab-btn.active {
    background: #fff;
    color: #7393A7;
    box-shadow: 0 -2px 8px rgba(0,0,0,.06);
}
.tab-badge {
    background: #7393A7;
    color: #fff;
    border-radius: 20px;
    padding: 2px 8px;
    font-size: 11px;
    font-weight: 700;
    min-width: 20px;
    text-align: center;
}
.tab-badge.zero { background: #cbd5e1; color: #64748b; }

/* ── Card ── */
.card {
    background: #fff;
    border-radius: 0 12px 12px 12px;
    padding: 28px;
    box-shadow: 0 2px 8px rgba(0,0,0,.05);
}
.card-section {
    border-radius: 12px;
    padding: 24px;
    background: #fff;
    box-shadow: 0 2px 8px rgba(0,0,0,.05);
    margin-bottom: 24px;
}
.card-section:last-child { margin-bottom: 0; }

/* ── Typography ── */
h2, th {
    font-family: 'Bricolage Grotesque', sans-serif;
    color: #7393A7 !important;
}
h3 { font-family: 'Bricolage Grotesque', sans-serif; color: #334155; }
label {
    display: block;
    margin-top: 15px;
    margin-bottom: 8px;
    color: #333;
    font-weight: 600;
    font-family: 'Bricolage Grotesque', sans-serif;
}
label:first-of-type { margin-top: 0; }

/* ── Table ── */
.table { width: 100%; border-collapse: collapse; }
.table th, .table td {
    padding: 12px 14px;
    border-bottom: 1px solid #e5e7eb;
    text-align: left;
    font-size: 14px;
}
.table tbody tr:hover { background: #f8fafc; transition: background 0.15s; }
.table th { font-size: 13px; text-transform: uppercase; letter-spacing: .04em; }

/* ── Badges ── */
.badge {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    font-family: 'Bricolage Grotesque', sans-serif;
}
.badge.new      { background: #e1f0ff; color: #1e3a8a; }
.badge.replied  { background: #e8f5e9; color: #256029; }
.badge.closed   { background: #fff4e5; color: #92400e; }

/* ── Form elements ── */
.input, textarea, select {
    width: 100%;
    padding: 12px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    font-family: 'Bricolage Grotesque', sans-serif;
    font-size: 14px;
    transition: border-color 0.2s, box-shadow 0.2s;
    box-sizing: border-box;
}
.input:focus, textarea:focus {
    outline: none;
    border-color: #7393A7;
    box-shadow: 0 0 0 3px rgba(115,147,167,.15);
}
textarea { resize: vertical; }

/* ── Buttons ── */
button[type="submit"], .btn-primary {
    padding: 11px 22px;
    border: 0;
    border-radius: 8px;
    background: linear-gradient(135deg, #7393A7 0%, #5f7a8d 100%);
    color: #fff;
    cursor: pointer;
    font-weight: 700;
    font-family: 'Bricolage Grotesque', sans-serif;
    font-size: 14px;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    transition: transform 0.2s, box-shadow 0.2s;
}
button[type="submit"]:hover, .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 14px rgba(115,147,167,.35);
}

/* ── Message content boxes ── */
.message-content {
    white-space: pre-wrap;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 15px;
    background: #fafafa;
    margin: 10px 0;
    font-size: 14px;
    line-height: 1.6;
}
.message-reply {
    white-space: pre-wrap;
    border: 1px solid #c8e6c9;
    border-radius: 8px;
    padding: 15px;
    background: #f0fdf4;
    margin-bottom: 10px;
    font-size: 14px;
    line-height: 1.6;
}
.super-admin-reply {
    white-space: pre-wrap;
    border: 1px solid #c7d9e8;
    border-radius: 8px;
    padding: 15px;
    background: #edf4f9;
    margin-bottom: 10px;
    font-size: 14px;
    line-height: 1.6;
}

/* ── Alerts ── */
.success-message {
    background: #e6f7ed;
    color: #1b5e20;
    padding: 13px 16px;
    border-radius: 8px;
    margin-bottom: 18px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
    font-family: 'Bricolage Grotesque', sans-serif;
}
.error-message {
    background: #fde2e1;
    color: #7f1d1d;
    padding: 13px 16px;
    border-radius: 8px;
    margin-bottom: 18px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
    font-family: 'Bricolage Grotesque', sans-serif;
}

/* ── Expandable reply row ── */
.reply-row { display: none; background: #f8fafc; }
.reply-row td { padding: 20px 24px; }
.reply-row.open { display: table-row; }
.reply-toggle {
    background: none;
    border: none;
    color: #7393A7;
    font-weight: 600;
    font-family: 'Bricolage Grotesque', sans-serif;
    font-size: 13px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 6px;
    transition: background 0.2s;
}
.reply-toggle:hover { background: #e8f0f5; transform: none; box-shadow: none; }

/* ── Super admin thread item ── */
.thread-item {
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 18px 20px;
    margin-bottom: 14px;
    transition: box-shadow 0.2s;
}
.thread-item:hover { box-shadow: 0 2px 10px rgba(0,0,0,.07); }
.thread-item:last-child { margin-bottom: 0; }
.thread-meta {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 8px;
}
.thread-subject {
    font-family: 'Bricolage Grotesque', sans-serif;
    font-weight: 700;
    font-size: 15px;
    color: #334155;
}
.thread-date {
    font-size: 12px;
    color: #94a3b8;
    margin-left: auto;
}
.thread-body { font-size: 14px; color: #475569; line-height: 1.6; }
.thread-reply-label {
    font-family: 'Bricolage Grotesque', sans-serif;
    font-size: 12px;
    font-weight: 700;
    color: #7393A7;
    text-transform: uppercase;
    letter-spacing: .05em;
    margin: 14px 0 6px 0;
}

/* ── Empty state ── */
.empty-state {
    text-align: center;
    padding: 50px 20px;
    color: #94a3b8;
}
.empty-state i {
    font-size: 48px;
    color: #B5CFD8;
    margin-bottom: 12px;
    display: block;
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

.empty-state p { font-family: 'Bricolage Grotesque', sans-serif; margin: 0; }

/* ── Section divider ── */
hr.divider { border: none; border-top: 1px solid #e5e7eb; margin: 24px 0; }

/* ── Tab panel ── */
.tab-panel { display: none; }
.tab-panel.active { display: block; }
</style>
</head>
<body>

<section class="home-section">
  <div class="home-content">
    <i class='bx bx-menu' id="sidebarToggle"></i>
    <span class="text">PharmAssist</span>
    <span class="branch-badge">📍 <?= $branch_name ?></span>
  </div>

  <div class="container">

    <?php if ($flash): ?>
      <div class="success-message" style="margin-top: 4%; margin-bottom: -4%;"><i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($flash) ?></div>
    <?php endif; ?>
    <?php if ($err): ?>
      <div class="error-message"><i class="bi bi-exclamation-circle-fill"></i> <?= htmlspecialchars($err) ?></div>
    <?php endif; ?>

    <!-- ── Tabs ── -->
    <div class="tabs-wrapper">
      <button class="tab-btn <?= $activeTab === 'users' ? 'active' : '' ?>" onclick="switchTab('users')">
        <i class="bi bi-people"></i> User Messages
        <span class="tab-badge <?= $newUserCount === 0 ? 'zero' : '' ?>"><?= $newUserCount ?></span>
      </button>
      <button class="tab-btn <?= $activeTab === 'superadmin' ? 'active' : '' ?>" onclick="switchTab('superadmin')">
        <i class="bi bi-shield-lock"></i> Super Admin
        <span class="tab-badge <?= $newAdminCount === 0 ? 'zero' : '' ?>"><?= $newAdminCount ?></span>
      </button>
    </div>

    <!-- ════════════════════════════
         TAB 1 — USER MESSAGES
    ════════════════════════════ -->
    <div class="card tab-panel <?= $activeTab === 'users' ? 'active' : '' ?>" id="tab-users">
      <h2 style="margin: 0 0 6px 0; font-size: 1.4rem;"><i class="bi bi-chat-dots"></i> Messages from Users</h2>
      <p style="color:#6C737E; margin: 0 0 22px 0; font-size: 14px;">View and reply to user enquiries via email.</p>

      <?php if (empty($userMessages)): ?>
        <div class="empty-state">
          <i class="bi bi-inbox"></i>
          <p>No user messages yet</p>
        </div>
      <?php else: ?>
        <table class="table">
          <thead>
            <tr>
              <th>#</th>
              <th>Name</th>
              <th>Email</th>
              <th>Subject</th>
              <th>Status</th>
              <th>Date</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($userMessages as $i => $msg): ?>
            <tr>
              <td><?= (int)$msg['user_id'] ?></td>
              <td><?= htmlspecialchars($msg['name']) ?></td>
              <td><?= htmlspecialchars($msg['email']) ?></td>
              <td><?= htmlspecialchars($msg['subject']) ?></td>
              <td><span class="badge <?= htmlspecialchars($msg['status']) ?>"><?= htmlspecialchars($msg['status']) ?></span></td>
              <td style="font-size:13px; color:#6b7280;"><?= htmlspecialchars($msg['created_at']) ?></td>
              <td>
                <button type="button" class="reply-toggle" onclick="toggleReply('user-<?= $i ?>', this)">
                  <i class="bi bi-reply"></i> Reply
                </button>
              </td>
            </tr>

            <!-- Expandable reply form -->
            <tr class="reply-row" id="user-<?= $i ?>">
              <td colspan="7">
                <!-- Show original message -->
                <div style="margin-bottom:16px;">
                  <div style="font-weight:700; font-family:'Bricolage Grotesque',sans-serif; font-size:13px; color:#7393A7; text-transform:uppercase; letter-spacing:.05em; margin-bottom:6px;">Original Message</div>
                  <div class="message-content"><?= htmlspecialchars($msg['message']) ?></div>
                </div>

                <?php if ($msg['admin_reply']): ?>
                <div style="margin-bottom:16px;">
                  <div style="font-weight:700; font-family:'Bricolage Grotesque',sans-serif; font-size:13px; color:#256029; text-transform:uppercase; letter-spacing:.05em; margin-bottom:6px;">Your Previous Reply</div>
                  <div class="message-reply"><?= htmlspecialchars($msg['admin_reply']) ?></div>
                </div>
                <?php endif; ?>

                <!-- Reply form -->
                <form method="post">
                  <input type="hidden" name="reply_id" value="<?= (int)$msg['user_id'] ?>">
                  <div style="display:grid; gap:14px;">
                    <div>
                      <label style="margin-top:0;">Reply Subject</label>
                      <input class="input" name="reply_subject" value="Re: <?= htmlspecialchars($msg['subject']) ?>" required>
                    </div>
                    <div>
                      <label>Reply Message</label>
                      <textarea name="reply_message" rows="5" required placeholder="Type your reply here..."></textarea>
                    </div>
                    <div>
                      <button type="submit"><i class="bi bi-send"></i> Send Reply</button>
                    </div>
                  </div>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

    <!-- ════════════════════════════
         TAB 2 — SUPER ADMIN MESSAGES
    ════════════════════════════ -->
    <div class="card tab-panel <?= $activeTab === 'superadmin' ? 'active' : '' ?>" id="tab-superadmin">

      <!-- Compose form -->
      <div class="card-section" style="background:#f8fafc; border:1px solid #e5e7eb; box-shadow:none; margin-bottom:28px;">
        <h2 style="margin: 0 0 6px 0; font-size: 1.3rem;"><i class="bi bi-pencil-square"></i> Message Super Admin</h2>
        <p style="color:#6C737E; margin: 0 0 20px 0; font-size: 14px;">Send a message directly to the super administrator.</p>
        <form method="post">
          <input type="hidden" name="send_to_super_admin" value="1">
          <div style="display:grid; gap:14px;">
            <div>
              <label style="margin-top:0;">Subject</label>
              <input class="input" name="subject" placeholder="e.g. Inventory issue at Branch A" required>
            </div>
            <div>
              <label>Message</label>
              <textarea name="message" rows="5" placeholder="Describe your concern or inquiry..." required></textarea>
            </div>
            <div>
              <button type="submit"><i class="bi bi-send"></i> Send to Super Admin</button>
            </div>
          </div>
        </form>
      </div>

      <!-- Message thread -->
      <h3 style="margin: 0 0 16px 0; font-size: 1.1rem; color:#334155;">
        <i class="bi bi-chat-square-text" style="color:#7393A7;"></i> Conversation History
      </h3>

      <?php if (empty($adminMessages)): ?>
        <div class="empty-state">
          <i class="bi bi-chat-square"></i>
          <p>No messages sent yet</p>
        </div>
      <?php else: ?>
        <?php foreach ($adminMessages as $msg): ?>
        <div class="thread-item">
          <div class="thread-meta">
            <span class="thread-subject"><?= htmlspecialchars($msg['subject']) ?></span>
            <span class="badge <?= htmlspecialchars($msg['status']) ?>"><?= htmlspecialchars($msg['status']) ?></span>
            <span class="thread-date"><i class="bi bi-clock" style="margin-right:4px;"></i><?= htmlspecialchars($msg['created_at']) ?></span>
          </div>

          <div style="font-size:12px; color:#94a3b8; margin-bottom:8px;">
            <i class="bi bi-person"></i> Sent by you
          </div>

          <div class="thread-body"><?= nl2br(htmlspecialchars($msg['message'])) ?></div>

          <?php if ($msg['super_admin_reply']): ?>
            <div class="thread-reply-label"><i class="bi bi-reply-all"></i> Super Admin Reply</div>
            <div class="super-admin-reply"><?= nl2br(htmlspecialchars($msg['super_admin_reply'])) ?></div>
            <?php if ($msg['replied_at']): ?>
              <div style="font-size:12px; color:#94a3b8; margin-top:4px;">
                <i class="bi bi-check2-all" style="color:#7393A7;"></i> Replied on <?= htmlspecialchars($msg['replied_at']) ?>
              </div>
            <?php endif; ?>
          <?php else: ?>
            <div style="font-size:12px; color:#f59e0b; margin-top:10px;">
              <i class="bi bi-hourglass-split"></i> Awaiting super admin reply
            </div>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

  </div><!-- /container -->
</section>

<script src="source/sidebar.js"></script>
<script src="source/homepage.js"></script>
<script>
// Tab switching
function switchTab(tab) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + tab).classList.add('active');
    event.currentTarget.classList.add('active');
}

// Expand/collapse inline reply forms
function toggleReply(id, btn) {
    const row = document.getElementById(id);
    const isOpen = row.classList.contains('open');
    // close all
    document.querySelectorAll('.reply-row').forEach(r => r.classList.remove('open'));
    document.querySelectorAll('.reply-toggle').forEach(b => {
        b.innerHTML = '<i class="bi bi-reply"></i> Reply';
    });
    if (!isOpen) {
        row.classList.add('open');
        btn.innerHTML = '<i class="bi bi-x-lg"></i> Close';
    }
}

// Sidebar toggle (mirrors existing pages)
document.addEventListener('DOMContentLoaded', function () {
    const toggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.sidebar');
    if (toggle && sidebar) {
        toggle.addEventListener('click', function () {
            sidebar.classList.toggle('open');
        });
    }
});
</script>
</body>
</html>