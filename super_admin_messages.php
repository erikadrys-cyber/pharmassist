<?php
session_start();
require 'config/connection.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ceo') {
    header("Location: login.php"); exit;
}

$viewId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$flash  = $err = '';

/* =========================
   REPLY TO ADMIN
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_id'])) {
    $replyId  = (int)$_POST['reply_id'];
    $replyMsg = trim($_POST['reply_message'] ?? '');
    $replySub = trim($_POST['reply_subject']  ?? '');

    if ($replyMsg === '' || $replySub === '') {
        $err = 'Subject and message are required.';
    } else {
        $q = $conn->prepare("SELECT name, email, subject FROM admin_messages WHERE message_id = ?");
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
                if (!empty($cfg['reply_to'])) $mail->addReplyTo($cfg['reply_to'], $cfg['from_name']);

                $mail->Subject = $replySub;
                $mail->isHTML(true);
                $mail->Body    = nl2br(htmlspecialchars($replyMsg));
                $mail->AltBody = $replyMsg;

                $mail->send();

                $superAdminId = (int)$_SESSION['id'];
                $upd = $conn->prepare("
                    UPDATE admin_messages
                    SET super_admin_reply = ?, status = 'replied', replied_by = ?, replied_at = NOW()
                    WHERE message_id = ?
                ");
                $upd->bind_param("sii", $replyMsg, $superAdminId, $replyId);
                $upd->execute();
                $upd->close();

                $flash  = 'Reply sent to ' . htmlspecialchars($orig['email']) . '.';
                $viewId = $replyId;

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
$single = null;
if ($viewId > 0) {
    $stmt = $conn->prepare("SELECT * FROM admin_messages WHERE message_id = ?");
    $stmt->bind_param("i", $viewId);
    $stmt->execute();
    $r = $stmt->get_result();
    if ($r->num_rows === 1) $single = $r->fetch_assoc();
    $stmt->close();
}

$rows = [];
if (!$single) {
    $rs = $conn->query("
        SELECT message_id, name, email, subject, status, created_at
        FROM admin_messages
        ORDER BY created_at DESC
    ");
    while ($row = $rs->fetch_assoc()) $rows[] = $row;
}

$totalNew     = 0;
$totalReplied = 0;
$totalAll     = 0;
if (!$single) {
    foreach ($rows as $r) {
        $totalAll++;
        if ($r['status'] === 'new')     $totalNew++;
        if ($r['status'] === 'replied') $totalReplied++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Super Admin | Messages</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,200..800&family=Tinos:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" crossorigin="anonymous">
<link rel="stylesheet" href="plugins/style.css">
<link rel="stylesheet" href="plugins/admin_sidebar.css">
<link rel="icon" href="website_icon/favicon.png" type="image/png" sizes="64x64">
<style>
/* ── Base ── */
body {
    font-family: 'Tinos', serif;
    background-color: #E8ECF1;
    margin: 0;
}

/* ── Layout ── */
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
.home-content i    { color: white; font-size: 20px; cursor: pointer; margin-right: 15px; }
.home-content .text { color: white; font-size: 18px; font-weight: 600; font-family: 'Bricolage Grotesque', sans-serif; }

/* ── Container ── */
.container {
    max-width: 1100px;
    margin: 0 auto;
    padding: 30px 20px;
}

/* ── Stats bar ── */
.stats-bar {
    display: flex;
    gap: 16px;
    margin-top: 90px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}
.stat-chip {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #fff;
    border-radius: 10px;
    padding: 12px 20px;
    box-shadow: 0 2px 6px rgba(0,0,0,.05);
    font-family: 'Bricolage Grotesque', sans-serif;
}
.stat-chip .stat-num {
    font-size: 22px;
    font-weight: 800;
    color: #7393A7;
    line-height: 1;
}
.stat-chip .stat-label {
    font-size: 13px;
    color: #6b7280;
    font-weight: 600;
}
.stat-chip.new-chip .stat-num  { color: #1e3a8a; }
.stat-chip.done-chip .stat-num { color: #256029; }

/* ── Card ── */
.card {
    background: #fff;
    border-radius: 12px;
    padding: 28px;
    box-shadow: 0 2px 8px rgba(0,0,0,.05);
    margin-bottom: 24px;
}

/* ── Typography ── */
h2, th {
    font-family: 'Bricolage Grotesque', sans-serif;
    color: #7393A7 !important;
}
h3 { font-family: 'Bricolage Grotesque', sans-serif; }
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
    padding: 13px 14px;
    border-bottom: 1px solid #e5e7eb;
    text-align: left;
    font-size: 14px;
}
.table thead tr { border-bottom: 2px solid #e2e8f0; }
.table th { font-size: 12px; text-transform: uppercase; letter-spacing: .05em; }
.table tbody tr:hover { background: #f8fafc; transition: background 0.15s; }

/* ── Row with unread highlight ── */
tr.unread td { font-weight: 600; }
tr.unread td:first-child { border-left: 3px solid #7393A7; }

/* ── Badges ── */
.badge {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    font-family: 'Bricolage Grotesque', sans-serif;
}
.badge.new     { background: #e1f0ff; color: #1e3a8a; }
.badge.replied { background: #e8f5e9; color: #256029; }
.badge.closed  { background: #fff4e5; color: #92400e; }

/* ── Form elements ── */
.input, textarea {
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
button[type="submit"] {
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
button[type="submit"]:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 14px rgba(115,147,167,.35);
}

/* ── Message boxes ── */
.message-content {
    white-space: pre-wrap;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 16px;
    background: #fafafa;
    margin: 10px 0;
    font-size: 14px;
    line-height: 1.65;
}
.message-reply {
    white-space: pre-wrap;
    border: 1px solid #c8e6c9;
    border-radius: 8px;
    padding: 16px;
    background: #f0fdf4;
    margin-bottom: 10px;
    font-size: 14px;
    line-height: 1.65;
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
    margin-top: 3%;
    margin-bottom: -4%;
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

/* ── Back link ── */
.back-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #7393A7;
    text-decoration: none;
    font-weight: 700;
    font-family: 'Bricolage Grotesque', sans-serif;
    margin-bottom: 20px;
    font-size: 14px;
    padding: 8px 14px;
    border-radius: 8px;
    transition: background 0.2s, color 0.2s;
}
.back-link:hover { background: #edf4f9; color: #5f7a8d; }

/* ── Detail: section label ── */
.section-label {
    font-family: 'Bricolage Grotesque', sans-serif;
    font-size: 12px;
    font-weight: 700;
    color: #7393A7;
    text-transform: uppercase;
    letter-spacing: .06em;
    margin-bottom: 8px;
}

/* ── Meta ── */
.meta { color: #6b7280; font-size: 13px; margin-top: 10px; }

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
.empty-state p { font-family: 'Bricolage Grotesque', sans-serif; margin: 0; }

/* ── Actions ── */
.actions a {
    text-decoration: none;
    color: #7393A7;
    font-weight: 700;
    font-family: 'Bricolage Grotesque', sans-serif;
    font-size: 13px;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 6px 12px;
    border-radius: 6px;
    transition: background 0.2s;
}
.actions a:hover { background: #edf4f9; }

/* ── Search bar ── */
.search-bar {
    display: flex;
    gap: 10px;
    margin-bottom: 18px;
    align-items: center;
}
.search-bar input {
    flex: 1;
    padding: 10px 14px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    font-family: 'Bricolage Grotesque', sans-serif;
    font-size: 14px;
    transition: border-color 0.2s, box-shadow 0.2s;
}
.search-bar input:focus {
    outline: none;
    border-color: #7393A7;
    box-shadow: 0 0 0 3px rgba(115,147,167,.15);
}
.search-bar select {
    padding: 10px 14px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    font-family: 'Bricolage Grotesque', sans-serif;
    font-size: 14px;
    background: #fff;
    cursor: pointer;
}
</style>
</head>
<body>
<?php include 'super_admin_sidebar.php'; ?>

<section class="home-section">
  <div class="home-content">
    <i class='bx bx-menu' id="sidebarToggle"></i>
    <span class="text"> PharmAssist</span>
  </div>

  <div class="container">

    <?php if ($flash): ?>
      <div class="success-message"><i class="bi bi-check-circle-fill"></i> <?= $flash ?></div>
    <?php endif; ?>
    <?php if ($err): ?>
      <div class="error-message"><i class="bi bi-exclamation-circle-fill"></i> <?= $err ?></div>
    <?php endif; ?>

    <?php if ($single): ?>
    <!-- ════════════════════════════
         DETAIL / REPLY VIEW
    ════════════════════════════ -->
    <a href="super_admin_messages.php" class="back-link" style="margin-top:90px; display:inline-flex;">
      <i class="bi bi-arrow-left"></i> Back to all messages
    </a>

    <div class="card">
      <!-- Header -->
      <div style="display:flex; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; gap:12px; margin-bottom:20px;">
        <div>
          <h2 style="margin:0; font-size:1.4rem;">
            <i class="bi bi-envelope-open"></i>
            Message #<?= (int)$single['message_id'] ?>
          </h2>
          <p class="meta">
            From: <strong><?= htmlspecialchars($single['name']) ?></strong>
            &lt;<?= htmlspecialchars($single['email']) ?>&gt; &nbsp;·&nbsp;
            <?= htmlspecialchars($single['created_at']) ?>
          </p>
        </div>
        <span class="badge <?= htmlspecialchars($single['status']) ?>" style="align-self:flex-start; font-size:13px; padding:6px 14px;">
          <?= htmlspecialchars($single['status']) ?>
        </span>
      </div>

      <!-- Subject + Message -->
      <div class="section-label">Subject</div>
      <p style="margin:0 0 16px 0; font-size:15px; font-weight:600; color:#334155;">
        <?= htmlspecialchars($single['subject']) ?>
      </p>

      <div class="section-label">Message</div>
      <div class="message-content"><?= htmlspecialchars($single['message']) ?></div>

      <!-- Existing reply if any -->
      <?php if ($single['super_admin_reply']): ?>
        <div style="margin-top:24px;">
          <div class="section-label" style="color:#256029;">Your Previous Reply</div>
          <div class="message-reply"><?= htmlspecialchars($single['super_admin_reply']) ?></div>
          <?php if ($single['replied_at']): ?>
            <p style="font-size:12px; color:#94a3b8; margin:4px 0 0 0;">
              <i class="bi bi-check2-all" style="color:#7393A7;"></i>
              Sent on <?= htmlspecialchars($single['replied_at']) ?>
            </p>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <hr style="border:none; border-top:1px solid #e5e7eb; margin:28px 0;">

      <!-- Reply form -->
      <h3 style="margin:0 0 20px 0; font-size:1.1rem;">
        <i class="bi bi-reply" style="color:#7393A7;"></i> Send Reply
      </h3>
      <form method="post">
        <input type="hidden" name="reply_id" value="<?= (int)$single['message_id'] ?>">
        <div style="display:grid; gap:16px;">
          <div>
            <label style="margin-top:0;">Reply Subject</label>
            <input class="input" name="reply_subject"
                   value="Re: <?= htmlspecialchars($single['subject']) ?>" required>
          </div>
          <div>
            <label>Reply Message</label>
            <textarea name="reply_message" rows="8" required
                      placeholder="Type your reply here..."></textarea>
          </div>
          <div>
            <button type="submit"><i class="bi bi-send"></i> Send reply via email</button>
          </div>
        </div>
      </form>
    </div>

    <?php else: ?>
    <!-- ════════════════════════════
         INBOX LIST VIEW
    ════════════════════════════ -->

    <!-- Stats chips -->
    <div class="stats-bar">
      <div class="stat-chip">
        <div>
          <div class="stat-num"><?= $totalAll ?></div>
          <div class="stat-label">Total</div>
        </div>
      </div>
      <div class="stat-chip new-chip">
        <div>
          <div class="stat-num"><?= $totalNew ?></div>
          <div class="stat-label">Unread</div>
        </div>
      </div>
      <div class="stat-chip done-chip">
        <div>
          <div class="stat-num"><?= $totalReplied ?></div>
          <div class="stat-label">Replied</div>
        </div>
      </div>
    </div>

    <div class="card" style="margin-top:0;">
      <h2 style="margin:0 0 6px 0; font-size:1.4rem;">
        <i class="bi bi-chat-dots"></i> Admin Messages
      </h2>
      <p style="color:#6C737E; margin:0 0 20px 0; font-size:14px;">
        Messages sent from branch admins to you.
      </p>

      <!-- Search / filter -->
      <div class="search-bar">
        <input type="text" id="searchInput" placeholder="Search by name, email or subject…" onkeyup="filterTable()">
        <select id="statusFilter" onchange="filterTable()">
          <option value="">All statuses</option>
          <option value="new">New</option>
          <option value="replied">Replied</option>
          <option value="closed">Closed</option>
        </select>
      </div>

      <?php if (empty($rows)): ?>
        <div class="empty-state">
          <i class="bi bi-inbox"></i>
          <p>No messages at the moment</p>
        </div>
      <?php else: ?>
        <table class="table" id="msgTable">
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
          <?php foreach ($rows as $row): ?>
            <tr class="<?= $row['status'] === 'new' ? 'unread' : '' ?>" data-search="<?= strtolower(htmlspecialchars($row['name'] . ' ' . $row['email'] . ' ' . $row['subject'])) ?>" data-status="<?= htmlspecialchars($row['status']) ?>">
              <td><?= (int)$row['message_id'] ?></td>
              <td><?= htmlspecialchars($row['name']) ?></td>
              <td><?= htmlspecialchars($row['email']) ?></td>
              <td><?= htmlspecialchars($row['subject']) ?></td>
              <td><span class="badge <?= htmlspecialchars($row['status']) ?>"><?= htmlspecialchars($row['status']) ?></span></td>
              <td style="font-size:13px; color:#6b7280;"><?= htmlspecialchars($row['created_at']) ?></td>
              <td class="actions">
                <a href="super_admin_messages.php?id=<?= (int)$row['message_id'] ?>">
                  <i class="bi bi-eye"></i> View / Reply
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

    <?php endif; ?>

  </div><!-- /container -->
</section>

<script src="source/sidebar.js"></script>
<script src="source/homepage.js"></script>
<script>
// Sidebar toggle
document.addEventListener('DOMContentLoaded', function () {
    const toggle  = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.sidebar');
    if (toggle && sidebar) {
        toggle.addEventListener('click', function () {
            sidebar.classList.toggle('open');
        });
    }
});

// Live search + status filter
function filterTable() {
    const search = document.getElementById('searchInput')?.value.toLowerCase() ?? '';
    const status = document.getElementById('statusFilter')?.value ?? '';
    document.querySelectorAll('#msgTable tbody tr').forEach(row => {
        const text   = row.dataset.search ?? '';
        const rowSt  = row.dataset.status ?? '';
        const matchS = search === '' || text.includes(search);
        const matchF = status === '' || rowSt === status;
        row.style.display = matchS && matchF ? '' : 'none';
    });
}
</script>
</body>
</html>