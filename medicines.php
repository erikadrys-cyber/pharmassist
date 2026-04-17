<?php
include 'config/connection.php';
session_start();

$isApproved = ($_SESSION['id_verification_status'] === 'approved');

if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION['role'] !== 'customer') {
    header("Location: dashboard.php");
    exit();
}

$branch_filter = isset($_GET['branch']) ? trim($_GET['branch']) : '';
$search_filter = isset($_GET['search']) ? trim($_GET['search']) : '';

$query = "SELECT m.*, b.branch_id, b.branch_name 
          FROM medicine m 
          LEFT JOIN branches b ON m.branch_id = b.branch_id";

$conditions = [];
if (!empty($branch_filter)) {
    $conditions[] = "b.branch_name = '" . $conn->real_escape_string($branch_filter) . "'";
}

if (!empty($search_filter)) {
    $conditions[] = "m.medicine_name LIKE '%" . $conn->real_escape_string($search_filter) . "%'";
}

if (!empty($conditions)) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}

$query .= " ORDER BY b.branch_id ASC, m.medicine_name ASC";
$result = $conn->query($query);

$branches = [];
while ($row = $result->fetch_assoc()) {
    $branchName = isset($row['branch_name']) ? $row['branch_name'] : 'Unknown';
    $branches[$branchName][] = $row;
}

$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Medicines | PharmAssist</title>

    <!-- Styles -->
    <link rel="stylesheet" href="plugins/sidebar.css">
    <link rel="stylesheet" href="plugins/medicines.css?v=2">
    <link rel="stylesheet" href="plugins/footer.css">
    <link rel="icon" href="website_icon/favicon.png" type="image/png" sizes="64x64">

    <!-- Icons + Bootstrap -->
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,200..800&family=Tinos:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">

    <!-- Tesseract.js OCR (Scan Label modal) -->
    <script src="https://cdn.jsdelivr.net/npm/tesseract.js@5.0.4/dist/tesseract.min.js"></script>

    <!-- TF.js + Teachable Machine -->
    <script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@1.7.4/dist/tf.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@teachablemachine/image@0.8.5/dist/teachablemachine-image.min.js"></script>

    <style>
        /* ════════════════════════════════════════════════
           SCANNER MODAL STYLES
        ════════════════════════════════════════════════ */

        .scanner-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .scanner-overlay.active {
            display: flex;
        }

        .scanner-modal {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 1000px;
            height: 600px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .scanner-body {
            display: flex;
            gap: 20px;
            flex: 1;
            padding: 20px;
            overflow: hidden;
        }

        .upload-col {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 15px;
            overflow-y: auto;
        }

        .result-col {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            background: linear-gradient(135deg, #f8faff 0%, #ffffff 100%);
            border-radius: 8px;
            padding: 15px;
            border: 1px solid #e0e7ff;
        }

        .result-col::-webkit-scrollbar {
            width: 6px;
        }

        .result-col::-webkit-scrollbar-track {
            background: #f0f4f8;
            border-radius: 10px;
        }

        .result-col::-webkit-scrollbar-thumb {
            background: #3b82f6;
            border-radius: 10px;
        }

        /* Mode Toggle */
        .mode-toggle {
            display: flex;
            gap: 10px;
            background: #f0f4f8;
            padding: 4px;
            border-radius: 8px;
        }

        .mode-btn {
            flex: 1;
            padding: 10px;
            border: none;
            background: transparent;
            border-radius: 6px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            color: #64748b;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .mode-btn.active {
            background: white;
            color: #3b82f6;
            box-shadow: 0 2px 4px rgba(59, 130, 246, 0.1);
        }

        .mode-btn:hover:not(.active) {
            color: #475569;
        }

        /* Upload Zone */
        .upload-zone {
            flex: 1;
            position: relative;
            overflow: hidden;
            border: 2px dashed #3b82f6;
            border-radius: 8px;
            background: #f0f9ff;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .upload-zone.dragover {
            background: #e0f2fe;
            border-color: #0284c7;
        }

        .upload-zone.has-image {
            border-color: #10b981;
            background: white;
        }

        .upload-zone.has-image .upload-placeholder {
            display: none;
        }

        .upload-zone.has-image .preview-img {
            display: block;
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .preview-img {
            display: none;
        }

        .upload-placeholder {
            text-align: center;
            padding: 20px;
        }

        .upload-icon-wrap {
            width: 60px;
            height: 60px;
            margin: 0 auto 15px;
            color: #3b82f6;
            opacity: 0.5;
        }

        .upload-icon-wrap svg {
            width: 100%;
            height: 100%;
            stroke: currentColor;
            stroke-width: 1.5;
        }

        .upload-placeholder h5 {
            font-size: 16px;
            font-weight: 600;
            color: #1e40af;
            margin-bottom: 8px;
        }

        .upload-placeholder p {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 15px;
        }

        .button-group {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-bottom: 15px;
        }

        .choose-btn {
            padding: 10px 24px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .choose-btn:hover {
            background: #2563eb;
            transform: translateY(-2px);
        }

        .camera-btn {
            width: 44px;
            height: 44px;
            border: 2px solid #3b82f6;
            background: #eff6ff;
            border-radius: 6px;
            cursor: pointer;
            color: #3b82f6;
            font-size: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .camera-btn:hover {
            background: #3b82f6;
            color: white;
        }

        .upload-file-note {
            font-size: 11px;
            color: #94a3b8;
        }

        /* Camera Feed */
        .camera-feed-container {
            position: relative;
            width: 100%;
            height: 100%;
            border-radius: 8px;
            overflow: hidden;
            background: #000;
            border: 2px dashed #3b82f6;
            display: none;
        }

        .camera-feed-container video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .camera-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            pointer-events: none;
            color: #10b981;
            font-size: 14px;
            font-weight: 600;
        }

        .camera-overlay p {
            margin: 20px 0 0 0;
            text-shadow: 0 2px 4px rgba(0,0,0,0.6);
            font-size: 13px;
        }

        .reticle {
            position: relative;
            width: 140px;
            height: 140px;
            border: 2px solid rgba(16, 185, 129, 0.4);
            border-radius: 16px;
            animation: pulse-reticle 2s ease-in-out infinite;
        }

        .reticle > div {
            position: absolute;
            inset: 10px;
            border: 1px solid rgba(16, 185, 129, 0.3);
            border-radius: 12px;
        }

        .reticle.success {
            border-color: #10b981;
            animation: none;
        }

        @keyframes pulse-reticle {
            0%, 100% { 
                border-color: rgba(16, 185, 129, 0.3);
                box-shadow: none;
            }
            50% { 
                border-color: rgba(16, 185, 129, 0.8);
                box-shadow: 0 0 12px rgba(16, 185, 129, 0.4);
            }
        }

        .capture-btn {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            padding: 12px 28px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            border-radius: 50px;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 8px 16px rgba(16, 185, 129, 0.3);
        }

        .capture-btn:hover {
            transform: translateX(-50%) translateY(-2px);
            box-shadow: 0 12px 24px rgba(16, 185, 129, 0.4);
        }

        /* Loading Overlay */
        .scanning-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            display: none;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: 15px;
            color: white;
            border-radius: 8px;
        }

        .scanning-overlay.active {
            display: flex;
        }

        .scan-spinner {
            width: 50px;
            height: 50px;
            border: 4px solid rgba(255, 255, 255, 0.2);
            border-top-color: #3b82f6;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Results */
        .result-idle {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #94a3b8;
            text-align: center;
            gap: 10px;
        }

        .result-idle svg {
            width: 60px;
            height: 60px;
            opacity: 0.3;
            stroke: currentColor;
        }

        .result-header {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 15px;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            border-radius: 8px;
            margin-bottom: 15px;
            font-weight: 600;
            font-size: 14px;
        }

        .result-header svg {
            width: 20px;
            height: 20px;
        }

        .check-name-row {
            display: flex;
            gap: 12px;
            align-items: flex-start;
            padding: 15px;
            background: white;
            border: 1px solid #e0e7ff;
            border-left: 4px solid #3b82f6;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .check-icon {
            flex-shrink: 0;
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .check-icon svg {
            width: 24px;
            height: 24px;
            stroke: white;
            stroke-width: 3;
        }

        .medicine-name-big {
            font-size: 18px;
            font-weight: 700;
            color: #1e40af;
            margin: 0;
            word-break: break-word;
        }

        .medicine-subtitle {
            font-size: 13px;
            color: #64748b;
            margin: 4px 0 0 0;
        }

        .confidence-row {
            padding: 12px 15px;
            background: white;
            border: 1px solid #e0e7ff;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .conf-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 600;
            color: #1e40af;
        }

        .conf-bar-bg {
            height: 6px;
            background: #e0e7ff;
            border-radius: 3px;
            overflow: hidden;
        }

        .conf-bar {
            height: 100%;
            background: linear-gradient(90deg, #3b82f6, #2563eb);
            transition: width 0.3s ease;
        }

        .info-grid-2x2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 15px;
        }

        .info-chip {
            padding: 12px;
            background: white;
            border: 1px solid #e0e7ff;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .chip-label {
            font-size: 11px;
            font-weight: 700;
            color: #3b82f6;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .chip-value {
            font-size: 14px;
            font-weight: 700;
            color: #1e3a8a;
        }

        #allPredictions {
            padding: 15px;
            background: white;
            border: 1px solid #e0e7ff;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .prediction-row {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-bottom: 10px;
        }

        .prediction-row:last-child {
            margin-bottom: 0;
        }

        .pred-label {
            font-size: 12px;
            font-weight: 600;
            color: #1e40af;
            min-width: 80px;
        }

        .pred-bar-bg {
            flex: 1;
            height: 4px;
            background: #e0e7ff;
            border-radius: 2px;
            overflow: hidden;
        }

        .pred-bar {
            height: 100%;
            background: linear-gradient(90deg, #3b82f6, #2563eb);
        }

        .pred-pct {
            font-size: 11px;
            font-weight: 600;
            color: #64748b;
            min-width: 35px;
            text-align: right;
        }

        /* Progress */
        .ocr-progress-wrap {
            padding: 12px;
            background: white;
            border: 1px solid #e0e7ff;
            border-radius: 6px;
            display: none;
        }

        .ocr-progress-label {
            font-size: 12px;
            font-weight: 600;
            color: #3b82f6;
            margin-bottom: 8px;
        }

        .ocr-bar-bg {
            height: 4px;
            background: #e0e7ff;
            border-radius: 2px;
            overflow: hidden;
        }

        .ocr-bar {
            height: 100%;
            background: linear-gradient(90deg, #3b82f6, #2563eb);
            width: 0%;
            transition: width 0.3s ease;
        }

        /* Footer */
        .scanner-footer {
            padding: 15px 20px;
            background: white;
            border-top: 1px solid #e0e7ff;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .btn-cancel, .btn-reserve {
            padding: 10px 24px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-cancel {
            background: #f0f4f8;
            color: #475569;
            border: 1px solid #e0e7ff;
        }

        .btn-cancel:hover {
            background: #e0e7ff;
            color: #1e40af;
        }

        .btn-reserve {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
        }

        .btn-reserve:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3);
        }

        .btn-reserve:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }



        /* Responsive */
        @media (max-width: 900px) {
            .scanner-body {
                flex-direction: column;
                padding: 15px;
                gap: 15px;
            }

            .scanner-modal {
                height: auto;
                max-height: 90vh;
            }

            .upload-zone, .camera-feed-container {
                height: 300px;
            }

            .info-grid-2x2 {
                grid-template-columns: 1fr;
            }

            .reticle {
                width: 100px;
                height: 100px;
            }
        }

        /* ════════════════════════════════════════════════
           FLOATING ACTION BUTTONS (Zoom + Guide)
        ════════════════════════════════════════════════ */
        .floating-buttons {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1050;
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
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        .fab:hover {
            background: #5B7A92;
            box-shadow: 0 6px 16px rgba(0,0,0,0.2);
            transform: scale(1.1);
        }
        .fab:active { transform: scale(0.95); }
        .fab:disabled {
            background: #ccc;
            cursor: not-allowed;
            opacity: 0.6;
            transform: none;
        }

        #medZoomInBtn  { animation: fabSlideIn 0.4s ease 0s both; }
        #medZoomOutBtn { animation: fabSlideIn 0.4s ease 0.1s both; }
        #medGuideBtn   { animation: fabSlideIn 0.4s ease 0.2s both; }

        @keyframes fabSlideIn {
            from { opacity:0; transform:translateY(30px); }
            to   { opacity:1; transform:translateY(0); }
        }

        /* page-wrapper zoom origin */
        .page-wrapper {
            transform-origin: top center;
            transition: transform 0.2s ease-out;
        }

        /* ════════════════════════════════════════════════
           GUIDE MODAL OVERLAY
        ════════════════════════════════════════════════ */
        .guide-modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 9000;
        }
        .guide-modal-overlay.active {
            display: flex;
            align-items: center;
            justify-content: center;
            animation: gmFadeIn 0.3s ease;
        }
        @keyframes gmFadeIn { from{opacity:0} to{opacity:1} }

        .guide-modal-box {
            background: white;
            border-radius: 12px;
            width: 90%;
            max-width: 860px;
            height: 78vh;
            display: flex;
            flex-direction: column;
            box-shadow: 0 10px 40px rgba(0,0,0,0.22);
            overflow: hidden;
            animation: gmSlideUp 0.3s ease;
        }
        @keyframes gmSlideUp {
            from { transform:translateY(30px); opacity:0; }
            to   { transform:translateY(0);    opacity:1; }
        }

        .guide-modal-header {
            background: #7393A7;
            padding: 22px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
        }
        .guide-modal-header h2 {
            color: white;
            font-family: "Bricolage Grotesque", sans-serif;
            font-weight: 600;
            font-size: 22px;
            margin: 0;
        }
        .guide-modal-close {
            background: none;
            border: none;
            color: white;
            font-size: 30px;
            cursor: pointer;
            width: 38px; height: 38px;
            display: flex; align-items: center; justify-content: center;
            transition: opacity 0.2s;
            line-height: 1;
        }
        .guide-modal-close:hover { opacity: 0.7; }

        /* ── Tabs ── */
        .guide-modal-tabs-bar {
            background: white;
            padding: 0 30px;
            border-bottom: 1px solid #e8ecf1;
            flex-shrink: 0;
        }
        .guide-modal-tabs {
            display: flex;
            gap: 0;
            flex-wrap: wrap;
        }
        .gm-tab-btn {
            padding: 12px 18px;
            background: none;
            border: none;
            color: #7393A7;
            font-family: "Bricolage Grotesque", sans-serif;
            font-size: 13.5px;
            font-weight: 500;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.25s ease;
            position: relative;
            top: 1px;
        }
        .gm-tab-btn:hover { color: #5B7A92; }
        .gm-tab-btn.active {
            color: #2d3f50;
            border-bottom-color: #7393A7;
            font-weight: 600;
        }

        /* ── Body ── */
        .guide-modal-body {
            flex: 1;
            overflow-y: auto;
            padding: 28px 30px;
            background: white;
        }
        .guide-modal-body::-webkit-scrollbar { width: 8px; }
        .guide-modal-body::-webkit-scrollbar-track { background: #f1f1f1; }
        .guide-modal-body::-webkit-scrollbar-thumb { background: #7393A7; border-radius: 4px; }
        .guide-modal-body::-webkit-scrollbar-thumb:hover { background: #5B7A92; }

        /* ── Tab content ── */
        .gm-tab-content { display: none; }
        .gm-tab-content.active {
            display: block;
            animation: gmFadeIn 0.3s ease;
        }

        /* ── TTS bar inside modal ── */
        .gm-tts-bar {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #F8FAFC;
            border: 1px solid #D9EAFD;
            border-radius: 10px;
            padding: 10px 16px;
            margin-bottom: 22px;
        }
        .gm-tts-bar span {
            font-size: 13px;
            color: #7393A7;
            font-family: "Bricolage Grotesque", sans-serif;
            flex: 1;
        }
        .gm-tts-read-btn, .gm-tts-stop-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            padding: 6px 16px;
            border-radius: 20px;
            border: 1px solid #BCCCDC;
            background: #D9EAFD;
            color: #2d3f50;
            cursor: pointer;
            font-family: "Bricolage Grotesque", sans-serif;
            transition: background 0.2s ease;
        }
        .gm-tts-read-btn:hover { background: #BCCCDC; }
        .gm-tts-stop-btn { background: #F8FAFC; }
        .gm-tts-stop-btn:hover { background: #D9EAFD; }

        /* ── Step ── */
        .gm-step {
            margin-bottom: 26px;
            padding-bottom: 22px;
            border-bottom: 1px solid #e8ecf1;
        }
        .gm-step:last-child { border-bottom: none; margin-bottom: 0; }
        .gm-step h3 {
            color: #7393A7;
            font-family: "Bricolage Grotesque", sans-serif;
            font-size: 17px;
            margin-bottom: 10px;
        }
        .gm-step p { color: #333; line-height: 1.65; margin-bottom: 10px; }
        .gm-step ul { margin-left: 20px; margin-bottom: 14px; }
        .gm-step li { color: #333; line-height: 1.65; margin-bottom: 7px; }

        .gm-step-read-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 12px;
            padding: 5px 14px;
            border-radius: 20px;
            border: 1px solid #BCCCDC;
            background: #F8FAFC;
            color: #7393A7;
            cursor: pointer;
            font-family: "Bricolage Grotesque", sans-serif;
            transition: background 0.2s ease;
            margin-top: 8px;
        }
        .gm-step-read-btn:hover { background: #D9EAFD; }
        .gm-step-read-btn.reading {
            background: #D9EAFD;
            border-color: #9AA6B2;
            color: #2d3f50;
        }

        .gm-tts-dot {
            width: 6px; height: 6px;
            border-radius: 50%;
            background: #9AA6B2;
            display: inline-block;
            animation: gmDotPulse 1s infinite;
        }
        @keyframes gmDotPulse { 0%,100%{opacity:1} 50%{opacity:0.3} }

        /* ── Image placeholder ── */
        .gm-step-img-placeholder {
            width: 100%;
            min-height: 190px;
            background: #F4F8FA;
            border: 2px dashed #D9EAFD;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #9AA6B2;
            font-size: 13px;
            text-align: center;
            padding: 20px;
            font-family: "Bricolage Grotesque", sans-serif;
            border-radius: 8px;
            margin: 14px 0;
        }

        /* ── Info box ── */
        .gm-info-box {
            background: #F8FAFC;
            padding: 15px 18px;
            border-left: 4px solid #7393A7;
            border-radius: 6px;
            margin: 14px 0;
            font-size: 14px;
            color: #5A6B7A;
        }
        .gm-info-box h4 {
            color: #2d3f50;
            margin: 0 0 8px 0;
            font-family: "Bricolage Grotesque", sans-serif;
            font-size: 15px;
        }
        .gm-info-box p { margin: 0; line-height: 1.6; }

        @media (max-width: 768px) {
            .floating-buttons { bottom:20px; right:20px; }
            .fab { width:50px; height:50px; font-size:20px; }
            .guide-modal-box { height:85vh; }
            .guide-modal-header { padding:18px 20px; }
            .guide-modal-header h2 { font-size:18px; }
            .guide-modal-body { padding:20px; }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- MEDICINE SCANNER MODAL -->
    <div class="scanner-overlay" id="scannerOverlay">
        <div class="scanner-modal">
            <div class="scanner-body">
                <!-- LEFT: UPLOAD / CAMERA MODE -->
                <div class="upload-col">
                    <!-- Mode Toggle -->
                    <div class="mode-toggle">
                        <button class="mode-btn active" id="uploadModeBtn">
                            <i class='bx bx-upload'></i> Upload
                        </button>
                        <button class="mode-btn" id="cameraModeBtn">
                            <i class='bx bx-camera'></i> Camera
                        </button>
                    </div>

                    <!-- UPLOAD ZONE -->
                    <div class="upload-zone" id="uploadZone">
                        <img class="preview-img" id="previewImg" src="" alt="Preview">
                        
                        <div class="upload-placeholder">
                            <div class="upload-icon-wrap">
                                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/>
                                </svg>
                            </div>
                            <h5>Click to Upload Medicine Image</h5>
                            <p>or drag and drop your file here</p>
                            <div class="button-group">
                                <button class="choose-btn" type="button" onclick="document.getElementById('scannerFileInput').click()">CHOOSE FILE</button>
                                <button class="camera-btn" type="button" id="scannerCameraBtn" title="Use camera">
                                    <i class='bx bx-camera'></i>
                                </button>
                            </div>
                            <span class="upload-file-note">Supports: JPG, PNG (Max: 10MB)</span>
                        </div>

                        <div class="scanning-overlay" id="scanningOverlay">
                            <div class="scan-spinner"></div>
                            <span class="scan-spinner-text" id="scanSpinnerText">Analyzing…</span>
                        </div>
                    </div>

                    <!-- CAMERA FEED -->
                    <div id="cameraFeedContainer" class="camera-feed-container">
                        <video id="camerafeed" playsinline autoplay></video>
                        <canvas id="camerafeedcanvas" style="display:none;"></canvas>
                        <div class="camera-overlay">
                            <div class="reticle"><div></div></div>
                            <p>Center medicine in view</p>
                        </div>
                        <button class="capture-btn" id="capturePhotoBtn" title="Capture photo">
                            <i class='bx bx-circle'></i> CAPTURE
                        </button>
                    </div>

                    <input type="file" id="scannerFileInput" accept="image/*" style="display:none;">

                    <!-- Progress -->
                    <div class="ocr-progress-wrap" id="ocrProgressWrap">
                        <div class="ocr-progress-label" id="ocrProgressLabel">Initializing…</div>
                        <div class="ocr-bar-bg"><div class="ocr-bar" id="ocrBar"></div></div>
                    </div>
                </div>

                <!-- RIGHT: RESULTS -->
                <div class="result-col" id="resultCol">
                    <div class="result-idle" id="resultIdle">
                        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M9 3H5a2 2 0 0 0-2 2v4m6-6h10a2 2 0 0 1 2 2v4M9 3v18m0 0h10a2 2 0 0 0 2-2V9M9 21H5a2 2 0 0 1-2-2V9m0 0h18"/>
                        </svg>
                        <p>Upload or scan a medicine<br>image to see details here</p>
                    </div>

                    <div class="result-header" id="resultHeader" style="display: none;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Medicine Detected</span>
                    </div>

                    <div id="resultContent" style="display:none;">
                        <div class="check-name-row">
                            <div class="check-icon">
                                <svg viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg>
                            </div>
                            <div style="flex: 1;">
                                <p class="medicine-name-big" id="resMedName">—</p>
                                <p class="medicine-subtitle" id="resMedSub">—</p>
                            </div>
                        </div>

                        <!-- Confidence Score -->
                        <div class="confidence-row">
                            <div class="conf-label">
                                <span>Detection Confidence</span>
                                <span id="confPct">—</span>
                            </div>
                            <div class="conf-bar-bg">
                                <div class="conf-bar" id="confBar" style="width:0%"></div>
                            </div>
                        </div>

                        <!-- All Predictions -->
                        <div id="allPredictions" style="display:none;"></div>

                        <!-- Medicine Details -->
                        <div class="info-grid-2x2">
                            <div class="info-chip">
                                <span class="chip-label">Strength</span>
                                <span class="chip-value" id="resStrength">—</span>
                            </div>
                            <div class="info-chip">
                                <span class="chip-label">Form</span>
                                <span class="chip-value" id="resForm">—</span>
                            </div>
                            <div class="info-chip">
                                <span class="chip-label">Type</span>
                                <span class="chip-value" id="resType">—</span>
                            </div>
                            <div class="info-chip">
                                <span class="chip-label">Active Ingredient</span>
                                <span class="chip-value" id="resIngredient" style="font-size:12px;">—</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="scanner-footer">
                <button class="btn-cancel" onclick="szClose()">CANCEL</button>
                <button class="btn-reserve" id="btnReserve" onclick="szReserve()" disabled>SEARCH</button>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <section class="home-section">
        <div class="home-content">
            <div style="display: flex; align-items: center; gap: 10px;">
                <i class='bx bx-menu'></i>
                <span>
                    <a href="homepage.php" style="text-decoration: none; color: white; font-size: 1.5rem;" class="text fw-semibold">PharmAssist</a>
                </span>
                <!-- Scanner trigger button -->
                <button class="open-scanner-btn" onclick="szOpen()" title="Scan medicine label">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 3v18"/>
                    </svg>
                    Scan Medicine
                </button>
                <!-- ADD THIS right after the existing Scan Medicine button -->
                <button class="open-scanner-btn" onclick="slOpen()" title="Scan medicine label">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2"/>
                        <rect x="7" y="7" width="10" height="10" rx="1"/>
                    </svg>
                    Scan Label
                </button>
            </div>

            <!-- Search Bar -->
            <div class="search-container">
                <form action="medicines.php" method="GET" id="searchForm">
                    <div class="ps-bar" id="psBar">
                        <input
                            type="text"
                            name="search"
                            id="searchInput"
                            class="ps-input"
                            placeholder="Search medicines or tap the mic..."
                            value="<?php echo htmlspecialchars($searchQuery); ?>"
                            autocomplete="off">

                        <!-- Mic (STT) button -->
                        <button type="button" class="mic-btn" id="micBtn" title="Search by voice" aria-label="Voice search">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="9" y="2" width="6" height="12" rx="3"/>
                                <path d="M5 10a7 7 0 0 0 14 0"/>
                                <line x1="12" y1="19" x2="12" y2="23"/>
                                <line x1="8" y1="23" x2="16" y2="23"/>
                            </svg>
                        </button>

                        <!-- Submit button -->
                        <button type="submit" class="ps-search-btn" aria-label="Search">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="7"/>
                                <line x1="16.5" y1="16.5" x2="22" y2="22"/>
                            </svg>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="page-wrapper">
            <?php if (!empty($searchQuery)): ?>
            <!-- Search Result Banner -->
            <div class="search-result-banner">
                <h3>Search Results for "<?php echo htmlspecialchars($searchQuery); ?>"</h3>
                <p id="searchResultCount">Loading results...</p>
                <a href="medicines.php" style="color: white; text-decoration: underline; font-size: 0.9rem;">Clear Search</a>
            </div>
            <?php endif; ?>

            <!-- Global Branch Filter -->
            <div class="global-filter">
                <h2 class="global-filter-title">Select Branches to View</h2>
                <div class="branch-toggles">
                    <?php
                    $filter_branches = $conn->query("SELECT * FROM branches ORDER BY branch_id ASC");
                    $filter_index = 1;
                    while ($filter_branch = $filter_branches->fetch_assoc()):
                    ?>
                      <label class="branch-toggle active" for="branch<?php echo $filter_index; ?>-toggle">
                        <input type="checkbox" id="branch<?php echo $filter_index; ?>-toggle" checked>
                        <span><?php echo htmlspecialchars($filter_branch['branch_name']); ?> - <?php echo htmlspecialchars($filter_branch['branch_address']); ?></span>
                      </label>
                    <?php
                      $filter_index++;
                    endwhile;
                    ?>
                    <button style="background-color: #84B179;" class="filter-btn" id="applyBranchFilter">Apply Filter</button>
                    <button class="toggle-all-btn" id="toggleAllBtn">Hide All</button>
                    <span class="filter-status" id="globalFilterStatus"></span>
                </div>
            </div>

            <!-- TTS Play-all bar -->
            <div class="tts-bar">
                <i class="bi bi-volume-up-fill" style="color:#9AA6B2; font-size:16px;"></i>
                <span class="tts-bar-label">Text to speech</span>
                <button class="tts-play-btn" id="ttsPlayAll">
                    <i class="bi bi-play-fill"></i> Play all
                </button>
                <button class="tts-stop-btn" id="ttsStop">
                    <i class="bi bi-stop-fill"></i> Stop
                </button>
            </div>

            <!-- Dynamic Branches -->
            <?php
            $branchIndex = 1;
            foreach ($branches as $branchName => $medicines):
            ?>
            <div class="branch-container" data-branch-name="<?php echo htmlspecialchars($branchName); ?>">
                <div class="branch-header">
                    <h2 class="branch-title">PharmAssist <?php echo htmlspecialchars($branchName); ?></h2>
                </div>

                <div class="cards-container" id="cardsContainer<?php echo $branchIndex; ?>">
                <?php foreach ($medicines as $med):
                    $medId       = isset($med['id'])            ? htmlspecialchars($med['id'])            : (isset($med['medicine_id']) ? htmlspecialchars($med['medicine_id']) : '0');
                    $medName     = isset($med['medicine_name']) ? htmlspecialchars($med['medicine_name']) : 'Unknown';
                    $medCategory = isset($med['category'])      ? htmlspecialchars($med['category'])      : 'Uncategorized';
                    $medCategoryLower = strtolower($medCategory);
                    $medImage    = isset($med['image_path'])    ? htmlspecialchars($med['image_path'])    : 'uploads/default.jpg';
                    $medPriceNum = isset($med['price'])         ? floatval($med['price'])                 : 0;
                    $medPrice    = number_format($medPriceNum, 2);
                    $medPriceRaw = htmlspecialchars($medPriceNum);
                    $medQuantity = isset($med['quantity'])      ? intval($med['quantity'])                : 0;

                    $rawMedName = isset($med['medicine_name']) ? (string) $med['medicine_name'] : '';
                    $unitsPerBox = 100;
                    if (preg_match('/\((\d+)\s*(?:tablet|tablets|tab|tabs|pcs?|pieces?|caps?|capsules?)\)/iu', $rawMedName, $boxMatch)) {
                        $unitsPerBox = max(1, (int) $boxMatch[1]);
                    }

                    $isPrescription  = (isset($med['prescription_required']) && $med['prescription_required'] === 'yes');
                    $prescriptionText = $isPrescription ? 'Doctor-prescribed Medication' : 'Non-prescribed Medication';

                    if ($medQuantity <= 0) {
                        $statusClass   = 'status-unavailable';
                        $statusText    = 'Out of Stock';
                        $buttonDisabled = 'disabled';
                    } elseif ($medQuantity < 5) {
                        $statusClass   = 'status-low-stock';
                        $statusText    = 'Low Stock';
                        $buttonDisabled = '';
                    } else {
                        $statusClass   = 'status-available';
                        $statusText    = 'Available';
                        $buttonDisabled = '';
                    }

                    $ttsText = "$medName. $prescriptionText. Category: $medCategory. Status: $statusText. Price: " . number_format($medPriceNum, 2) . " pesos. Branch: $branchName.";
                ?>
                    <div class="card"
                         data-category="<?php echo $medCategoryLower; ?>"
                         data-medicine-name="<?php echo $medName; ?>"
                         data-medicine-id="<?php echo $medId; ?>"
                         data-tts="<?php echo htmlspecialchars($ttsText); ?>">
                        <div class="card-image">
                            <img src="<?php echo $medImage; ?>" alt="<?php echo $medName; ?>">
                        </div>
                        <div class="card-body">
                            <div class="card-title"><?php echo $medName; ?></div>
                            <div class="prescription-status"><?php echo $prescriptionText; ?></div>
                            <div class="card-category"><?php echo $medCategory; ?></div>
                            <div class="card-status <?php echo $statusClass; ?>"><?php echo $statusText; ?></div>
                            <div class="card-price">₱<?php echo $medPrice; ?></div>
                            <button class="reserve-button" 
                                data-id="<?php echo $medId; ?>" 
                                data-name="<?php echo $medName; ?>" 
                                data-price="<?php echo $medPriceRaw; ?>" 
                                data-prescription-required="<?php echo $isPrescription ? 'yes' : 'no'; ?>"
                                data-units-per-box="<?php echo (int) $unitsPerBox; ?>"
                                data-image="<?php echo htmlspecialchars($medImage); ?>"
                                data-branch-id="<?php echo isset($med['branch_id']) && $med['branch_id'] > 0 ? (int)$med['branch_id'] : 1; ?>"
                                data-branch-name="<?php echo htmlspecialchars($branchName); ?>"
                                <?php echo ($buttonDisabled || !$isApproved) ? 'disabled' : ''; ?>>
                                <?php echo !$isApproved ? 'Pending Approval' : 'Reserve'; ?>
                            </button>

                            <button class="tts-card-btn" data-tts="<?php echo htmlspecialchars($ttsText); ?>">
                                <i class="bi bi-volume-up"></i> Read
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            </div>
            <?php
            $branchIndex++;
            endforeach;
            ?>

            <!-- No Results Message -->
            <div class="no-results-message" id="noResultsMessage" style="display: none;">
                <i class="bi bi-search"></i>
                <h4>No medicines found</h4>
                <p>We couldn't find any medicines matching "<span id="noResultsQuery"></span>" in any branch.</p>
                <p>Try searching with a different keyword or <a href="medicines.php">browse all medicines</a>.</p>
            </div>
        </div>
    </section>

    <!-- Floating Action Buttons -->
    <div class="floating-buttons">
        <button class="fab" id="medZoomInBtn" title="Zoom In">+</button>
        <button class="fab" id="medZoomOutBtn" title="Zoom Out">−</button>
        <button class="fab" id="medGuideBtn" title="Page Guide">?</button>
    </div>

    <!-- ═══════════════════════════════════════════════════
         GUIDE MODAL
    ═══════════════════════════════════════════════════ -->
    <div class="guide-modal-overlay" id="guideModalOverlay">
      <div class="guide-modal-box">

        <!-- Header -->
        <div class="guide-modal-header">
          <h2>Medicines Page Guide</h2>
          <button class="guide-modal-close" id="guideModalClose">&times;</button>
        </div>

        <!-- Tab Bar -->
        <div class="guide-modal-tabs-bar">
          <div class="guide-modal-tabs">
            <button class="gm-tab-btn active" data-gmtab="gmt-guide">Page Guide</button>
            <button class="gm-tab-btn" data-gmtab="gmt-tts">Text-to-Speech</button>
            <button class="gm-tab-btn" data-gmtab="gmt-stt">Speech-to-Text</button>
            <button class="gm-tab-btn" data-gmtab="gmt-img">Image-to-Text</button>
          </div>
        </div>

        <!-- Body -->
        <div class="guide-modal-body" id="guideModalBody">

          <!-- ══════════════════════════════
               TAB 1 — PAGE GUIDE
          ══════════════════════════════ -->
          <div id="gmt-guide" class="gm-tab-content active">

            <!-- TTS bar -->
            <div class="gm-tts-bar">
              <i class="bi bi-volume-up-fill" style="color:#9AA6B2; font-size:15px;"></i>
              <span>Text-to-Speech</span>
              <button class="gm-tts-read-btn" data-gmread="gmt-guide">
                <i class="bi bi-play-fill"></i> Read guide
              </button>
              <button class="gm-tts-stop-btn" data-gmstop>
                <i class="bi bi-stop-fill"></i> Stop
              </button>
            </div>

            <!-- Step 1: Overview -->
            <div class="gm-step" data-step="Medicines page overview. The Medicines page is where you can browse all available medicines across all PharmAssist branches. You can search, filter by branch, view medicine details, and reserve the ones you need.">
              <h3>Overview</h3>
              <p>The <strong>Medicines</strong> page lets you browse, search, and reserve medicines available across all PharmAssist branches. Each medicine card shows the name, category, prescription requirement, availability status, and price.</p>
              <div class="gm-step-img"><img src="screenshots/medicine1.png" alt="Medicines overview" style="width:100%; border-radius:8px; margin-top:10px;"></div>
              <button class="gm-step-read-btn"><i class="bi bi-volume-up"></i> Read step</button>
            </div>

            <!-- Step 2: Search -->
            <div class="gm-step" data-step="Using the search bar. At the top of the page you will find a search bar. Type the name of the medicine you are looking for and press Enter or click the search button. The page will highlight matching results and scroll you to the first match. You can also use the microphone button for voice search, or the Scan Label button to search by image.">
              <h3>Searching for Medicines</h3>
              <p>Use the search bar at the top to find a specific medicine quickly:</p>
              <ul>
                <li><strong>Type &amp; Search:</strong> Type the medicine name and press <kbd>Enter</kbd> or click the search icon.</li>
                <li><strong>Voice Search:</strong> Click the <i class="bi bi-mic"></i> microphone icon to speak the medicine name.</li>
                <li><strong>Scan Label:</strong> Click the <strong>Scan Medicine</strong> button to identify a medicine by photo.</li>
                <li>Matching cards are highlighted and the page scrolls to the first result automatically.</li>
              </ul>
              <div class="gm-step-img"><img src="screenshots/medicine2.png" alt="Search bar with mic and scan medicine buttons" style="width:100%; border-radius:8px; margin-top:10px;"></div>
              <button class="gm-step-read-btn"><i class="bi bi-volume-up"></i> Read step</button>
            </div>

            <!-- Step 3: Branch filter -->
            <div class="gm-step" data-step="Branch filter. Below the search bar you will find the branch filter section. It lists all PharmAssist branches. Check or uncheck each branch to control which branches are shown, then click Apply Filter. You can also use the Hide All or Show All button to toggle all branches at once.">
              <h3>Filtering by Branch</h3>
              <p>Use the <strong>Select Branches to View</strong> panel to control which branches are displayed:</p>
              <ul>
                <li>Check or uncheck individual branches using the toggle labels.</li>
                <li>Click <strong>Apply Filter</strong> to apply your selection.</li>
                <li>Use <strong>Hide All / Show All</strong> to quickly toggle all branches.</li>
                <li>A status message shows how many branches are currently visible.</li>
              </ul>
               <div class="gm-step-img"><img src="screenshots/medicine3.png" alt="Branch filter panel" style="width:100%; border-radius:8px; margin-top:10px;"></div>
              <button class="gm-step-read-btn"><i class="bi bi-volume-up"></i> Read step</button>
            </div>

            <!-- Step 4: Medicine cards -->
            <div class="gm-step" data-step="Medicine cards. Each medicine is shown as a card. The card displays the medicine image, name, whether it requires a prescription, its category, stock status, and price. If the medicine is in stock and your account is verified, you can click the Reserve button to add it to your cart.">
              <h3>Understanding Medicine Cards</h3>
              <p>Each card shows the following information:</p>
              <ul>
                <li><strong>Image:</strong> A photo of the medicine.</li>
                <li><strong>Name:</strong> The full medicine name including strength/form.</li>
                <li><strong>Prescription Status:</strong> Doctor-prescribed or Non-prescribed.</li>
                <li><strong>Category:</strong> The medicine classification (e.g., Antibiotic, Vitamin).</li>
                <li><strong>Stock Status:</strong> Available (green), Low Stock (yellow), or Out of Stock (red).</li>
                <li><strong>Price:</strong> Retail price per unit.</li>
                <li><strong>Reserve Button:</strong> Adds the medicine to your cart (requires a verified account).</li>
                <li><strong>Read Button:</strong> Reads out the medicine details aloud via Text-to-Speech.</li>
              </ul>
              <div class="gm-step-img"><img src="screenshots/medicine4.png" alt="Medicine cards" style="width:100%; border-radius:8px; margin-top:10px;"></div>
              <button class="gm-step-read-btn"><i class="bi bi-volume-up"></i> Read step</button>
            </div>

            <!-- Step 5: Reserving -->
            <div class="gm-step" data-step="Reserving a medicine. To reserve a medicine, your account must be ID-verified and approved by the manager. Click the Reserve button on any available medicine card to add it to your cart. You will see a confirmation message once it has been added successfully.">
              <h3>How to Reserve a Medicine</h3>
              <ul>
                <li>Make sure your account ID verification is <strong>Approved</strong> by the manager.</li>
                <li>Find the medicine you need and confirm the stock status shows <strong>Available</strong>.</li>
                <li>Click the <strong>Reserve</strong> button on the card.</li>
                <li>A confirmation message will appear once the medicine is added to your cart.</li>
                <li>Proceed to your cart to complete the reservation process.</li>
              </ul>
              <div class="gm-step-img"><img src="screenshots/medicine5.png" alt="Medicine cards" style="width:100%; border-radius:8px; margin-top:10px;"></div>
              <button class="gm-step-read-btn"><i class="bi bi-volume-up"></i> Read step</button>
            </div>

          </div><!-- /gmt-guide -->


          <!-- ══════════════════════════════
               TAB 2 — TEXT-TO-SPEECH
          ══════════════════════════════ -->
          <div id="gmt-tts" class="gm-tab-content">

            <!-- TTS bar -->
            <div class="gm-tts-bar">
              <i class="bi bi-volume-up-fill" style="color:#9AA6B2; font-size:15px;"></i>
              <span>Text-to-Speech</span>
              <button class="gm-tts-read-btn" data-gmread="gmt-tts">
                <i class="bi bi-play-fill"></i> Read guide
              </button>
              <button class="gm-tts-stop-btn" data-gmstop>
                <i class="bi bi-stop-fill"></i> Stop
              </button>
            </div>

            <h3 style="color:#7393A7; margin-top:0;">Text-to-Speech (TTS) Feature</h3>

            <div class="gm-info-box">
              <h4>What is Text-to-Speech?</h4>
              <p>Text-to-Speech (TTS) technology reads content aloud so you can listen instead of reading. On the Medicines page, TTS helps you hear medicine details, making it easier for customers with visual impairments or those who prefer audio information.</p>
            </div>

            <!-- Step: Play All -->
            <div class="gm-step" data-step="Using the Play All button. At the top of the medicines list you will find a Text-to-Speech bar with a Play All button. Click Play All to hear the details of every visible medicine card read aloud one by one. Click Stop at any time to pause the reading.">
              <h3>Using the "Play All" Button</h3>
              <p>The <strong>Play All</strong> button reads every visible medicine card from top to bottom:</p>
              <ul>
                <li>Look for the <strong>Text-to-speech</strong> bar just above the medicine cards.</li>
                <li>Click <strong><i class="bi bi-play-fill"></i> Play all</strong> to start reading all visible medicines.</li>
                <li>The currently reading card will show a pulsing animation on its Read button.</li>
                <li>Click <strong><i class="bi bi-stop-fill"></i> Stop</strong> at any time to stop the audio.</li>
              </ul>
                <div class="gm-step-img"><img src="screenshots/tts1.png" alt="Medicine cards" style="width:100%; border-radius:8px; margin-top:10px;"></div>
              <button class="gm-step-read-btn"><i class="bi bi-volume-up"></i> Read step</button>
            </div>

            <!-- Step: Per-card Read -->
            <div class="gm-step" data-step="Reading individual medicine cards. Each medicine card has a Read button at the bottom. Click it to hear the details of that specific medicine including its name, prescription requirement, category, stock status, price, and branch. Click the button again to stop.">
              <h3>Reading Individual Medicine Cards</h3>
              <p>Each medicine card has its own <strong><i class="bi bi-volume-up"></i> Read</strong> button:</p>
              <ul>
                <li>Scroll to any medicine card you want to hear.</li>
                <li>Click the <strong>Read</strong> button at the bottom of the card.</li>
                <li>The button will show a pulsing dot while reading is in progress.</li>
                <li>Click the button again (or the Stop button above) to stop reading.</li>
                <li>The audio includes: medicine name, prescription status, category, stock status, price, and branch.</li>
              </ul>
                <div class="gm-step-img"><img src="screenshots/tts2.png" alt="Medicine card with read button" style="width:100%; border-radius:8px; margin-top:10px;"></div>
              <button class="gm-step-read-btn"><i class="bi bi-volume-up"></i> Read step</button>
            </div>

            <!-- Step: Tips -->
            <div class="gm-step" data-step="Text-to-Speech tips. Adjust your device volume for comfortable listening. TTS works best in Chrome, Firefox, Safari, and Edge. If you use the branch filter to hide some branches, Play All will only read the visible cards. You can combine branch filtering and Play All to hear only the medicines at your preferred branch.">
              <h3>TTS Tips &amp; Tricks</h3>
              <ul>
                <li><strong>Adjust Volume:</strong> Use your device's volume controls for comfortable listening.</li>
                <li><strong>Filter First:</strong> Apply a branch filter before clicking Play All to hear only your preferred branch.</li>
                <li><strong>Browser Compatibility:</strong> Works best on Chrome, Firefox, Safari, and Edge.</li>
                <li><strong>Language:</strong> The voice reads in English with a Philippine accent for natural pronunciation of local medicine names.</li>
                <li><strong>Restart Anytime:</strong> Click Stop and then Play All again to restart from the beginning.</li>
              </ul>
              <button class="gm-step-read-btn"><i class="bi bi-volume-up"></i> Read step</button>
            </div>

          </div><!-- /gmt-tts -->


          <!-- ══════════════════════════════
               TAB 3 — SPEECH-TO-TEXT
          ══════════════════════════════ -->
          <div id="gmt-stt" class="gm-tab-content">

            <!-- TTS bar -->
            <div class="gm-tts-bar">
              <i class="bi bi-volume-up-fill" style="color:#9AA6B2; font-size:15px;"></i>
              <span>Text-to-Speech</span>
              <button class="gm-tts-read-btn" data-gmread="gmt-stt">
                <i class="bi bi-play-fill"></i> Read guide
              </button>
              <button class="gm-tts-stop-btn" data-gmstop>
                <i class="bi bi-stop-fill"></i> Stop
              </button>
            </div>

            <h3 style="color:#7393A7; margin-top:0;">Speech-to-Text (STT) Feature</h3>

            <div class="gm-info-box">
              <h4>What is Speech-to-Text?</h4>
              <p>Speech-to-Text (STT) converts your spoken words into typed text so you can search for medicines by voice — no typing needed. Just speak the medicine name and PharmAssist will search for it automatically.</p>
            </div>

            <!-- Step: Activate voice search -->
            <div class="gm-step" data-step="How to activate voice search. In the search bar at the top of the page, click the microphone icon on the right side of the search field. A Voice Search dialog will open. The microphone starts listening automatically. Speak the name of the medicine clearly. Your speech will appear as text in the dialog.">
              <h3>How to Activate Voice Search</h3>
              <ul>
                <li><strong>Step 1:</strong> Look at the search bar at the top of the page.</li>
                <li><strong>Step 2:</strong> Click the <i class="bi bi-mic"></i> <strong>microphone icon</strong> on the right side of the search field.</li>
                <li><strong>Step 3:</strong> The <strong>Voice Search</strong> dialog will open and immediately start listening.</li>
                <li><strong>Step 4:</strong> Speak the medicine name clearly and at a normal pace.</li>
                <li><strong>Step 5:</strong> Your spoken words will appear as text in the dialog.</li>
              </ul>
              <div class="gm-step-img-placeholder"><img src="screenshots/stt1.png" alt="Search bar with mic and scan label buttons" style="width:100%; border-radius:8px; margin-top:10px;"></div>
              <button class="gm-step-read-btn"><i class="bi bi-volume-up"></i> Read step</button>
            </div>

            <!-- Step: Voice search dialog -->
            <div class="gm-step" data-step="Using the Voice Search dialog. After clicking the microphone icon, a dialog appears. The large circle in the center is the microphone button. It turns blue and pulses while it is listening. Once you finish speaking, the recognized text appears above. You can click the Search button to search, or tap the microphone circle again to re-record. Click Cancel to close without searching.">
              <h3>Using the Voice Search Dialog</h3>
              <p>Once the Voice Search dialog opens:</p>
              <ul>
                <li>The center <strong>microphone circle</strong> pulses when it is actively listening.</li>
                <li>Speak your search term — your words appear in the transcript area above the mic.</li>
                <li>When you stop speaking, recognition ends and the status shows <em>"Got it! Press Search."</em></li>
                <li>Click <strong>Search</strong> to submit and find the matching medicines.</li>
                <li>Tap the <strong>mic circle</strong> again to re-record if the text was not accurate.</li>
                <li>Click <strong>Cancel</strong> to close the dialog without searching.</li>
              </ul>
              <div class="gm-step-img-placeholder"><img src="screenshots/stt2.png" alt="Search bar with mic and scan label buttons" style="width:100%; border-radius:8px; margin-top:10px;"> </div>
              <button class="gm-step-read-btn"><i class="bi bi-volume-up"></i> Read step</button>
            </div>

            <!-- Step: Examples & tips -->
            <div class="gm-step" data-step="Voice search examples and tips. You can say medicine names like Paracetamol, Amoxicillin, Losartan, or Metformin. For best results, speak in a quiet environment, pronounce the medicine name clearly, and make sure your browser has microphone permission enabled.">
              <h3>Voice Search Examples &amp; Tips</h3>
              <p>Try saying these medicine names clearly:</p>
              <ul>
                <li>"Paracetamol"</li>
                <li>"Amoxicillin"</li>
                <li>"Losartan"</li>
                <li>"Metformin"</li>
              </ul>
              <p><strong>Tips for better accuracy:</strong></p>
              <ul>
                <li><strong>Quiet Environment:</strong> Use voice search away from loud background noise.</li>
                <li><strong>Clear Pronunciation:</strong> Speak at a natural pace — not too fast or too slow.</li>
                <li><strong>Microphone Permission:</strong> Allow microphone access in your browser when prompted.</li>
                <li><strong>Re-record:</strong> If the result is wrong, tap the mic circle again to try once more.</li>
                <li><strong>Fallback:</strong> If voice search doesn't work, just type the medicine name manually.</li>
              </ul>
              <button class="gm-step-read-btn"><i class="bi bi-volume-up"></i> Read step</button>
            </div>

          </div><!-- /gmt-stt -->


          <!-- ══════════════════════════════
               TAB 4 — IMAGE-TO-TEXT
          ══════════════════════════════ -->
          <div id="gmt-img" class="gm-tab-content">

            <!-- TTS bar -->
            <div class="gm-tts-bar">
              <i class="bi bi-volume-up-fill" style="color:#9AA6B2; font-size:15px;"></i>
              <span>Text-to-Speech</span>
              <button class="gm-tts-read-btn" data-gmread="gmt-img">
                <i class="bi bi-play-fill"></i> Read guide
              </button>
              <button class="gm-tts-stop-btn" data-gmstop>
                <i class="bi bi-stop-fill"></i> Stop
              </button>
            </div>

            <h3 style="color:#7393A7; margin-top:0;">Image-to-Text / Medicine Scanner Feature</h3>

            <div class="gm-info-box">
              <h4>What is the Medicine Scanner?</h4>
              <p>The Medicine Scanner uses image recognition (AI) to identify a medicine from a photo of its packaging or label. Simply upload an image or take a live photo with your camera, and the system will detect the medicine name and show matching results from the PharmAssist inventory.</p>
            </div>

            <!-- Step: Opening the scanner -->
            <div class="gm-step" data-step="How to open the medicine scanner. At the top of the medicines page, next to the PharmAssist logo, you will find a Scan Label button. Click it to open the Medicine Scanner. The scanner has two panels: the left panel is where you provide the image, and the right panel shows the detected medicine information.">
              <h3>Opening the Medicine Scanner</h3>
              <ul>
                <li><strong>Step 1:</strong> Look at the top navigation bar of the Medicines page.</li>
                <li><strong>Step 2:</strong> Click the <strong>Scan Label</strong> button (next to the PharmAssist logo).</li>
                <li><strong>Step 3:</strong> The <strong>Medicine Scanner</strong> window will open.</li>
                <li>The left panel is for providing the image; the right panel shows the detected results.</li>
              </ul>
              <div class="gm-step-img"><img src="screenshots/imgt1.png" alt="Medicine card with read button" style="width:100%; border-radius:8px; margin-top:10px;"></div>
              <button class="gm-step-read-btn"><i class="bi bi-volume-up"></i> Read step</button>
            </div>

            <!-- Step: Upload mode -->
            <div class="gm-step" data-step="Uploading an image of a medicine. Inside the scanner, the Upload tab is selected by default. Click Choose File to select an image from your device, or drag and drop an image file directly into the upload area. The scanner will automatically analyze the image and show the identified medicine in the right panel.">
              <h3>Identifying a Medicine by Uploading an Image</h3>
              <p>Use the <strong>Upload</strong> mode to identify a medicine from an existing photo:</p>
              <ul>
                <li><strong>Step 1:</strong> Make sure <strong>Upload</strong> is selected (default tab in the scanner).</li>
                <li><strong>Step 2:</strong> Click <strong>CHOOSE FILE</strong> to pick an image from your device, or drag and drop an image into the upload area.</li>
                <li><strong>Step 3:</strong> The scanner automatically analyzes your image.</li>
                <li><strong>Step 4:</strong> The right panel shows the detected medicine name, confidence score, strength, form, type, and active ingredient.</li>
                <li><strong>Step 5:</strong> Click <strong>RESERVE</strong> to search for that medicine and add it to your cart.</li>
              </ul>
              <div class="gm-step-img"><img src="screenshots/imgt2.png" alt="Medicine card with read button" style="width:100%; border-radius:8px; margin-top:10px;"></div>
              <button class="gm-step-read-btn"><i class="bi bi-volume-up"></i> Read step</button>
            </div>

            <!-- Step: Camera mode -->
            <div class="gm-step" data-step="Using the camera to scan a medicine. Inside the scanner, click the Camera tab. Your device camera will activate and show a live preview with a targeting frame. Hold the medicine box or bottle so the label is centered inside the frame. Click the Capture button to take a photo. The scanner will analyze the captured image and show the identified medicine on the right.">
              <h3>Scanning a Medicine with Your Camera</h3>
              <p>Use the <strong>Camera</strong> mode to scan a medicine in real time:</p>
              <ul>
                <li><strong>Step 1:</strong> Click the <strong>Camera</strong> tab at the top of the left panel.</li>
                <li><strong>Step 2:</strong> Allow camera access if your browser asks for permission.</li>
                <li><strong>Step 3:</strong> A live camera feed appears with a targeting frame (reticle) in the center.</li>
                <li><strong>Step 4:</strong> Hold the medicine box or bottle so its label is clearly inside the frame.</li>
                <li><strong>Step 5:</strong> Click the green <strong>CAPTURE</strong> button to take the photo.</li>
                <li><strong>Step 6:</strong> The scanner analyzes the captured image and shows the results on the right panel.</li>
                <li><strong>Step 7:</strong> Click <strong>RESERVE</strong> to search for and reserve the identified medicine.</li>
              </ul>
              <div class="gm-step-img"><img src="screenshots/imgt3.png" alt="Medicine card with read button" style="width:100%; border-radius:8px; margin-top:10px;"></div>
              <button class="gm-step-read-btn"><i class="bi bi-volume-up"></i> Read step</button>
            </div>

            <!-- Step: Results & reserve -->
            <div class="gm-step" data-step="Reading the scanner results. After the image is analyzed, the right panel shows the detected medicine name, a confidence percentage bar, the medicine strength, dosage form, type, and active ingredient. A higher confidence percentage means a more certain identification. If the result looks correct, click the Reserve button at the bottom of the scanner to search for that medicine in the PharmAssist inventory.">
              <h3>Understanding Scanner Results &amp; Reserving</h3>
              <p>The results panel on the right displays:</p>
              <ul>
                <li><strong>Medicine Name:</strong> The identified medicine (large blue text at the top).</li>
                <li><strong>Confidence Score:</strong> A percentage bar showing how certain the AI is about its detection.</li>
                <li><strong>Strength:</strong> The dosage strength (e.g., 500mg).</li>
                <li><strong>Form:</strong> The dosage form (e.g., Tablet, Capsule, Syrup).</li>
                <li><strong>Type:</strong> The medicine classification.</li>
                <li><strong>Active Ingredient:</strong> The primary chemical component.</li>
                <li>Click <strong>RESERVE</strong> at the bottom to search and reserve the detected medicine.</li>
                <li>Click <strong>CANCEL</strong> to close the scanner without reserving.</li>
              </ul>
                <div class="gm-step-img"><img src="screenshots/imgt4.png" alt="Medicine card with read button" style="width:100%; border-radius:8px; margin-top:10px;"></div>
              <button class="gm-step-read-btn"><i class="bi bi-volume-up"></i> Read step</button>
            </div>

            <!-- Step: Tips -->
            <div class="gm-step" data-step="Tips for better medicine scanning. For best results, use clear and well-lit photos. Make sure the medicine name and label are in focus and fully visible. Supported image formats are JPG and PNG up to 10 megabytes. If the scanner does not detect the medicine correctly, try a different angle or use the Upload mode with a clearer photo.">
              <h3>Tips for Better Scanning Results</h3>
              <ul>
                <li><strong>Good Lighting:</strong> Use well-lit environments for clearer label photos.</li>
                <li><strong>Focus on the Label:</strong> Make sure the medicine name is sharp and fully in frame.</li>
                <li><strong>Supported Formats:</strong> JPG and PNG images, up to 10MB.</li>
                <li><strong>Multiple Angles:</strong> If the first scan fails, try photographing from a different angle.</li>
                <li><strong>Clean Packaging:</strong> Remove any stickers or coverings that obscure the label.</li>
                <li><strong>Fallback:</strong> If scanning doesn't work, you can always search manually by typing or voice.</li>
              </ul>
              <button class="gm-step-read-btn"><i class="bi bi-volume-up"></i> Read step</button>
            </div>

          </div><!-- /gmt-img -->

        </div><!-- /guide-modal-body -->
      </div><!-- /guide-modal-box -->
    </div><!-- /guide-modal-overlay -->

    <!-- Voice Search Modal -->
    <div class="voice-modal-overlay" id="voiceModalOverlay">
        <div class="voice-modal">
            <p class="voice-modal-title">Voice Search</p>
            <p class="voice-modal-subtitle" id="voiceSubtitle">Tap the mic and speak...</p>
            <p class="voice-transcript placeholder" id="voiceTranscript">Your speech will appear here…</p>

            <div class="voice-mic-ring stopped" id="voiceMicRing">
                <button class="voice-mic-btn-inner" id="voiceMicCircle" title="Tap to listen">
                    <i class="bi bi-mic" id="voiceMicInnerIcon"></i>
                </button>
            </div>

            <p class="voice-modal-status" id="voiceStatusLine"></p>

            <div class="voice-modal-actions">
                <button class="voice-btn-search" id="voiceBtnSearch" disabled>Search</button>
                <button class="voice-btn-cancel" id="voiceBtnCancel">Cancel</button>
            </div>
        </div>
    </div>



    <!-- Success Popup -->
    <div class="success-popup" id="successPopup">
        <p class="check">✅ Reservation submitted!</p>
        <p>Your reservation is waiting to be approved.</p>
        <p><b>Reservation code</b> will be generated once confirmed.</p>
    </div>

    <!-- Footer -->
    <?php include 'footer.php'; ?>

    <!-- Scripts -->
    <?php
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    if ($scriptDir === '/' || $scriptDir === '' || $scriptDir === '.') {
        $tm_model_base = 'datasets/tm-models/';
    } else {
        $tm_model_base = rtrim($scriptDir, '/') . '/datasets/tm-models/';
    }
    ?>
    <script>window.MEDICINE_TM_MODEL_BASE = <?php echo json_encode($tm_model_base); ?>;</script>
    <script src="source/medicine-scanner.js?v=tm-models"></script>
    <script src="source/sidebar.js"></script>
    <script src="source/homepage.js"></script>

    <script>
document.addEventListener("DOMContentLoaded", function () {

    document.querySelectorAll(".reserve-button").forEach(button => {
        button.addEventListener("click", async () => {
            const isApproved = <?php echo json_encode($isApproved); ?>;

            if (!isApproved) {
                alert("Your account is still being verified by the manager. You cannot reserve medicines yet.");
                return;
            }

            const medId = button.dataset.id;
            const medName = button.dataset.name;
            const medPrice = parseFloat(button.dataset.price);
            const medImage = button.dataset.image || "uploads/default.jpg";
            const pr = (button.dataset.prescriptionRequired || "no").toLowerCase();
            const requiresPrescription = pr === "yes" || pr === "1" || pr === "true";

            // Prepare form data for add_to_cart.php
            const formData = new FormData();
            formData.append('medicine_id', medId);
            formData.append('medicine', medName);
            formData.append('price', medPrice);
            formData.append('quantity', 1); // Default to 1
            formData.append('requires_prescription', requiresPrescription ? 'yes' : 'no');
            formData.append('image_path', medImage);
            formData.append('discount_type', 'none'); // Default
            formData.append('branch_id', button.dataset.branchId || '');
            formData.append('branch_name', button.dataset.branchName || '');

            try {
                const response = await fetch('add_to_cart.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.success) {
                    alert('Added to cart successfully!');
                    // Optionally update cart count if there's a UI element
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                alert('Error adding to cart');
                console.error(error);
            }
        });
    });

    /* ══════════════════════════════════════════════
       TTS CORE
    ══════════════════════════════════════════════ */
    let currentCardBtn = null;

    window.speechSynthesis.onvoiceschanged = function () {
        window.speechSynthesis.getVoices();
    };

    function speak(text, onEnd) {
        window.speechSynthesis.cancel();
        const utter  = new SpeechSynthesisUtterance(text);
        utter.lang   = 'en-PH';
        utter.rate   = 1;

        const voices    = window.speechSynthesis.getVoices();
        const rosaVoice = voices.find(v => v.name === 'Microsoft Rosa Online (Natural) - English (Philippines)');
        if (rosaVoice) utter.voice = rosaVoice;

        if (onEnd) utter.onend = onEnd;
        window.speechSynthesis.speak(utter);
    }

    function stopTTS() {
        window.speechSynthesis.cancel();
        if (currentCardBtn) { resetCardBtn(currentCardBtn); currentCardBtn = null; }
    }

    function setCardReading(btn) {
        if (currentCardBtn && currentCardBtn !== btn) resetCardBtn(currentCardBtn);
        currentCardBtn = btn;
        btn.classList.add('reading');
        btn.innerHTML = '<span class="tts-dot"></span> Reading...';
    }

    function resetCardBtn(btn) {
        btn.classList.remove('reading');
        btn.innerHTML = '<i class="bi bi-volume-up"></i> Read';
    }

    /* Per-card Read buttons */
    document.querySelectorAll('.tts-card-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const text = this.getAttribute('data-tts');
            if (this.classList.contains('reading')) { stopTTS(); return; }
            setCardReading(this);
            speak(text, () => { resetCardBtn(this); currentCardBtn = null; });
        });
    });

    /* Play all visible cards */
    document.getElementById('ttsPlayAll').addEventListener('click', function () {
        const visibleCards = Array.from(document.querySelectorAll('.card[data-tts]'))
            .filter(c => !c.classList.contains('hidden-by-search') && c.style.display !== 'none');
        if (!visibleCards.length) return;

        let index = 0;
        function readNext() {
            if (index >= visibleCards.length) {
                if (currentCardBtn) { resetCardBtn(currentCardBtn); currentCardBtn = null; }
                return;
            }
            const card = visibleCards[index];
            const btn  = card.querySelector('.tts-card-btn');
            const text = card.getAttribute('data-tts');
            if (btn) setCardReading(btn);
            index++;
            speak(text, readNext);
        }
        readNext();
    });

    document.getElementById('ttsStop').addEventListener('click', stopTTS);

    /* Read reservation summary */
    const ttsReadSummaryButton = document.getElementById('ttsReadSummary');
    if (ttsReadSummaryButton) {
        ttsReadSummaryButton.addEventListener('click', function () {
            syncReservationQuantity();
            const qty   = parseInt(document.getElementById("quantity").value) || 1;
        const firstName = (document.getElementById("first_name") && document.getElementById("first_name").value) || "";
        const lastName = (document.getElementById("last_name") && document.getElementById("last_name").value) || "";
        const who = [firstName, lastName].filter(Boolean).join(" ");
        const namePart = who ? ` Name: ${who}.` : "";
        const u = getOrderUnit();
        const count = parseInt(orderCountInput.value, 10) || 1;
        let orderDesc = count + " piece(s)";
        if (u === "ten") orderDesc = count + " pack(s) of ten, " + qty + " units total";
        else if (u === "box") orderDesc = count + " box(es) of " + medUnitsPerBox + ", " + qty + " units total";
        const rxPart = medPrescriptionRequired ? " Prescription upload is required." : " Prescription is not required.";
        const discountType = getDiscountType();
        const discountPart = discountType === "none" ? " No discount applied." : ` Discount: ${discountType === "pwd" ? "Person with Disability" : "Senior Citizen"} discount applied.`;
        speak(`You are reserving ${medName}.${namePart} Order: ${orderDesc}. Unit price: ${medPrice.toFixed(2)} pesos.${rxPart}${discountPart}`);
        });
    }

    /* ══════════════════════════════════════════════
       STT (Speech-to-Text)
    ══════════════════════════════════════════════ */
    const micBtn      = document.getElementById('micBtn');
    const searchInput = document.getElementById('searchInput');
    const searchForm  = document.getElementById('searchForm');

    const voiceOverlay    = document.getElementById('voiceModalOverlay');
    const voiceMicRing    = document.getElementById('voiceMicRing');
    const voiceMicCircle  = document.getElementById('voiceMicCircle');
    const voiceMicIcon    = document.getElementById('voiceMicInnerIcon');
    const voiceSubtitle   = document.getElementById('voiceSubtitle');
    const voiceTranscript = document.getElementById('voiceTranscript');
    const voiceStatusLine = document.getElementById('voiceStatusLine');
    const voiceBtnSearch  = document.getElementById('voiceBtnSearch');
    const voiceBtnCancel  = document.getElementById('voiceBtnCancel');

    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

    if (SpeechRecognition) {
        let recognition  = null;
        let listening    = false;
        let capturedText = '';

        function setIdleUI() {
            voiceMicRing.classList.add('stopped');
            voiceMicCircle.classList.remove('listening');
            voiceMicIcon.className    = 'bi bi-mic';
            voiceSubtitle.textContent = 'Tap the mic and speak...';
            voiceStatusLine.innerHTML = '';
            voiceStatusLine.className = 'voice-modal-status';
            listening = false;
        }

        function setListeningUI() {
            voiceMicRing.classList.remove('stopped');
            voiceMicCircle.classList.add('listening');
            voiceMicIcon.className    = 'bi bi-mic-fill';
            voiceSubtitle.textContent = 'Listening…';
            voiceStatusLine.innerHTML = `<span class="voice-wave"><span></span><span></span><span></span><span></span><span></span></span>&nbsp;Listening...`;
            voiceStatusLine.className = 'voice-modal-status';
            listening = true;
        }

        function setSuccessUI(text) {
            const cleanedText = text.replace(/[.,!?;:]+$/, '');
            voiceTranscript.textContent = cleanedText;
            voiceTranscript.classList.remove('placeholder');
            voiceSubtitle.textContent   = 'Got it! Press Search or tap mic again.';
            voiceStatusLine.textContent = '✓ Ready to search';
            voiceStatusLine.className   = 'voice-modal-status success';
            voiceBtnSearch.disabled     = false;
            capturedText                = cleanedText;
        }

        function setErrorUI(msg) {
            voiceSubtitle.textContent   = 'Tap the mic and speak...';
            voiceStatusLine.textContent = msg;
            voiceStatusLine.className   = 'voice-modal-status error';
            voiceBtnSearch.disabled     = true;
        }

        function resetUI() {
            capturedText = '';
            voiceTranscript.textContent = 'Your speech will appear here…';
            voiceTranscript.classList.add('placeholder');
            voiceBtnSearch.disabled     = true;
            setIdleUI();
        }

        function startListening() {
            if (recognition) {
                recognition.onresult = null;
                recognition.onend    = null;
                recognition.onerror  = null;
                try { recognition.abort(); } catch(e) {}
            }

            recognition = new SpeechRecognition();
            recognition.lang            = 'en-US';
            recognition.interimResults  = true;
            recognition.maxAlternatives = 1;
            recognition.continuous      = false;

            let finalText = '';

            recognition.onstart = function () {
                setListeningUI();
            };

            recognition.onresult = function (event) {
                let interim = '';
                finalText = '';
                for (let i = event.resultIndex; i < event.results.length; i++) {
                    if (event.results[i].isFinal) {
                        finalText += event.results[i][0].transcript;
                    } else {
                        interim += event.results[i][0].transcript;
                    }
                }
                const live = finalText || interim;
                if (live) {
                    const cleanedText = live.replace(/[.,!?;:]+$/, '');
                    voiceTranscript.textContent = cleanedText;
                    voiceTranscript.classList.remove('placeholder');
                }
            };

            recognition.onend = function () {
                setIdleUI();
                const result = (finalText || voiceTranscript.textContent || '').trim().replace(/[.,!?;:]+$/, '');
                if (result && result !== 'Your speech will appear here…') {
                    setSuccessUI(result);
                } else {
                    setErrorUI("Didn't catch that — tap mic to try again.");
                }
            };

            recognition.onerror = function (event) {
                setIdleUI();
                const msgs = {
                    'no-speech'    : "No speech detected — tap mic to try again.",
                    'audio-capture': "Microphone not found. Check your device.",
                    'not-allowed'  : "Mic access denied. Allow microphone in your browser.",
                    'network'      : "Network error. Check your connection.",
                    'aborted'      : ''
                };
                const msg = msgs[event.error];
                if (msg !== '') setErrorUI(msg || ('Error: ' + event.error));
            };

            recognition.start();
        }

        function stopListening() {
            if (recognition) {
                try { recognition.stop(); } catch(e) {}
            }
        }

        function openVoiceModal() {
            resetUI();
            voiceOverlay.classList.add('active');
            setTimeout(startListening, 150);
        }

        function closeVoiceModal() {
            stopListening();
            voiceOverlay.classList.remove('active');
            resetUI();
        }

        micBtn.addEventListener('click', openVoiceModal);

        voiceMicCircle.addEventListener('click', function () {
            if (listening) {
                stopListening();
            } else {
                voiceTranscript.textContent = 'Your speech will appear here…';
                voiceTranscript.classList.add('placeholder');
                voiceBtnSearch.disabled = true;
                startListening();
            }
        });

        voiceBtnSearch.addEventListener('click', function () {
            if (capturedText) {
                searchInput.value = capturedText;
                closeVoiceModal();
                searchForm.submit();
            }
        });

        voiceBtnCancel.addEventListener('click', closeVoiceModal);

        voiceOverlay.addEventListener('click', function (e) {
            if (e.target === voiceOverlay) closeVoiceModal();
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && voiceOverlay.classList.contains('active')) closeVoiceModal();
        });

    } else {
        if (micBtn) micBtn.style.display = 'none';
    }

    if (searchForm) {
        searchForm.addEventListener('submit', function (e) {
            if (!searchInput.value.trim()) {
                e.preventDefault();
                window.location.href = 'medicines.php';
            }
        });
    }

    /* ══════════════════════════════════════════════
       SEARCH FILTERING
    ══════════════════════════════════════════════ */
    const urlParams   = new URLSearchParams(window.location.search);
    const searchQuery = urlParams.get('search');

    if (searchQuery && searchQuery.trim() !== '') {
        performSearch(searchQuery.trim());
    }

    function performSearch(query) {
        const searchLower = query.toLowerCase();
        const allCards    = document.querySelectorAll('.card[data-medicine-name]');
        let foundCards    = [];
        let totalFound    = 0;

        allCards.forEach(card => {
            card.classList.remove('search-highlight', 'hidden-by-search');
        });

        allCards.forEach(card => {
            const name = card.getAttribute('data-medicine-name');
            if (name && name.toLowerCase().includes(searchLower)) {
                card.classList.add('search-highlight');
                foundCards.push(card);
                totalFound++;
            } else {
                card.classList.add('hidden-by-search');
            }
        });

        const resultCount = document.getElementById('searchResultCount');
        if (resultCount) {
            if (totalFound > 0) {
                const branchSet = new Set();
                foundCards.forEach(card => {
                    const bc = card.closest('.branch-container');
                    if (bc) branchSet.add(bc.getAttribute('data-branch-name'));
                });
                resultCount.textContent = `Found ${totalFound} result${totalFound !== 1 ? 's' : ''} across ${branchSet.size} branch${branchSet.size !== 1 ? 'es' : ''}`;
            } else {
                resultCount.textContent = 'No results found';
            }
        }

        const noResultsMsg   = document.getElementById('noResultsMessage');
        const noResultsQuery = document.getElementById('noResultsQuery');

        if (totalFound === 0) {
            noResultsMsg.style.display = 'block';
            noResultsQuery.textContent = query;
            document.querySelectorAll('.branch-container').forEach(bc => bc.style.display = 'none');
        } else {
            noResultsMsg.style.display = 'none';
        }

        if (foundCards.length > 0) {
            setTimeout(() => {
                const first = foundCards[0];
                const bc    = first.closest('.branch-container');
                if (bc) {
                    bc.style.display = 'block';
                    const allBCs   = document.querySelectorAll('.branch-container');
                    const idx      = Array.from(allBCs).indexOf(bc);
                    const checkbox = document.getElementById(`branch${idx + 1}-toggle`);
                    if (checkbox) checkbox.checked = true;
                }
                window.scrollTo({ top: first.getBoundingClientRect().top + window.pageYOffset - 120, behavior: 'smooth' });
                first.style.animation = 'none';
                setTimeout(() => { first.style.animation = 'highlight-pulse 1.5s ease-in-out'; }, 10);
            }, 100);
        }
    }

    /* ══════════════════════════════════════════════
       GLOBAL BRANCH FILTERING
    ══════════════════════════════════════════════ */
    function setupGlobalBranchFiltering() {
        const applyFilterBtn     = document.getElementById('applyBranchFilter');
        const toggleAllBtn       = document.getElementById('toggleAllBtn');
        const globalFilterStatus = document.getElementById('globalFilterStatus');
        const branchContainers   = document.querySelectorAll('.branch-container');
        const branchToggles      = document.querySelectorAll('.branch-toggle input[type="checkbox"]');
        const branchLabels       = document.querySelectorAll('.branch-toggle');

        if (!applyFilterBtn || !toggleAllBtn || !globalFilterStatus || !branchContainers.length) return;

        let currentStates = Array.from(branchToggles).map(t => t.checked);

        function updateFilterStatusText() {
            const hasChanges = Array.from(branchToggles).some((t, i) => t.checked !== currentStates[i]);

            if (hasChanges) {
                globalFilterStatus.textContent = 'Click Apply Filter to update view';
                applyFilterBtn.classList.remove('active');
                applyFilterBtn.textContent = 'Apply Filter';
            } else {
                const visCount = currentStates.filter(Boolean).length;
                const total    = branchToggles.length;
                if      (visCount === 0)    globalFilterStatus.textContent = 'No branches visible';
                else if (visCount === total) globalFilterStatus.textContent = 'Showing all branches';
                else                        globalFilterStatus.textContent = `Showing ${visCount} of ${total} branches`;
                applyFilterBtn.classList.add('active');
                applyFilterBtn.textContent = 'Filter Applied';
            }
        }

        function applyBranchFilter() {
            branchToggles.forEach((t, i) => { currentStates[i] = t.checked; });

            const hasSearch = new URLSearchParams(window.location.search).get('search');
            if (!hasSearch) {
                branchContainers.forEach((c, i) => { c.style.display = currentStates[i] ? 'block' : 'none'; });
            }

            branchLabels.forEach((lbl, i) => lbl.classList.toggle('active', currentStates[i]));

            const allVis  = currentStates.every(Boolean);
            const noneVis = currentStates.every(s => !s);
            toggleAllBtn.textContent = noneVis ? 'Show All' : allVis ? 'Hide All' : 'Show All';

            updateFilterStatusText();
        }

        function toggleAllBranches() {
            const anyVisible = currentStates.some(Boolean);
            branchToggles.forEach(t => { t.checked = !anyVisible; });
            applyBranchFilter();
        }

        branchToggles.forEach(t => t.addEventListener('change', updateFilterStatusText));
        applyFilterBtn.addEventListener('click', applyBranchFilter);
        toggleAllBtn.addEventListener('click', toggleAllBranches);

        applyBranchFilter();
    }

    setupGlobalBranchFiltering();

});

/* ══════════════════════════════════════════════════════════════
   SCANNER GLOBAL FUNCTIONS
══════════════════════════════════════════════════════════════ */

function szOpen() {
    document.getElementById('scannerOverlay').classList.add('active');
}

function szClose() {
    if (typeof releaseWebcamToUploadMode === 'function') {
        releaseWebcamToUploadMode();
    }
    document.getElementById('scannerOverlay').classList.remove('active');
}

function szReserve() {
    const name = window._szDetectedName;
    if (!name) {
        szClose();
        return;
    }
    
    const searchTerm = name.split(/\s+/).slice(0, 3).join(' ');
    const searchInput = document.getElementById('searchInput');
    
    if (searchInput) {
        searchInput.value = searchTerm;
        szClose();
        document.getElementById('searchForm').submit();
    }
}

document.getElementById('scannerOverlay').addEventListener('click', function(e) {
    if (e.target === this) szClose();
});

function slOpen() {
    document.getElementById('scanLabelOverlay').classList.add('active');
}
function slClose() {
    document.getElementById('scanLabelOverlay').classList.remove('active');
}
function slReserve() {
    const name = window._slDetectedName;
    if (!name) { slClose(); return; }
    const searchTerm = name.split(/\s+/).slice(0, 3).join(' ');
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.value = searchTerm;
        slClose();
        document.getElementById('searchForm').submit();
    }
}
document.getElementById('scanLabelOverlay').addEventListener('click', function(e) {
    if (e.target === this) slClose();
});
    </script>
  <!-- ══════════════════════════════════════════════
       GUIDE MODAL + ZOOM JAVASCRIPT
  ══════════════════════════════════════════════ -->
  <script>
  (function () {
    /* ── Zoom ── */
    let currentZoom = 100;
    const minZoom   = 80;
    const maxZoom   = 150;
    const zoomStep  = 10;
    const pageWrapper = document.querySelector('.page-wrapper');
    const zoomInBtn   = document.getElementById('medZoomInBtn');
    const zoomOutBtn  = document.getElementById('medZoomOutBtn');

    function updateZoom() {
      if (!pageWrapper) return;
      pageWrapper.style.transform = 'scale(' + (currentZoom / 100) + ')';
      zoomInBtn.disabled  = currentZoom >= maxZoom;
      zoomOutBtn.disabled = currentZoom <= minZoom;
    }

    zoomInBtn.addEventListener('click', function() {
      if (currentZoom < maxZoom) { currentZoom += zoomStep; updateZoom(); }
    });

    zoomOutBtn.addEventListener('click', function() {
      if (currentZoom > minZoom) { currentZoom -= zoomStep; updateZoom(); }
    });

    // Ctrl/Cmd + / - / 0 keyboard shortcuts
    document.addEventListener('keydown', function(e) {
      if (e.ctrlKey || e.metaKey) {
        if (e.key === '+' || e.key === '=') { e.preventDefault(); zoomInBtn.click(); }
        else if (e.key === '-')             { e.preventDefault(); zoomOutBtn.click(); }
        else if (e.key === '0')             { e.preventDefault(); currentZoom = 100; updateZoom(); }
      }
    });

    updateZoom();

    /* ── Guide modal elements ── */
    const overlay    = document.getElementById('guideModalOverlay');
    const guideBtn   = document.getElementById('medGuideBtn');
    const closeBtn   = document.getElementById('guideModalClose');
    const modalBody  = document.getElementById('guideModalBody');

    /* ── Open / Close ── */
    function openGuide() { overlay.classList.add('active'); }
    function closeGuide() {
      overlay.classList.remove('active');
      gmStopTTS();
    }

    guideBtn.addEventListener('click', openGuide);
    closeBtn.addEventListener('click', closeGuide);
    overlay.addEventListener('click', function(e) {
      if (e.target === overlay) closeGuide();
    });
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && overlay.classList.contains('active')) closeGuide();
    });

    /* ── Tab switching ── */
    document.querySelectorAll('.gm-tab-btn').forEach(function(btn) {
      btn.addEventListener('click', function() {
        gmStopTTS();
        document.querySelectorAll('.gm-tab-btn').forEach(function(b) { b.classList.remove('active'); });
        document.querySelectorAll('.gm-tab-content').forEach(function(c) { c.classList.remove('active'); });
        btn.classList.add('active');
        const tab = document.getElementById(btn.getAttribute('data-gmtab'));
        if (tab) {
          tab.classList.add('active');
          modalBody.scrollTop = 0;
        }
      });
    });

    /* ── TTS core ── */
    let gmCurrentStepBtn = null;

    window.speechSynthesis.onvoiceschanged = function() { window.speechSynthesis.getVoices(); };

    function gmSpeak(text, onEnd) {
      window.speechSynthesis.cancel();
      const utter = new SpeechSynthesisUtterance(text);
      utter.lang  = 'en-PH';
      utter.rate  = 1;
      const voices    = window.speechSynthesis.getVoices();
      const rosaVoice = voices.find(function(v) { return v.name === 'Microsoft Rosa Online (Natural) - English (Philippines)'; });
      if (rosaVoice) utter.voice = rosaVoice;
      if (onEnd) utter.onend = onEnd;
      window.speechSynthesis.speak(utter);
    }

    function gmStopTTS() {
      window.speechSynthesis.cancel();
      if (gmCurrentStepBtn) { gmResetStepBtn(gmCurrentStepBtn); gmCurrentStepBtn = null; }
    }

    function gmSetReading(btn) {
      if (gmCurrentStepBtn && gmCurrentStepBtn !== btn) gmResetStepBtn(gmCurrentStepBtn);
      gmCurrentStepBtn = btn;
      btn.classList.add('reading');
      btn.innerHTML = '<span class="gm-tts-dot"></span> Reading...';
    }

    function gmResetStepBtn(btn) {
      btn.classList.remove('reading');
      btn.innerHTML = '<i class="bi bi-volume-up"></i> Read step';
    }

    /* ── Per-step Read buttons ── */
    document.querySelectorAll('.gm-step-read-btn').forEach(function(btn) {
      btn.addEventListener('click', function() {
        const step = btn.closest('.gm-step');
        const text = step ? step.getAttribute('data-step') : '';
        if (!text) return;
        if (btn.classList.contains('reading')) { gmStopTTS(); return; }
        gmSetReading(btn);
        gmSpeak(text, function() { gmResetStepBtn(btn); gmCurrentStepBtn = null; });
      });
    });

    /* ── "Read guide" buttons (one per tab) ── */
    document.querySelectorAll('.gm-tts-read-btn[data-gmread]').forEach(function(readBtn) {
      readBtn.addEventListener('click', function() {
        const tabId = readBtn.getAttribute('data-gmread');
        const tabEl = document.getElementById(tabId);
        if (!tabEl) return;
        const steps = tabEl.querySelectorAll('.gm-step');
        if (!steps.length) return;

        let index = 0;
        function readNext() {
          if (index >= steps.length) {
            if (gmCurrentStepBtn) { gmResetStepBtn(gmCurrentStepBtn); gmCurrentStepBtn = null; }
            return;
          }
          const step    = steps[index];
          const stepBtn = step.querySelector('.gm-step-read-btn');
          const text    = step.getAttribute('data-step');
          if (stepBtn) gmSetReading(stepBtn);
          index++;
          gmSpeak(text, readNext);
        }
        readNext();
      });
    });

    /* ── Stop buttons ── */
    document.querySelectorAll('[data-gmstop]').forEach(function(btn) {
      btn.addEventListener('click', gmStopTTS);
    });

  })();
  </script>

  <!-- SCAN LABEL MODAL -->
<div class="scanner-overlay" id="scanLabelOverlay">
    <div class="scanner-modal">
        <div class="scanner-body">
            <!-- LEFT: UPLOAD / CAMERA MODE -->
            <div class="upload-col">
                <div class="mode-toggle">
                    <button class="mode-btn active" id="slUploadModeBtn">
                        <i class='bx bx-upload'></i> Upload
                    </button>
                </div>

                <div class="upload-zone" id="slUploadZone">
                    <img class="preview-img" id="slPreviewImg" src="" alt="Preview">
                    <div class="upload-placeholder">
                        <div class="upload-icon-wrap">
                            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/>
                            </svg>
                        </div>
                        <h5>Click to Upload Label Image</h5>
                        <p>or drag and drop your file here</p>
                        <div class="button-group">
                            <button class="choose-btn" type="button" onclick="event.stopPropagation(); if(!document.getElementById('slUploadZone').classList.contains('has-image')) document.getElementById('slFileInput').click()">CHOOSE FILE</button>
                            <button class="camera-btn" type="button" id="slCameraBtn" title="Use camera">
                                <i class='bx bx-camera'></i>
                            </button>
                        </div>
                        <span class="upload-file-note">Supports: JPG, PNG (Max: 10MB)</span>
                    </div>
                    <div class="scanning-overlay" id="slScanningOverlay">
                        <div class="scan-spinner"></div>
                        <span id="slSpinnerText">Analyzing…</span>
                    </div>
                </div>

                <div id="slCameraFeedContainer" class="camera-feed-container">
                    <video id="slCameraFeed" playsinline autoplay></video>
                    <canvas id="slCameraCanvas" style="display:none;"></canvas>
                    <div class="camera-overlay">
                        <div class="reticle"><div></div></div>
                        <p>Center label in view</p>
                    </div>
                    <button class="capture-btn" id="slCaptureBtn">
                        <i class='bx bx-circle'></i> CAPTURE
                    </button>
                </div>

                <input type="file" id="slFileInput" accept="image/*" style="display:none;">
            </div>

            <!-- RIGHT: RESULTS (placeholder — update after you provide the layout) -->
            <div class="result-col" id="slResultCol">
                <div class="result-idle" id="slResultIdle">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9 3H5a2 2 0 0 0-2 2v4m6-6h10a2 2 0 0 1 2 2v4M9 3v18m0 0h10a2 2 0 0 0 2-2V9M9 21H5a2 2 0 0 1-2-2V9m0 0h18"/>
                    </svg>
                    <p>Upload or scan a label<br>image to see details here</p>
                </div>
                <div class="result-header" id="slResultHeader" style="display:none;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Label Detected</span>
                </div>
                <div id="slResultContent" style="display:none;">
                    <div class="check-name-row">
                        <div class="check-icon">
                            <svg viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg>
                        </div>
                        <div style="flex:1;">
                            <p class="medicine-name-big" id="slMedName">—</p>
                            <p class="medicine-subtitle" id="slMedSub">Detected from label scan</p>
                        </div>
                    </div>
                    <div class="confidence-row">
                        <div class="conf-label">
                            <span>Detection Confidence</span>
                            <span id="slConfPct">—</span>
                        </div>
                        <div class="conf-bar-bg">
                            <div class="conf-bar" id="slConfBar" style="width:0%"></div>
                        </div>
                    </div>
                    <div class="info-grid-2x2">
                        <div class="info-chip">
                            <span class="chip-label">Strength</span>
                            <span class="chip-value" id="slStrength">—</span>
                        </div>
                        <div class="info-chip">
                            <span class="chip-label">Form</span>
                            <span class="chip-value" id="slForm">—</span>
                        </div>
                        <div class="info-chip">
                            <span class="chip-label">Type</span>
                            <span class="chip-value" id="slType">—</span>
                        </div>
                        <div class="info-chip">
                            <span class="chip-label">Active Ingredient</span>
                            <span class="chip-value" id="slIngredient" style="font-size:12px;">—</span>
                        </div>
                    </div>
                    <!-- Dosage / Intake / Indication -->
                    <div class="info-grid-2x2" style="grid-template-columns:1fr;">
                        <div class="info-chip">
                            <span class="chip-label">Dosage</span>
                            <span class="chip-value" id="slDosage" style="font-size:13px;">—</span>
                        </div>
                        <div class="info-chip">
                            <span class="chip-label">How to Take (Intake)</span>
                            <span class="chip-value" id="slIntake" style="font-size:13px;">—</span>
                        </div>
                        <div class="info-chip">
                            <span class="chip-label">Indication / Use</span>
                            <span class="chip-value" id="slIndication" style="font-size:13px;">—</span>
                        </div>
                    </div>
                    <!-- Raw OCR toggle -->
                    <details style="margin-top:10px; font-size:12px; color:#64748b;">
                        <summary style="cursor:pointer;">Show raw OCR text</summary>
                        <pre id="slRawText" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:8px;font-size:11px;white-space:pre-wrap;max-height:120px;overflow-y:auto;margin-top:6px;"></pre>
                    </details>
                </div>
            </div>
        </div>

        <div class="scanner-footer">
            <button class="btn-reserve" id="slBtnReserve" onclick="slReserve()" disabled>DONE</button>
        </div>
    </div>
</div>
<script src="source/scan-label.js?v=1"></script>
<script>
/* ── Populate new OCR fields from scan-label results ── */
(function () {
    // Override or extend the global callback that scan-label.js calls when OCR succeeds.
    // We patch window.slPopulateResult if scan-label.js uses it, otherwise we
    // use a MutationObserver to watch slResultContent becoming visible and read
    // the data from window._slOcrData (set by scan-label.js).

    function fillNewFields(data) {
        if (!data) return;
        var v = function(x) { return (x && x !== '—' && x.trim() !== '') ? x : '—'; };

        var dosage = document.getElementById('slDosage');
        var intake = document.getElementById('slIntake');
        var indic  = document.getElementById('slIndication');
        var raw    = document.getElementById('slRawText');

        if (dosage) dosage.textContent = v(data.dosage);
        if (intake) intake.textContent = v(data.intake);
        if (indic)  indic.textContent  = v(data.indication);
        if (raw)    raw.textContent    = data.all_text || '';
    }

    // If scan-label.js exposes a hook, use it directly.
    // Otherwise watch for the result panel to appear.
    var observer = new MutationObserver(function () {
        var content = document.getElementById('slResultContent');
        if (content && content.style.display !== 'none') {
            fillNewFields(window._slOcrData);
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        var content = document.getElementById('slResultContent');
        if (content) {
            observer.observe(content, { attributes: true, attributeFilter: ['style'] });
        }
    });
})();
</script>

</body>
</html>