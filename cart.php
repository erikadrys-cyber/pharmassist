<?php
include 'config/connection.php';
session_start();

if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION['role'] !== 'customer') {
    header("Location: dashboard.php");
    exit();
}

if (!isset($_SESSION['reservation_cart']) || !is_array($_SESSION['reservation_cart'])) {
    $_SESSION['reservation_cart'] = [];
}

if (isset($_GET['remove'])) {
    $removeIndex = intval($_GET['remove']);
    if (isset($_SESSION['reservation_cart'][$removeIndex])) {
        unset($_SESSION['reservation_cart'][$removeIndex]);
        $_SESSION['reservation_cart'] = array_values($_SESSION['reservation_cart']);
    }
    header("Location: cart.php");
    exit();
}

if (isset($_GET['clear']) && $_GET['clear'] == '1') {
    $_SESSION['reservation_cart'] = [];
    header("Location: cart.php");
    exit();
}

// AJAX quantity update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_quantity') {
    header('Content-Type: application/json');
    $updateIndex = intval($_POST['item_index'] ?? -1);
    $newQty = max(1, intval($_POST['quantity'] ?? 1));
    if (isset($_SESSION['reservation_cart'][$updateIndex])) {
        $_SESSION['reservation_cart'][$updateIndex]['quantity'] = $newQty;
        echo json_encode(['success' => true, 'quantity' => $newQty]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit();
}

// AJAX reserve_by update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_reserve_by') {
    header('Content-Type: application/json');
    $updateIndex = intval($_POST['item_index'] ?? -1);
    $reserveBy   = in_array($_POST['reserve_by'] ?? '', ['piece','10pieces','box']) ? $_POST['reserve_by'] : 'piece';
    if (isset($_SESSION['reservation_cart'][$updateIndex])) {
        $_SESSION['reservation_cart'][$updateIndex]['reserve_by'] = $reserveBy;
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit();
}

$cartItems = $_SESSION['reservation_cart'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation Cart | PharmAssist</title>
    <link rel="stylesheet" href="plugins/sidebar.css?v=3">
    <link rel="stylesheet" href="plugins/footer.css?v=3">
    <link rel="icon" href="website_icon/favicon.png" type="image/png" sizes="64x64">
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,200..800&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Tinos', serif;
            background-color: #E8ECF1;
            margin: 0;
        }

        .home-content, .text, .section-title { color: white; font-size: 18px; font-weight: 600; font-family: 'Bricolage Grotesque', sans-serif; }

        :root {
            --bg:         #E8ECF1;
            --surface:    #ffffff;
            --border:     #d4dde8;
            --navy:       #42596f;
            --slate:      #6C737E;
            --slate-lt:   #f4f6f9;
            --blue:       #7393A7;
            --blue-dk:    #5e85a0;
            --blue-lt:    #eef4f8;
            --blue-mid:   #c3d4de;
            --green:      #2e7d54;
            --green-lt:   #edf7f1;
            --red:        #c0392b;
            --red-lt:     #fdf2f1;
            --amber:      #b45309;
            --amber-lt:   #fdf8ef;
            --amber-mid:  #f5dfa8;
            --shadow-sm:  0 2px 8px rgba(45,63,80,.07);
            --shadow-md:  0 6px 20px rgba(45,63,80,.11);
            --radius:     12px;
            --radius-sm:  8px;
            --font:       'Bricolage Grotesque', sans-serif;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: var(--font); background: var(--bg); color: var(--navy); }

        /* ── PAGE WRAPPER FOR ZOOM ── */
        .page-wrapper {
            transform-origin: top center;
            transition: transform 0.2s ease-out;
        }

        /* ── WRAPPER ── */
        .cart-wrapper {
            padding: 68px 28px 48px;
            max-width: 1120px;
            margin: 0 auto;
        }

        /* ── PAGE HEADER ── */
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            background: var(--surface);
            padding: 20px 24px;
            border-radius: var(--radius);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            margin-bottom: 24px;
        }

        .page-header-left h1 {
            font-size: 1.65rem;
            font-weight: 700;
            color: var(--navy);
            letter-spacing: -.3px;
        }

        .page-header-left p {
            color: var(--slate);
            font-size: .85rem;
            margin-top: 2px;
        }

        .header-actions { display: flex; gap: 10px; }

        .btn-outline {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 7px 15px;
            border-radius: var(--radius-sm);
            font-size: .82rem; font-weight: 600;
            cursor: pointer; transition: all .15s;
            text-decoration: none; border: 1.5px solid;
        }

        .btn-outline-blue {
            color: var(--blue-dk); border-color: var(--blue-mid);
            background: var(--blue-lt);
        }
        .btn-outline-blue:hover { background: #dde9f0; color: var(--navy); }

        .btn-outline-red {
            color: var(--red); border-color: #e8c5c2;
            background: var(--red-lt);
        }
        .btn-outline-red:hover { background: #f9e5e3; }

        /* ── LAYOUT ── */
        .cart-layout {
            display: grid;
            grid-template-columns: 1fr 360px;
            gap: 20px;
            align-items: start;
        }

        @media (max-width: 940px) { .cart-layout { grid-template-columns: 1fr; } }

        /* ── EMPTY STATE ── */
        .empty-state {
            background: var(--surface);
            border-radius: var(--radius);
            border: 1.5px dashed var(--border);
            padding: 60px 40px;
            text-align: center;
        }

        .empty-icon {
            width: 64px; height: 64px;
            background: var(--blue-lt);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 18px;
            font-size: 1.7rem; color: var(--blue-dk);
        }

        .empty-state h3 { font-size: 1.25rem; color: var(--navy); margin-bottom: 6px; }
        .empty-state p  { color: var(--slate); margin-bottom: 18px; font-size: .9rem; }

        /* ── CART ITEMS ── */
        .items-list { display: flex; flex-direction: column; gap: 14px; }

        .cart-item {
            background: var(--surface);
            border-radius: var(--radius);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            padding: 18px;
            display: flex;
            gap: 16px;
            align-items: flex-start;
            transition: box-shadow .18s, border-color .18s;
        }

        .cart-item:hover { box-shadow: var(--shadow-md); border-color: var(--blue-mid); }

        .item-thumb {
            flex-shrink: 0;
            width: 80px; height: 80px;
            border-radius: var(--radius-sm);
            overflow: hidden;
            border: 1px solid var(--border);
            background: var(--slate-lt);
        }

        .item-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }

        .item-body { flex: 1; min-width: 0; }

        .item-name {
            font-weight: 700; font-size: .97rem; color: var(--navy);
            margin-bottom: 6px; line-height: 1.35;
        }

        .item-badges { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 10px; }

        .badge-pill {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: .71rem; font-weight: 600;
            letter-spacing: .2px;
        }

        .badge-rx-yes  { background: var(--red-lt);   color: var(--red);   border: 1px solid #e8c5c2; }
        .badge-rx-no   { background: var(--green-lt);  color: var(--green); border: 1px solid #b2d9c3; }
        .badge-presc   { background: var(--green-lt);  color: var(--green); border: 1px solid #b2d9c3; }

        /* ── RESERVE BY ── */
        .reserve-by-block {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
            margin-bottom: 10px;
            padding: 8px 10px;
            background: var(--slate-lt);
            border-radius: var(--radius-sm);
            border: 1px solid var(--border);
        }

        .reserve-by-label {
            font-size: .74rem; font-weight: 700;
            color: var(--slate); text-transform: uppercase;
            letter-spacing: .4px; margin-right: 2px;
            white-space: nowrap;
        }

        .reserve-by-opts { display: flex; gap: 6px; flex-wrap: wrap; }

        .rb-opt { position: relative; }
        .rb-opt input { position: absolute; opacity: 0; width: 0; height: 0; }

        .rb-opt label {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 4px 10px;
            border-radius: 999px;
            border: 1px solid var(--border);
            background: var(--surface);
            font-size: .74rem; font-weight: 600;
            color: var(--slate);
            cursor: pointer;
            transition: all .14s;
            white-space: nowrap;
        }

        .rb-opt label:hover { background: var(--blue-lt); border-color: var(--blue-mid); color: var(--blue-dk); }

        .rb-opt input:checked + label {
            background: var(--navy);
            border-color: var(--navy);
            color: #fff;
            box-shadow: 0 2px 8px rgba(45,63,80,.18);
        }

        /* ── */

        .item-meta {
            display: flex; align-items: center; gap: 10px;
            flex-wrap: wrap;
        }

        .meta-unit-price { font-size: .82rem; color: var(--slate); }
        .meta-unit-price strong { color: var(--navy); }

        .item-line-total {
            font-size: 1rem; font-weight: 700; color: var(--blue-dk);
            margin-left: auto;
        }

        /* Qty stepper */
        .qty-stepper {
            display: inline-flex; align-items: center;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            overflow: hidden;
            background: var(--surface);
        }

        .qty-btn {
            width: 30px; height: 30px;
            display: flex; align-items: center; justify-content: center;
            background: var(--slate-lt);
            border: none; cursor: pointer;
            font-size: 1rem; color: var(--slate);
            transition: background .12s, color .12s;
        }

        .qty-btn:hover  { background: var(--blue-lt); color: var(--blue-dk); }
        .qty-btn:active { transform: scale(.9); }

        .qty-display {
            width: 38px; height: 30px;
            text-align: center; border: none;
            font-weight: 700; font-size: .9rem; color: var(--navy);
            background: transparent; outline: none; cursor: default;
        }

        .remove-btn {
            flex-shrink: 0;
            width: 30px; height: 30px;
            border-radius: var(--radius-sm);
            border: 1px solid #e8c5c2;
            background: var(--red-lt);
            color: var(--red);
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; font-size: .9rem;
            transition: all .15s;
        }

        .remove-btn:hover { background: #f5d5d2; }

        .vat-note {
            margin-top: 6px;
            font-size: .72rem; color: var(--slate);
        }

        /* ── SUMMARY CARD ── */
        .summary-card {
            background: var(--surface);
            border-radius: var(--radius);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            position: sticky;
            top: 76px;
        }

        .summary-head {
            padding: 16px 20px 13px;
            border-bottom: 1px solid var(--border);
            background: #7393A7;
        }

        .summary-head h3 { font-size: 1.05rem; color: #fff; font-weight: 700; font-family: 'Bricolage Grotesque'; }
        .summary-head p  { font-size: .78rem; color: rgba(255,255,255,.55); margin-top: 2px; }

        .summary-body { padding: 18px 20px; }

        /* Breakdown */
        .breakdown { display: flex; flex-direction: column; gap: 7px; margin-bottom: 14px; }

        .bk-row {
            display: flex; justify-content: space-between;
            font-size: .85rem; color: var(--slate);
        }

        .bk-row.deduct  { color: var(--red); }

        .bk-row.total-row {
            font-size: 1rem; font-weight: 700; color: var(--navy);
            padding-top: 10px;
            border-top: 1.5px solid var(--border);
        }

        .bk-row.total-row .total-amt { color: var(--blue-dk); font-size: 1.2rem; }

        /* Form */
        .form-section-title {
            font-size: .72rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: .6px; color: var(--slate);
            margin: 16px 0 9px;
        }

        .form-field { margin-bottom: 11px; }

        .form-field label {
            display: block; font-size: .8rem; font-weight: 600;
            color: #374151; margin-bottom: 4px;
        }

        .form-field input,
        .form-field textarea,
        .form-field select {
            width: 100%; padding: 8px 11px;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border);
            font-family: var(--font); font-size: .85rem;
            color: var(--navy); background: var(--surface);
            transition: border-color .14s, box-shadow .14s;
        }

        .form-field input:focus,
        .form-field textarea:focus {
            outline: none;
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(115,147,167,.15);
        }

        .form-field small {
            display: block; margin-top: 3px;
            color: var(--slate); font-size: .74rem; line-height: 1.4;
        }

        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 9px; }

        .presc-upload-item {
            background: var(--red-lt);
            border: 1px dashed #e8c5c2;
            border-radius: var(--radius-sm);
            padding: 11px; margin-bottom: 9px;
        }

        .presc-upload-item label {
            font-size: .78rem; font-weight: 700; color: var(--red);
            margin-bottom: 5px; display: block;
        }

        .presc-upload-item input[type="file"] {
            border-color: #e8c5c2; background: #fff; font-size: .78rem;
        }

        /* TTS */
        .tts-row { display: flex; gap: 7px; margin-bottom: 13px; }

        .tts-mini-btn {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 6px 11px; border-radius: var(--radius-sm);
            border: 1px solid var(--border); background: var(--slate-lt);
            font-size: .78rem; font-weight: 600; color: var(--slate);
            cursor: pointer; transition: all .14s;
        }

        .tts-mini-btn:hover { background: var(--blue-lt); border-color: var(--blue-mid); color: var(--blue-dk); }

        /* Submit */
        .submit-btn {
            width: 100%; padding: 12px;
            background: #7393A7;
            color: #fff; border: none; border-radius: var(--radius-sm);
            font-family: var(--font); font-size: .92rem; font-weight: 700;
            cursor: pointer; letter-spacing: .2px;
            transition: all .18s;
            display: flex; align-items: center; justify-content: center; gap: 7px;
        }

        .submit-btn:hover { background: var(--blue-dk); transform: translateY(-1px); box-shadow: var(--shadow-md); }
        .submit-btn:active { transform: translateY(0); }
        .submit-btn:disabled { opacity: .55; pointer-events: none; }

        /* ── MODAL ── */
        .modal-backdrop-custom {
            display: none; position: fixed; inset: 0;
            background: rgba(15,25,40,.5);
            z-index: 30010;
            align-items: center; justify-content: center; padding: 20px;
            backdrop-filter: blur(4px);
        }

        .modal-backdrop-custom.show { display: flex; }

        .modal-card-custom {
            background: #fff; border-radius: 16px;
            box-shadow: var(--shadow-md);
            padding: 32px 26px; text-align: center;
            max-width: 380px; width: 100%;
            animation: popIn .22s cubic-bezier(.34,1.56,.64,1);
        }

        @keyframes popIn {
            from { opacity: 0; transform: scale(.9) translateY(14px); }
            to   { opacity: 1; transform: scale(1) translateY(0); }
        }

        .modal-check {
            width: 64px; height: 64px; border-radius: 50%;
            background: var(--green-lt); color: var(--green);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.9rem; margin: 0 auto 16px;
            border: 1.5px solid #b2d9c3;
        }

        .modal-card-custom h3 { font-size: 1.35rem; color: var(--navy); margin-bottom: 7px; }
        .modal-card-custom p  { color: var(--slate); font-size: .87rem; line-height: 1.6; margin-bottom: 20px; }

        .modal-close-btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 9px 22px;
            border-radius: var(--radius-sm);
            background: var(--navy); color: #fff;
            border: none;
            font-family: var(--font); font-weight: 700; font-size: .88rem;
            cursor: pointer; transition: all .14s;
        }

        .modal-close-btn:hover { background: var(--blue-dk); }

        /* ── TOAST ── */
        .page-toast {
            position: fixed; right: 18px; top: 18px;
            z-index: 30000; min-width: 190px; max-width: 280px;
            padding: 11px 15px; border-radius: 9px;
            color: #fff; font-weight: 600; font-size: .84rem;
            box-shadow: var(--shadow-md);
            opacity: 0; transform: translateY(-8px);
            transition: opacity .18s, transform .18s;
            pointer-events: none;
        }

        .page-toast.show { opacity: 1; transform: translateY(0); }
        .page-toast.success { background: var(--green); }
        .page-toast.info    { background: var(--blue-dk); }
        .page-toast.error   { background: var(--red); }

        .qty-updating { opacity: .45; pointer-events: none; }

        /* ── FLOATING ACTION BUTTONS ── */
        .floating-buttons {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 999;
            display: flex;
            flex-direction: column;
            gap: 12px;
            align-items: center;
        }

        .fab {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #7393A7;
            color: white;
            border: none;
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .fab:hover {
            background: #5B7A92;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
            transform: scale(1.1);
        }

        .fab:active {
            transform: scale(0.95);
        }

        .fab:disabled {
            background: #ccc;
            cursor: not-allowed;
            opacity: 0.6;
        }

        #zoomInBtn { animation: slideIn 0.4s ease 0s; }
        #zoomOutBtn { animation: slideIn 0.4s ease 0.1s; }
        #guideBtn { animation: slideIn 0.4s ease 0.2s; }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ── GUIDE MODAL STYLES ── */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .modal-overlay.active {
            display: flex;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            width: 90%;
            max-width: 850px;
            height: 75vh;
            display: flex;
            flex-direction: column;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            position: relative;
            overflow: hidden;
            animation: slideUp 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from {
                transform: translateY(30px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            background: #7393A7;
            padding: 25px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
            border-radius: 12px 12px 0 0;
        }

        .modal-header h2 {
            color: white;
            font-family: "Bricolage Grotesque", sans-serif;
            font-weight: 600;
            font-size: 24px;
            margin: 0;
        }

        .modal-close {
            background: none;
            border: none;
            color: white;
            font-size: 32px;
            cursor: pointer;
            padding: 0;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: opacity 0.2s;
            line-height: 1;
        }

        .modal-close:hover { opacity: 0.7; }

        .modal-inner {
            flex: 1;
            overflow-y: auto;
            padding: 30px;
            background: white;
        }

        .modal-inner::-webkit-scrollbar {
            width: 8px;
        }

        .modal-inner::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .modal-inner::-webkit-scrollbar-thumb {
            background: #7393A7;
            border-radius: 4px;
        }

        .modal-inner::-webkit-scrollbar-thumb:hover {
            background: #5B7A92;
        }

        .modal-tts-bar {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #F8FAFC;
            border: 1px solid #D9EAFD;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 25px;
        }

        .modal-tts-bar span {
            font-size: 13px;
            color: #7393A7;
            font-family: "Bricolage Grotesque", sans-serif;
            flex: 1;
        }

        .modal-tts-btn, .modal-tts-stop-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 11px;
            padding: 6px 12px;
            border-radius: 16px;
            border: 1px solid #BCCCDC;
            background: #D9EAFD;
            color: #2d3f50;
            cursor: pointer;
            font-family: "Bricolage Grotesque", sans-serif;
            transition: background 0.2s ease;
        }

        .modal-tts-btn:hover { background: #BCCCDC; }
        .modal-tts-stop-btn { background: #F8FAFC; }
        .modal-tts-stop-btn:hover { background: #D9EAFD; }

        .modal-tts-step-btn {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 10px;
            padding: 4px 10px;
            border-radius: 16px;
            border: 1px solid #BCCCDC;
            background: #F8FAFC;
            color: #7393A7;
            cursor: pointer;
            font-family: "Bricolage Grotesque", sans-serif;
            margin-top: 10px;
            transition: background 0.2s ease;
        }

        .modal-tts-step-btn:hover { background: #D9EAFD; }
        .modal-tts-step-btn.reading {
            background: #D9EAFD;
            border-color: #9AA6B2;
            color: #2d3f50;
        }

        /* Tab Styles */
        .modal-tabs {
            display: flex;
            gap: 0;
            border-bottom: 2px solid #e8ecf1;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .modal-tab-btn {
            padding: 12px 20px;
            background: none;
            border: none;
            color: #7393A7;
            font-family: "Bricolage Grotesque", sans-serif;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
            position: relative;
            top: 2px;
        }

        .modal-tab-btn:hover {
            color: #5B7A92;
        }

        .modal-tab-btn.active {
            color: #2d3f50;
            border-bottom-color: #7393A7;
        }

        .modal-tab-content {
            display: none;
        }

        .modal-tab-content.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        .multimodal-info {
            background: #F8FAFC;
            padding: 15px;
            border-left: 4px solid #7393A7;
            border-radius: 6px;
            margin: 15px 0;
            font-size: 14px;
            color: #5A6B7A;
        }

        .multimodal-info h4 {
            color: #2d3f50;
            margin-top: 0;
            margin-bottom: 8px;
            font-family: "Bricolage Grotesque", sans-serif;
        }

        .multimodal-info ul {
            margin: 8px 0 0 20px;
            padding: 0;
        }

        .multimodal-info li {
            margin-bottom: 5px;
        }

        .tts-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #9AA6B2;
            display: inline-block;
            animation: tts-pulse 1s infinite;
        }

        @keyframes tts-pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }

        .modal-step {
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid #E8EEF5;
        }

        .modal-step:last-child {
            border-bottom: none;
        }

        .modal-step h3 {
            color: #6C737E;
            font-size: 16px;
            margin-bottom: 12px;
            margin-top: 0;
            font-family: 'Bricolage Grotesque', sans-serif;
            font-weight: 600;
        }

        .modal-step p {
            font-size: 14px;
            color: #5A6B7A;
            margin-bottom: 10px;
            line-height: 1.5;
        }

        .modal-step ul {
            margin-left: 18px;
            margin-bottom: 12px;
            font-size: 14px;
            color: #5A6B7A;
        }

        .modal-step ul li {
            margin-bottom: 5px;
        }

        .modal-rules {
            background: #F4F8FA;
            padding: 15px;
            border-left: 5px solid #7393A7;
            border-radius: 6px;
            margin-top: 20px;
        }

        .modal-rules h3 {
            margin-top: 0;
            color: #6C737E;
            font-family: 'Bricolage Grotesque', sans-serif;
            font-weight: 600;
        }

        .modal-rules ul {
            margin-left: 18px;
            font-size: 13px;
            color: #5A6B7A;
        }

        .modal-rules ul li {
            margin-bottom: 6px;
        }

        .modal-step-image-placeholder {
            background: #F4F8FA;
            border: 2px dashed #D9EAFD;
            border-radius: 8px;
            padding: 20px;
            margin: 15px 0;
            text-align: center;
            min-height: 150px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #9AA6B2;
            font-size: 14px;
            font-family: "Bricolage Grotesque", sans-serif;
        }

        @media (max-width: 600px) {
            .cart-wrapper { padding: 68px 12px 40px; }
            .form-row     { grid-template-columns: 1fr; }
            .item-thumb   { width: 64px; height: 64px; }
        }
    </style>
</head>
<body>
<?php include 'sidebar.php'; ?>

<section class="home-section">
    <div class="home-content">
        <div style="display:flex; align-items:center; gap:10px;">
            <i class='bx bx-menu'></i>
            <span><a href="homepage.php" style="text-decoration:none;color:white;font-size:1.5rem;" class="text fw-semibold">PharmAssist</a></span>
        </div>
    </div>

    <div class="page-wrapper">
        <div class="cart-wrapper">

            <!-- Page Header -->
            <div class="page-header">
                <div class="page-header-left">
                    <h1 style="font-family: 'Bricolage Grotesque';">Reservation Cart</h1>
                    <p><?php echo count($cartItems); ?> item<?php echo count($cartItems) !== 1 ? 's' : ''; ?> in your cart</p>
                </div>
                <div class="header-actions">
                    <a href="medicines.php" class="btn-outline btn-outline-blue"><i class="bi bi-plus-circle"></i> Add More</a>
                    <?php if (!empty($cartItems)): ?>
                    <a href="cart.php?clear=1" class="btn-outline btn-outline-red" onclick="return confirm('Clear all cart items?');"><i class="bi bi-trash"></i> Clear Cart</a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (empty($cartItems)): ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="bi bi-cart3"></i></div>
                    <h3>Your cart is empty</h3>
                    <p>Browse our medicines and add items to get started.</p>
                    <a href="medicines.php" class="btn-outline btn-outline-blue"><i class="bi bi-capsule"></i> Browse Medicines</a>
                </div>

            <?php else:
                $grandTotal = 0;
                foreach ($cartItems as $item) {
                    $reserveBy  = $item['reserve_by'] ?? 'piece';
                    $multiplier = ($reserveBy === 'box') ? 100 : (($reserveBy === '10pieces') ? 10 : 1);
                    $total = floatval($item['price_per_piece']) * intval($item['quantity']) * $multiplier;
                    $grandTotal += $total;
                }
            ?>

            <div class="cart-layout">
                <!-- LEFT: Items -->
                <div>
                <!-- TTS: Cart Items -->
                <div class="modal-tts-bar" style="margin-bottom:14px;">
                    <span><span class="tts-dot" id="itemsTtsDot" style="display:none;"></span> Read all items in your cart aloud</span>
                    <button type="button" class="modal-tts-btn" id="cartReadItems"><i class="bi bi-play-fill"></i> Read Items</button>
                    <button type="button" class="modal-tts-stop-btn" id="cartStopItems"><i class="bi bi-stop-fill"></i> Stop</button>
                </div>
                <div class="items-list">
                    <?php foreach ($cartItems as $index => $item):
                        $pricePerPiece = floatval($item['price_per_piece']);
                        $qty           = intval($item['quantity']);
                        $reserveBy     = $item['reserve_by'] ?? 'piece';
                        $multiplier    = ($reserveBy === 'box') ? 100 : (($reserveBy === '10pieces') ? 10 : 1);
                        $lineTotal     = $pricePerPiece * $qty * $multiplier;
                        $requiresPrescription  = ($item['requires_prescription'] ?? 'no') === 'yes';
                        $hasStoredPrescription = !empty($item['prescription_file']);
                        $medImage  = !empty($item['image_path']) ? htmlspecialchars($item['image_path']) : 'uploads/default.jpg';
                        $reserveBy = $item['reserve_by'] ?? 'piece';
                    ?>
                <div class="cart-item" data-index="<?php echo $index; ?>" id="cart-item-<?php echo $index; ?>">
                        <input type="hidden" name="items[<?php echo $index; ?>][branch_id]" value="<?php echo $item['branch_id'] ?? ''; ?>">
                        <div class="item-thumb">
                            <img src="<?php echo $medImage; ?>" alt="<?php echo htmlspecialchars($item['medicine']); ?>">
                        </div>

                        <div class="item-body">
                            <div class="item-name"><?php echo htmlspecialchars($item['medicine']); ?></div>

                            <div class="item-badges">
                                <?php if ($requiresPrescription): ?>
                                    <span class="badge-pill badge-rx-yes"><i class="bi bi-file-medical"></i> Rx Required</span>
                                <?php else: ?>
                                    <span class="badge-pill badge-rx-no"><i class="bi bi-check-circle"></i> No Rx Needed</span>
                                <?php endif; ?>
                                <?php if ($hasStoredPrescription): ?>
                                    <span class="badge-pill badge-presc"><i class="bi bi-paperclip"></i> Rx Uploaded</span>
                                <?php endif; ?>
                            </div>

                            <!-- Reserve By Options -->
                            <div class="reserve-by-block">
                                <span class="reserve-by-label">Reserve by</span>
                                <div class="reserve-by-opts">
                                    <div class="rb-opt">
                                        <input type="radio" name="reserve_by_<?php echo $index; ?>" id="rb_piece_<?php echo $index; ?>" value="piece"
                                            <?php echo $reserveBy === 'piece' ? 'checked' : ''; ?>
                                            onchange="updateReserveBy(<?php echo $index; ?>, 'piece')">
                                        <label for="rb_piece_<?php echo $index; ?>">
                                            <i class="bi bi-capsule"></i> Per piece <span style="color:inherit;opacity:.7;font-weight:400;">(per tablet / unit)</span>
                                        </label>
                                    </div>
                                    <div class="rb-opt">
                                        <input type="radio" name="reserve_by_<?php echo $index; ?>" id="rb_10_<?php echo $index; ?>" value="10pieces"
                                            <?php echo $reserveBy === '10pieces' ? 'checked' : ''; ?>
                                            onchange="updateReserveBy(<?php echo $index; ?>, '10pieces')">
                                        <label for="rb_10_<?php echo $index; ?>">
                                            <i class="bi bi-stack"></i> Per 10 pieces
                                        </label>
                                    </div>
                                    <div class="rb-opt">
                                        <input type="radio" name="reserve_by_<?php echo $index; ?>" id="rb_box_<?php echo $index; ?>" value="box"
                                            <?php echo $reserveBy === 'box' ? 'checked' : ''; ?>
                                            onchange="updateReserveBy(<?php echo $index; ?>, 'box')">
                                        <label for="rb_box_<?php echo $index; ?>">
                                            <i class="bi bi-box-seam"></i> Per box
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="item-meta">
                                <div class="qty-stepper" id="stepper-<?php echo $index; ?>">
                                    <button class="qty-btn" type="button" onclick="changeQty(<?php echo $index; ?>, -1)" title="Decrease">&#8722;</button>
                                    <input class="qty-display" type="text" id="qty-<?php echo $index; ?>" value="<?php echo $qty; ?>" readonly>
                                    <button class="qty-btn" type="button" onclick="changeQty(<?php echo $index; ?>, 1)" title="Increase">+</button>
                                </div>

                                <?php
                                    $unitLabel = ($reserveBy === 'box') ? '/box (100 pcs)' : (($reserveBy === '10pieces') ? '/10 pcs' : '/pc');
                                ?>
                                    <span class="meta-unit-price" id="unit-price-<?php echo $index; ?>">@ <strong>&#8369;<?php echo number_format($pricePerPiece * $multiplier, 2); ?></strong><?php echo $unitLabel; ?></span>

                                    <span class="item-line-total" id="line-total-<?php echo $index; ?>">&#8369;<?php echo number_format($lineTotal, 2); ?></span>
                                <button class="remove-btn" onclick="removeItem(<?php echo $index; ?>)" title="Remove item">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </div>


                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                </div>

                <!-- RIGHT: Summary + Form -->
                <div>
                    <div class="summary-card">
                        <div class="summary-head">
                            <h3>Order Summary</h3>
                            <p>Review and complete your reservation</p>
                        </div>

                        <div class="summary-body">

                            <!-- Breakdown -->
                            <div class="breakdown">
                                <div class="bk-row total-row">
                                    <span>Total Amount</span>
                                    <span class="total-amt" id="sum-total">&#8369;<?php echo number_format($grandTotal, 2); ?></span>
                                </div>
                            </div>

                            <hr style="border-color:var(--border); margin:0 0 15px;">

                            <!-- Checkout Form -->
                            <form action="submit_reservation.php" method="POST" enctype="multipart/form-data" id="checkoutForm">
                                <input type="hidden" name="mode" value="cart">

                                <div class="form-section-title">Your Details</div>

                                <div class="form-row">
                                    <div class="form-field">
                                        <label>First Name <span style="color:var(--red)">*</span></label>
                                        <input type="text" name="first_name" required placeholder="Juan">
                                    </div>
                                    <div class="form-field">
                                        <label>Last Name <span style="color:var(--red)">*</span></label>
                                        <input type="text" name="last_name" required placeholder="Dela Cruz">
                                    </div>
                                </div>
                                <div class="form-field">
                                    <small>Please provide accurate information.</small>
                                </div>

                                <div class="form-field">
                                    <label>Contact Number <span style="color:var(--red)">*</span></label>
                                    <input type="text" name="contact" required placeholder="09XXXXXXXXX">
                                </div>

                                <div class="form-field">
                                    <label>Additional Notes</label>
                                    <textarea name="notes" rows="2" placeholder="Any special instructions..."></textarea>
                                </div>

                                <?php
                                $hasPrescriptionItems = false;
                                foreach ($cartItems as $item) {
                                    if (($item['requires_prescription'] ?? 'no') === 'yes') { $hasPrescriptionItems = true; break; }
                                }
                                ?>

                                <?php if ($hasPrescriptionItems): ?>
                                <div class="form-section-title">Prescription Uploads</div>
                                <?php foreach ($cartItems as $index => $item):
                                    $rp = ($item['requires_prescription'] ?? 'no') === 'yes';
                                    $hs = !empty($item['prescription_file']);
                                    if (!$rp) continue;
                                ?>
                                <div class="presc-upload-item">
                                    <label><i class="bi bi-file-earmark-medical"></i> <?php echo htmlspecialchars($item['medicine']); ?></label>
                                    <input type="file" name="prescription_<?php echo $index; ?>" accept="image/*,.pdf" <?php echo !$hs ? 'required' : ''; ?>>
                                    <?php if ($hs): ?>
                                        <small style="color:var(--green);margin-top:3px;display:block;"><i class="bi bi-check-circle"></i> Already uploaded</small>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                                <?php endif; ?>

                                <!-- TTS: Summary -->
                                <div class="modal-tts-bar" style="margin-bottom:13px;">
                                    <span><span class="tts-dot" id="summaryTtsDot" style="display:none;"></span></span>
                                    <button type="button" class="modal-tts-btn" id="cartReadSummary"><i class="bi bi-play-fill"></i> Read Summary</button>
                                    <button type="button" class="modal-tts-stop-btn" id="cartStopSummary"><i class="bi bi-stop-fill"></i> Stop</button>
                                </div>

                                <button type="submit" class="submit-btn" id="submitBtn">
                                    <i class="bi bi-bag-check"></i> Submit Reservation
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Success Modal -->
<div class="modal-backdrop-custom" id="successModal">
<div class="modal-card-custom">
    <div class="modal-check"><i class="bi bi-check-lg"></i></div>
    <h3>Reservation Sent!</h3>
    <p>Your request has been submitted. Our team will review it and contact you shortly.</p>
    <div id="reservationCodeBox" style="display:none;
        background:var(--blue-lt); border:1.5px solid var(--blue-mid);
        border-radius:8px; padding:12px 18px; margin:0 0 18px; text-align:left;">
        <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;
            letter-spacing:.6px;color:var(--slate);margin-bottom:5px;">
            Reservation Code
        </div>
        <div id="reservationCodeValue" style="font-size:1.3rem;font-weight:800;
            color:var(--navy);letter-spacing:3px;font-family:monospace;"></div>
        <div style="font-size:.71rem;color:var(--slate);margin-top:6px;line-height:1.5;">
            <i class="bi bi-info-circle"></i>
            All items in this order share this code.<br>Screenshot it — present when picking up.
        </div>
    </div>
    <button class="modal-close-btn" id="successClose">
        <i class="bi bi-arrow-left"></i> Back to Cart
    </button>
</div>
</div>

<!-- Guide Modal -->
<div class="modal-overlay" id="guideModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Reservation Cart Guide</h2>
            <button class="modal-close" id="closeBtn">&times;</button>
        </div>

        <!-- Tab Buttons -->
        <div style="background: white; padding: 0 30px; border-bottom: 1px solid #e8ecf1;">
            <div class="modal-tabs">
                <button class="modal-tab-btn active" data-tab="guide-tab">Page Guide</button>
                <button class="modal-tab-btn" data-tab="tts-tab">Text-to-Speech</button>
            </div>
        </div>

        <div class="modal-inner" id="modalInner">
            
            <!-- ===== GUIDE TAB ===== -->
            <div id="guide-tab" class="modal-tab-content active">
                <!-- TTS Controls -->
                <div class="modal-tts-bar">
                    <i class="bi bi-volume-up-fill" style="color:#9AA6B2; font-size:15px;"></i>
                    <span>Text to speech</span>
                    <button class="modal-tts-btn" id="modalTtsReadGuide">
                        <i class="bi bi-play-fill"></i> Read guide
                    </button>
                    <button class="modal-tts-stop-btn" id="modalTtsStop">
                        <i class="bi bi-stop-fill"></i> Stop
                    </button>
                </div>

                <!-- Step 1 -->
                <div class="modal-step" data-step="Step 1: Review Your Items. The left side shows all medicines in your cart. Each item displays the medicine name, image, badges indicating if a prescription is required or already uploaded, and the reserve by options.">
                    <h3>Step 1: Review Your Items</h3>
                    <p>The <strong>left side</strong> shows all medicines in your cart. Each item displays:</p>
                    <ul>
                        <li>Medicine name and image</li>
                        <li>Badges showing prescription requirements (Rx Required or No Rx Needed)</li>
                        <li>Uploaded prescription status (Rx Uploaded badge)</li>
                        <li>Reserve by options (per piece, per 10 pieces, or per box)</li>
                    </ul>
                    <div class="modal-step-image-placeholder">
                        <i class="bi bi-image" style="font-size: 2rem; color: #D9EAFD; margin-right: 8px;"></i>
                        <img src="screenshots/c1.png">
                    </div>
                    <button class="modal-tts-step-btn">
                        <i class="bi bi-volume-up"></i> Read step
                    </button>
                </div>

                <!-- Step 2 -->
                <div class="modal-step" data-step="Step 2: Adjust Quantities. Each item has a quantity stepper with minus and plus buttons. Click to adjust how many units you want to reserve. The unit price and line total update automatically.">
                    <h3>Step 2: Adjust Quantities</h3>
                    <p>Each item has a <strong>quantity stepper</strong> with minus and plus buttons.</p>
                    <ul>
                        <li>Click <strong>minus</strong> to decrease quantity</li>
                        <li>Click <strong>plus</strong> to increase quantity</li>
                        <li>Unit price and line total update automatically</li>
                    </ul>
                    <div class="modal-step-image-placeholder">
                        <i class="bi bi-calculator" style="font-size: 2rem; color: #D9EAFD; margin-right: 8px;"></i>
                        <img src="screenshots/c2.png">
                    </div>
                    <button class="modal-tts-step-btn">
                        <i class="bi bi-volume-up"></i> Read step
                    </button>
                </div>

                <!-- Step 3 -->
                <div class="modal-step" data-step="Step 3: Choose Reserve Option. For each medicine, select how you want to reserve it: per piece (individual tablets or units), per 10 pieces (pack of 10), or per box (100 pieces). The price automatically adjusts based on your selection.">
                    <h3>Step 3: Choose Reserve Option</h3>
                    <p>For each medicine, select your preferred reserve option:</p>
                    <ul>
                        <li><strong>Per piece</strong> - Individual tablets or units</li>
                        <li><strong>Per 10 pieces</strong> - Pack of 10 units</li>
                        <li><strong>Per box</strong> - Full box of 100 pieces</li>
                    </ul>
                    <p>Prices automatically adjust based on your selection.</p>
                    <div class="modal-step-image-placeholder">
                        <i class="bi bi-diagram-3" style="font-size: 2rem; color: #D9EAFD; margin-right: 8px;"></i>
                        <img src="screenshots/c3.png">
                    </div>
                    <button class="modal-tts-step-btn">
                        <i class="bi bi-volume-up"></i> Read step
                    </button>
                </div>

                <!-- Step 4 -->
                <div class="modal-step" data-step="Step 4: View Order Summary. The right side shows your order summary with the total amount calculated. It also displays the form where you need to enter your personal details.">
                    <h3>Step 4: View Order Summary</h3>
                    <p>The <strong>right side</strong> displays your order summary with:</p>
                    <ul>
                        <li>Total amount of all items</li>
                        <li>Form fields for your personal information</li>
                        <li>Sticky positioning so it stays visible while scrolling</li>
                    </ul>
                    <div class="modal-step-image-placeholder">
                        <i class="bi bi-receipt" style="font-size: 2rem; color: #D9EAFD; margin-right: 8px;"></i>
                        <img src="screenshots/c4.png">
                    </div>
                    <button class="modal-tts-step-btn">
                        <i class="bi bi-volume-up"></i> Read step
                    </button>
                </div>

                <!-- Step 5 -->
                <div class="modal-step" data-step="Step 5: Fill In Your Details. Enter your first name, last name, and contact number. These details are essential for the pharmacy to contact and process your reservation. You can also add additional notes with special instructions.">
                    <h3>Step 5: Fill In Your Details</h3>
                    <p>In the form, provide:</p>
                    <ul>
                        <li><strong>First Name</strong> (required)</li>
                        <li><strong>Last Name</strong> (required)</li>
                        <li><strong>Contact Number</strong> (required) - How the pharmacy will reach you</li>
                        <li><strong>Additional Notes</strong> (optional) - Any special instructions</li>
                    </ul>
                    <p>⚠️ Ensure your details are accurate to avoid rejection.</p>
                    <div class="modal-step-image-placeholder">
                        <i class="bi bi-person-fill" style="font-size: 2rem; color: #D9EAFD; margin-right: 8px;"></i>
                        <img src="screenshots/c5.png">
                    </div>
                    <button class="modal-tts-step-btn">
                        <i class="bi bi-volume-up"></i> Read step
                    </button>
                </div>

                <!-- Step 6 -->
                <div class="modal-step" data-step="Step 6: Upload Prescriptions. If any medicine requires a prescription, upload a clear image or PDF of your prescription. If you already uploaded it, a confirmation badge appears.">
                    <h3>Step 6: Upload Prescriptions</h3>
                    <p>For medicines marked as <strong>Rx Required</strong>:</p>
                    <ul>
                        <li>Upload a clear image or PDF of your prescription</li>
                        <li>Only required for items without prior upload</li>
                        <li>Look for the <strong>Rx Uploaded</strong> badge if already provided</li>
                    </ul>
                    <div class="modal-step-image-placeholder">
                        <i class="bi bi-file-earmark-medical" style="font-size: 2rem; color: #D9EAFD; margin-right: 8px;"></i>
                        <img src="screenshots/c6.png">
                    </div>
                    <button class="modal-tts-step-btn">
                        <i class="bi bi-volume-up"></i> Read step
                    </button>
                </div>

                <!-- Step 7 -->
                <div class="modal-step" data-step="Step 7: Submit Your Reservation. Click the Submit Reservation button to send your order. You will receive a unique reservation code that you can use to track or claim your order.">
                    <h3>Step 7: Submit Your Reservation</h3>
                    <p>Review all details, then click <strong>Submit Reservation</strong>.</p>
                    <ul>
                        <li>Your request is sent to the pharmacy</li>
                        <li>You receive a <strong>unique reservation code</strong></li>
                        <li>Use this code to claim your medicines at the pharmacy</li>
                        <li>Screenshot it for future reference</li>
                    </ul>
                    <div class="modal-step-image-placeholder">
                        <i class="bi bi-bag-check" style="font-size: 2rem; color: #D9EAFD; margin-right: 8px;"></i>
                        <img src="screenshots/c7.png">
                    </div>
                    <button class="modal-tts-step-btn">
                        <i class="bi bi-volume-up"></i> Read step
                    </button>
                </div>

                <!-- Rules -->
                <div class="modal-step" data-step="Cart Rules and Features. You can add more medicines directly from the cart page. Use Clear Cart to remove all items at once. Each item can be removed individually. Quantity and reserve options update the total instantly. Prices change based on reserve method. All cart data is saved in your session until submission.">
                    <h3>Cart Rules & Features</h3>
                    <ul>
                        <li>You can <strong>Add More</strong> medicines directly from the cart page</li>
                        <li>Use <strong>Clear Cart</strong> to remove all items at once</li>
                        <li>Each item can be removed individually with the trash icon</li>
                        <li>Quantity and reserve options update the total instantly</li>
                        <li>Prices change based on reserve method (piece vs 10-pieces vs box)</li>
                        <li>All cart data is saved in your session until submission</li>
                    </ul>
                    <div class="modal-step-image-placeholder">
                        <i class="bi bi-sliders" style="font-size: 2rem; color: #D9EAFD; margin-right: 8px;"></i>
                        <img src="screenshots/c8.png">
                    </div>
                    <button class="modal-tts-step-btn">
                        <i class="bi bi-volume-up"></i> Read step
                    </button>
                </div>
            </div>

            <!-- ===== TEXT-TO-SPEECH TAB ===== -->
            <div id="tts-tab" class="modal-tab-content">
                <!-- TTS Controls -->
                <div class="modal-tts-bar">
                    <i class="bi bi-volume-up-fill" style="color:#9AA6B2; font-size:15px;"></i>
                    <span>Text to speech</span>
                    <button class="modal-tts-btn" id="ttsTabReadGuide">
                        <i class="bi bi-play-fill"></i> Read guide
                    </button>
                    <button class="modal-tts-stop-btn" id="ttsTabStop">
                        <i class="bi bi-stop-fill"></i> Stop
                    </button>
                </div>

                <h3 style="color: #7393A7; margin-top: 0;">Text-to-Speech (TTS) Feature</h3>
                
                <div class="multimodal-info">
                    <h4>What is Text-to-Speech?</h4>
                    <p>Text-to-Speech technology reads text content aloud to you, making it easier to understand the cart process and instructions without having to read. This is especially helpful for customers with visual impairments or those who prefer listening to information.</p>
                </div>

                <div class="modal-step" data-step="How to Use TTS on This Page. Step 1: At the top of the guide modal, look for the Text-to-Speech bar with a speaker icon. Step 2: Click Read guide to hear the entire page guide from start to finish. Step 3: For individual sections, scroll to a step and click the Read step button. Step 4: To stop audio at any time, click the Stop button.">
                    <h3>How to Use TTS on This Page</h3>
                    <p>Follow these simple steps to use the Text-to-Speech feature:</p>
                    <ul>
                        <li><strong>Step 1:</strong> At the top of the guide modal, look for the Text-to-Speech bar with a speaker icon</li>
                        <li><strong>Step 2:</strong> Click <strong>"Read guide"</strong> to hear the entire page guide from start to finish</li>
                        <li><strong>Step 3:</strong> For individual sections, scroll to a step and click the <strong>"Read step"</strong> button</li>
                        <li><strong>Step 4:</strong> To stop audio at any time, click the <strong>"Stop"</strong> button</li>
                    </ul>
                    <div class="modal-step-image-placeholder">
                        <i class="bi bi-play-circle" style="font-size: 2rem; color: #D9EAFD; margin-right: 8px;"></i>
                        <img src="screenshots/ct11.png">
                    </div>
                    <button class="modal-tts-step-btn">
                        <i class="bi bi-volume-up"></i> Read step
                    </button>
                </div>

                <div class="modal-step" data-step="Using TTS on the Main Cart Page. The cart page also has TTS controls for the summary section. Look for the speaker icon in the order summary area. Use Read to hear your cart summary including total amount and items. Click Stop to stop the audio playback at any time. Use this feature to quickly verify your order before submitting.">
                    <h3>Using TTS on the Main Cart Page</h3>
                    <p>The cart page also has TTS controls for the summary section:</p>
                    <ul>
                        <li>Look for the speaker icon in the order summary area</li>
                        <li>Use <strong>"Read"</strong> to hear your cart summary including total amount and items</li>
                        <li>Click <strong>"Stop"</strong> to stop the audio playback at any time</li>
                        <li>Use this feature to quickly verify your order before submitting</li>
                    </ul>
                    <div class="modal-step-image-placeholder">
                        <i class="bi bi-volume-up-fill" style="font-size: 2rem; color: #D9EAFD; margin-right: 8px;"></i>
                        <img src="screenshots/ct1.png">
                    </div>
                    <button class="modal-tts-step-btn">
                        <i class="bi bi-volume-up"></i> Read step
                    </button>
                </div>

                <div class="modal-step" data-step="Understanding What TTS Reads. Medicine Names: The names of medicines in your cart. Quantities: How many units of each medicine. Unit Prices: The price per piece, 10-piece pack, or box. Line Totals: The total for each medicine item. Grand Total: Your complete order amount.">
                    <h3>Understanding What TTS Reads</h3>
                    <ul>
                        <li><strong>Medicine Names:</strong> The names of medicines in your cart</li>
                        <li><strong>Quantities:</strong> How many units of each medicine</li>
                        <li><strong>Unit Prices:</strong> The price per piece, 10-piece pack, or box</li>
                        <li><strong>Line Totals:</strong> The total for each medicine item</li>
                        <li><strong>Grand Total:</strong> Your complete order amount</li>
                    </ul>
                    <div class="modal-step-image-placeholder">
                        <i class="bi bi-chat-left-text" style="font-size: 2rem; color: #D9EAFD; margin-right: 8px;"></i>
                        <img src="screenshots/ct2.png">
                    </div>
                    <button class="modal-tts-step-btn">
                        <i class="bi bi-volume-up"></i> Read step
                    </button>
                </div>

                <div class="modal-step" data-step="TTS Tips and Tricks. Adjust Your Browser Volume: Use your device's volume controls for comfortable listening. Browser Compatibility: Works best on Chrome, Firefox, Safari, and Edge browsers. Language Support: Currently reads in English Philippines accent for optimal experience. Multiple Readings: You can click stop and restart reading at any section. Before Submission: Always use TTS to verify totals before submitting your reservation.">
                    <h3>TTS Tips & Tricks</h3>
                    <ul>
                        <li><strong>Adjust Your Browser Volume:</strong> Use your device's volume controls for comfortable listening</li>
                        <li><strong>Use Keyboard Shortcuts:</strong> Most browsers support spacebar to play/pause in some contexts</li>
                        <li><strong>Browser Compatibility:</strong> Works best on Chrome, Firefox, Safari, and Edge browsers</li>
                        <li><strong>Language Support:</strong> Currently reads in English (Philippines) accent for optimal experience</li>
                        <li><strong>Multiple Readings:</strong> You can click stop and restart reading at any section</li>
                        <li><strong>Before Submission:</strong> Always use TTS to verify totals before submitting your reservation</li>
                    </ul>
                    <button class="modal-tts-step-btn">
                        <i class="bi bi-volume-up"></i> Read step
                    </button>
                </div>
            </div>

           </div>
    </div>
</div>

<div class="page-toast" id="pageToast"></div>

<!-- Floating Action Buttons -->
<div class="floating-buttons">
    <button class="fab" id="zoomInBtn" title="Zoom in">+</button>
    <button class="fab" id="zoomOutBtn" title="Zoom out">−</button>
    <button class="fab" id="guideBtn" title="View Guide">?</button>
</div>

<?php include 'footer.php'; ?>
<script src="source/sidebar.js"></script>
<script src="source/homepage.js"></script>
<script>
(function () {
    // ===== ZOOM FUNCTIONALITY =====
    let currentZoom = 100;
    const minZoom = 80;
    const maxZoom = 150;
    const zoomStep = 10;

    let currentModalStepBtn = null;

    const zoomInBtn = document.getElementById('zoomInBtn');
    const zoomOutBtn = document.getElementById('zoomOutBtn');
    const guideBtn = document.getElementById('guideBtn');
    const pageWrapper = document.querySelector('.page-wrapper');
    const guideModal = document.getElementById('guideModal');
    const closeBtn = document.getElementById('closeBtn');

    // Ensure voices are loaded
    window.speechSynthesis.onvoiceschanged = function() {
        window.speechSynthesis.getVoices();
    };

    function updateZoom() {
        const scale = currentZoom / 100;
        pageWrapper.style.transform = `scale(${scale})`;
        
        zoomInBtn.disabled = currentZoom >= maxZoom;
        zoomOutBtn.disabled = currentZoom <= minZoom;
    }

    zoomInBtn.addEventListener('click', function() {
        if (currentZoom < maxZoom) {
            currentZoom += zoomStep;
            updateZoom();
        }
    });

    zoomOutBtn.addEventListener('click', function() {
        if (currentZoom > minZoom) {
            currentZoom -= zoomStep;
            updateZoom();
        }
    });

    // ===== MODAL FUNCTIONALITY =====
    guideBtn.addEventListener('click', function() {
        guideModal.classList.add('active');
    });

    closeBtn.addEventListener('click', function() {
        stopModalTTS();
        guideModal.classList.remove('active');
    });

    guideModal.addEventListener('click', function(e) {
        if (e.target === guideModal) {
            stopModalTTS();
            guideModal.classList.remove('active');
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && guideModal.classList.contains('active')) {
            stopModalTTS();
            guideModal.classList.remove('active');
        }
    });

    // Keyboard shortcuts for zoom
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey || e.metaKey) {
            if (e.key === '+' || e.key === '=') {
                e.preventDefault();
                zoomInBtn.click();
            } else if (e.key === '-') {
                e.preventDefault();
                zoomOutBtn.click();
            } else if (e.key === '0') {
                e.preventDefault();
                currentZoom = 100;
                updateZoom();
            }
        }
    });

    // ===== MODAL TTS FUNCTIONALITY =====
    function speakModal(text, onEnd) {
        window.speechSynthesis.cancel();
        const utter = new SpeechSynthesisUtterance(text);
        utter.lang = 'en-PH';
        utter.rate = 1;
        
        let voices = window.speechSynthesis.getVoices();
        const rosaVoice = voices.find(v => v.name === 'Microsoft Rosa Online (Natural) - English (Philippines)');
        if (rosaVoice) utter.voice = rosaVoice;
        
        if (onEnd) utter.onend = onEnd;
        window.speechSynthesis.speak(utter);
    }

    function stopModalTTS() {
        window.speechSynthesis.cancel();
        if (currentModalStepBtn) { resetModalBtn(currentModalStepBtn); currentModalStepBtn = null; }
    }

    function setModalReading(btn) {
        if (currentModalStepBtn && currentModalStepBtn !== btn) resetModalBtn(currentModalStepBtn);
        currentModalStepBtn = btn;
        btn.classList.add('reading');
        btn.innerHTML = '<span class="tts-dot"></span> Reading...';
    }

    function resetModalBtn(btn) {
        btn.classList.remove('reading');
        btn.innerHTML = '<i class="bi bi-volume-up"></i> Read step';
    }

    // Per-step Read buttons in modal
    document.querySelectorAll('.modal-tts-step-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const stepDiv = this.closest('.modal-step');
            const text = stepDiv ? stepDiv.getAttribute('data-step') : '';
            if (this.classList.contains('reading')) { stopModalTTS(); return; }
            setModalReading(this);
            speakModal(text, () => { resetModalBtn(this); currentModalStepBtn = null; });
        });
    });

    // Read entire guide in modal
    document.getElementById('modalTtsReadGuide').addEventListener('click', function () {
        const steps = document.querySelectorAll('.modal-step');
        if (steps.length === 0) return;
        let index = 0;

        function readNext() {
            if (index >= steps.length) {
                if (currentModalStepBtn) { resetModalBtn(currentModalStepBtn); currentModalStepBtn = null; }
                return;
            }
            const step = steps[index];
            const btn = step.querySelector('.modal-tts-step-btn');
            const text = step.getAttribute('data-step');
            if (btn) setModalReading(btn);
            index++;
            speakModal(text, readNext);
        }

        readNext();
    });

    // Stop modal TTS
    document.getElementById('modalTtsStop').addEventListener('click', stopModalTTS);

    // ===== TAB SWITCHING FUNCTIONALITY =====
    document.querySelectorAll('.modal-tab-btn').forEach(btn => {
      btn.addEventListener('click', function() {
        const tabName = this.getAttribute('data-tab');
        
        // Remove active class from all buttons and contents
        document.querySelectorAll('.modal-tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.modal-tab-content').forEach(c => c.classList.remove('active'));
        
        // Add active class to clicked button and corresponding content
        this.classList.add('active');
        const tabContent = document.getElementById(tabName);
        if (tabContent) {
          tabContent.classList.add('active');
          
          // Reset scroll to top of new tab
          const modalInner = document.getElementById('modalInner');
          if (modalInner) {
            modalInner.scrollTop = 0;
          }
          
          // Update TTS to read only active tab
          setupTabTTS(tabName);
        }
      });
    });

    // Tab-specific TTS setup
    function setupTabTTS(tabName) {
      // Get new TTS buttons
      const readBtn = document.getElementById('modalTtsReadGuide');
      const stopBtn = document.getElementById('modalTtsStop');
      
      if (!readBtn || !stopBtn) return;
      
      // Clone to remove old listeners
      readBtn.replaceWith(readBtn.cloneNode(true));
      stopBtn.replaceWith(stopBtn.cloneNode(true));
      
      // Re-add listeners
      document.getElementById('modalTtsReadGuide').addEventListener('click', function() {
        const steps = document.querySelectorAll('.modal-tab-content.active .modal-step');
        if (steps.length === 0) return;
        let index = 0;

        function readNext() {
          if (index >= steps.length) {
            if (currentModalStepBtn) { resetModalBtn(currentModalStepBtn); currentModalStepBtn = null; }
            return;
          }
          const step = steps[index];
          const btn = step.querySelector('.modal-tts-step-btn');
          const text = step.getAttribute('data-step');
          if (btn) setModalReading(btn);
          index++;
          speakModal(text, readNext);
        }

        readNext();
      });

      document.getElementById('modalTtsStop').addEventListener('click', stopModalTTS);
    }

    // TTS tab "Read guide" and "Stop" buttons
    document.getElementById('ttsTabReadGuide').addEventListener('click', function () {
      const steps = document.querySelectorAll('#tts-tab .modal-step');
      if (steps.length === 0) return;
      let index = 0;
      function readNext() {
        if (index >= steps.length) {
          if (currentModalStepBtn) { resetModalBtn(currentModalStepBtn); currentModalStepBtn = null; }
          return;
        }
        const step = steps[index];
        const btn = step.querySelector('.modal-tts-step-btn');
        const text = step.getAttribute('data-step');
        if (btn) setModalReading(btn);
        index++;
        speakModal(text, readNext);
      }
      readNext();
    });

    document.getElementById('ttsTabStop').addEventListener('click', stopModalTTS);

    // Initialize zoom
    updateZoom();

    // ===== ORIGINAL CART FUNCTIONALITY =====
    const cartData = <?php echo json_encode(array_map(function($item) {
        return [
            'price_per_piece' => floatval($item['price_per_piece']),
            'quantity'        => intval($item['quantity']),
            'reserve_by'      => $item['reserve_by'] ?? 'piece',
            'branch_id'       => $item['branch_id'] ?? null,
        ];
    }, $cartItems)); ?>;

    const toast = document.getElementById('pageToast');
    function showToast(msg, type = 'info', dur = 2000) {
        toast.textContent = msg;
        toast.className = 'page-toast ' + type + ' show';
        clearTimeout(toast._t);
        toast._t = setTimeout(() => toast.classList.remove('show'), dur);
    }

    function changeQty(index, delta) {
        const qtyEl   = document.getElementById('qty-' + index);
        const stepper = document.getElementById('stepper-' + index);
        const current = parseInt(qtyEl.value) || 1;
        const next    = Math.max(1, current + delta);
        if (next === current) return;

        qtyEl.value = next;
        stepper.classList.add('qty-updating');
        cartData[index].quantity = next;

        const fd = new FormData();
        fd.append('action', 'update_quantity');
        fd.append('item_index', index);
        fd.append('quantity', next);

        fetch('cart.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => { if (data.success) { updateLineTotal(index); recalcSummary(); } })
            .catch(() => showToast('Update failed', 'error'))
            .finally(() => stepper.classList.remove('qty-updating'));
    }

    window.changeQty = changeQty;

    window.updateReserveBy = function(index, value) {
        cartData[index].reserve_by = value;

        updateLineTotal(index);
        recalcSummary();

        const fd = new FormData();
        fd.append('action', 'update_reserve_by');
        fd.append('item_index', index);
        fd.append('reserve_by', value);

        fetch('cart.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => { if (data.success) showToast('Reserve option updated', 'info', 1500); })
            .catch(() => showToast('Update failed', 'error'));
    };

    function getMultiplier(reserveBy) {
        if (reserveBy === 'box')      return 100;
        if (reserveBy === '10pieces') return 10;
        return 1;
    }

    function fmt(n) { return '\u20B1' + n.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ','); }
    
    function updateLineTotal(index) {
        const item       = cartData[index];
        const mult       = getMultiplier(item.reserve_by);
        const unitPrice  = item.price_per_piece * mult;
        const total      = unitPrice * item.quantity;

        const lineEl = document.getElementById('line-total-' + index);
        if (lineEl) lineEl.textContent = fmt(total);

        const unitLabel = item.reserve_by === 'box'
            ? '/box (100 pcs)'
            : item.reserve_by === '10pieces'
                ? '/10 pcs'
                : '/pc';
        const unitPriceEl = document.getElementById('unit-price-' + index);
        if (unitPriceEl) unitPriceEl.innerHTML = `@ <strong>${fmt(unitPrice)}</strong>${unitLabel}`;
    }

    window.removeItem = function(index) {
        if (!confirm('Remove this item from the cart?')) return;
        window.location.href = 'cart.php?remove=' + index;
    };

    function recalcSummary() {
        let total = 0;
        cartData.forEach(item => {
            const mult = getMultiplier(item.reserve_by);
            total += item.price_per_piece * item.quantity * mult;
        });

        document.getElementById('sum-total').textContent = fmt(total);
    }

    recalcSummary();

    /* Form submit */
    const checkoutForm = document.getElementById('checkoutForm');
    const successModal = document.getElementById('successModal');
    const successClose = document.getElementById('successClose');
    const submitBtn    = document.getElementById('submitBtn');

    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function (e) {
            e.preventDefault();

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Submitting...';
            showToast('Submitting reservation\u2026', 'info');

            fetch(checkoutForm.action, {
                method: 'POST',
                body: new FormData(checkoutForm),
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(json => {
                if (json.success) {
                    if (json.code) {
                        document.getElementById('reservationCodeValue').textContent = json.code;
                        document.getElementById('reservationCodeBox').style.display = 'block';
                    }
                    successModal.classList.add('show');
                    showToast('Reservation submitted! Code: ' + (json.code || ''), 'success', 5000);
                } else {
                    alert('Error: ' + (json.message || 'Unknown error'));
                }
            })
            .catch(err => alert('Connection error: ' + err))
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="bi bi-bag-check"></i> Submit Reservation';
            });
        });
    }

    if (successClose) {
        successClose.addEventListener('click', () => {
            successModal.classList.remove('show');
            window.location.reload();
        });
    }

    /* TTS helpers */
    function speak(text, onEnd) {
        window.speechSynthesis.cancel();
        const utter = new SpeechSynthesisUtterance(text);
        utter.lang = 'en-PH'; utter.rate = 1;
        const rosa = window.speechSynthesis.getVoices().find(v => v.name && v.name.includes('Rosa'));
        if (rosa) utter.voice = rosa;
        if (onEnd) utter.onend = onEnd;
        window.speechSynthesis.speak(utter);
    }

    function setDot(dotEl, on) { if (dotEl) dotEl.style.display = on ? 'inline-block' : 'none'; }

    /* TTS: Read Items */
    const readItemsBtn = document.getElementById('cartReadItems');
    const stopItemsBtn = document.getElementById('cartStopItems');
    const itemsDot     = document.getElementById('itemsTtsDot');

    if (readItemsBtn) {
        readItemsBtn.addEventListener('click', () => {
            if (cartData.length === 0) return;
            const itemNames = <?php echo json_encode(array_map(function($i){ return $i['medicine']; }, $cartItems)); ?>;
            let index = 0;
            setDot(itemsDot, true);
            function readNext() {
                if (index >= cartData.length) { setDot(itemsDot, false); return; }
                const item = cartData[index];
                const mult = getMultiplier(item.reserve_by);
                const unitLabel = item.reserve_by === 'box' ? 'box' : item.reserve_by === '10pieces' ? '10 pieces' : 'piece';
                const text = `Item ${index + 1}: ${itemNames[index]}. Quantity: ${item.quantity} ${unitLabel}. Price: ${fmt(item.price_per_piece * mult * item.quantity)}.`;
                index++;
                speak(text, readNext);
            }
            readNext();
        });
    }

    if (stopItemsBtn) stopItemsBtn.addEventListener('click', () => { window.speechSynthesis.cancel(); setDot(itemsDot, false); });

    /* TTS: Read Summary */
    const readBtn  = document.getElementById('cartReadSummary');
    const stopBtn  = document.getElementById('cartStopSummary');
    const sumDot   = document.getElementById('summaryTtsDot');

    if (readBtn) {
        readBtn.addEventListener('click', () => {
            const total = document.getElementById('sum-total')?.textContent || '';
            const items = cartData.length;
            setDot(sumDot, true);
            speak(`You have ${items} item${items !== 1 ? 's' : ''} in your cart. Grand total: ${total}.`, () => setDot(sumDot, false));
        });
    }

    if (stopBtn) stopBtn.addEventListener('click', () => { window.speechSynthesis.cancel(); setDot(sumDot, false); });
})();
</script>
</body>
</html>