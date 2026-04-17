<?php
session_start();

if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION['role'] === 'manager1' || $_SESSION['role'] === 'manager2') {
    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PharmAssist</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,200..800&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Tinos:wght@400;700&display=swap" rel="stylesheet">

  <style>
    body {
      font-family: 'Tinos', serif;
      background-color: #f6faff;
      margin: 0;
      padding: 20px;
      text-align: center;
    }

    .medicine-card {
      border: 1px solid #ccc;
      padding: 15px;
      border-radius: 10px;
      margin-bottom: 15px;
      width: 250px;
      display: inline-block;
      vertical-align: top;
      background: #fff;
      text-align: center;
      box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    }
    .medicine-card h3 { margin: 0 0 5px; }
    .medicine-card p { margin: 0 0 10px; color: #444; }
    .medicine-card button {
      background: #7393A7;
      color: #fff;
      padding: 8px 15px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
    }
    .medicine-card button:hover {
      background: #5a7a8e;
    }

    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0,0,0,0.6);
      overflow: auto;
    }
    .modal-content {
      background: #fff;
      margin: 5% auto;
      padding: 25px;
      border-radius: 12px;
      width: 90%;
      max-width: 400px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.2);
      position: relative;
      animation: fadeIn 0.3s ease;
    }
    .close {
      color: #aaa;
      position: absolute;
      top: 10px;
      right: 15px;
      font-size: 28px;
      font-weight: bold;
      cursor: pointer;
      line-height: 1;
    }
    .close:hover { color: #333; }

    .preview-box {
      margin-top: 15px;
      padding: 12px;
      border-radius: 8px;
      border: 1px solid #2a6fdb;
      background: #e8f0fb;
      text-align: left;
      font-size: 14px;
      line-height: 1.6;
    }

    .modal-content h2 {
      text-align: center;
      color: #7393A7;
      margin: 0 0 15px 0;
      font-family: "Bricolage Grotesque", sans-serif;
    }
    label { 
      display: block; 
      margin-top: 10px; 
      color: #333; 
      font-weight: bold; 
      text-align: left;
      font-size: 14px;
    }
    input, textarea {
      width: calc(100% - 20px);
      padding: 10px;
      margin-top: 5px;
      border: 1px solid #ccc;
      border-radius: 6px;
      font-size: 14px;
      font-family: 'Tinos', serif;
      box-sizing: border-box;
    }
    textarea { 
      resize: vertical;
      min-height: 60px;
    }

    input[type="file"] {
      width: 100%;
      padding: 8px 0;
      font-size: 13px;
    }

    .prescription-upload-wrap {
      display: none;
      margin-top: 12px;
      padding: 12px;
      border-radius: 8px;
      background: #fff8e6;
      border: 1px solid #e6d4a8;
      text-align: left;
    }

    .prescription-upload-wrap.visible {
      display: block;
    }

    .prescription-upload-wrap .hint {
      font-size: 12px;
      color: #666;
      font-weight: normal;
      margin-top: 4px;
    }

    .id-upload-wrap {
      margin-top: 12px;
      padding: 12px;
      border-radius: 8px;
      background: #f0fdf4;
      border: 1px solid #86efac;
      text-align: left;
    }

    .id-upload-wrap .hint {
      font-size: 12px;
      color: #166534;
      font-weight: normal;
      margin-top: 4px;
    }

    .privacy-note {
      font-size: 12px;
      color: #15803d;
      margin: 10px 0 0 0;
      line-height: 1.45;
      font-weight: normal;
    }

    .order-unit-fieldset {
      border: 1px solid #d0d7de;
      border-radius: 8px;
      padding: 12px 14px 14px;
      margin: 12px 0 0 0;
      text-align: left;
    }

    .order-unit-fieldset legend {
      font-size: 13px;
      font-weight: 600;
      color: #333;
      padding: 0 6px;
    }

    .order-unit-label {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-top: 8px;
      font-weight: normal;
      font-size: 14px;
      cursor: pointer;
    }

    .order-unit-label input {
      width: auto;
      margin: 0;
    }

    .order-count-hint {
      font-size: 12px;
      color: #64748b;
      margin: 4px 0 0 0;
      line-height: 1.4;
    }

    .submit-btn {
      margin-top: 20px;
      width: 100%;
      background: #7393A7;
      color: #fff;
      font-size: 16px;
      font-weight: bold;
      border: none;
      border-radius: 6px;
      padding: 12px;
      cursor: pointer;
      transition: 0.3s;
      font-family: "Bricolage Grotesque", sans-serif;
    }
    .submit-btn:hover { background: #4b6fa5; }
    .submit-btn:disabled { 
      background: #ccc; 
      cursor: not-allowed;
      opacity: 0.6;
    }

    .popup {
      display: none;
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      background: #fff;
      border-radius: 10px;
      padding: 30px;
      width: 90%;
      max-width: 350px;
      text-align: center;
      box-shadow: 0 6px 18px rgba(0,0,0,0.25);
      z-index: 2000;
    }
    .popup.success { border: 2px solid #28a745; }
    .popup.error { border: 2px solid #dc3545; }
    .popup .icon { font-size: 48px; margin-bottom: 10px; }
    .popup .check { color: #28a745; }
    .popup .cross { color: #dc3545; }
    .popup button {
      margin-top: 15px; 
      padding: 10px 24px; 
      border: none; 
      border-radius: 6px; 
      cursor: pointer;
      font-family: "Bricolage Grotesque", sans-serif;
      font-weight: bold;
      font-size: 14px;
    }
    .popup.success button { background: #28a745; color: white; }
    .popup.error button { background: #dc3545; color: white; }

    @keyframes fadeIn {
      from {opacity: 0; transform: translateY(-20px);}
      to {opacity: 1; transform: translateY(0);}
    }
  </style>
</head>
<body>
  <!-- Example reserve button: add data-units-per-box when known (defaults to 100). -->
  <!--
  <button type="button" class="reserve-btn" data-name="Medicine" data-price="50"
    data-prescription-required="no" data-units-per-box="100">Reserve</button>
  -->
  <!-- Modal -->
  <div id="reservationModal" class="modal">
    <div class="modal-content">
      <span class="close" id="closeModal">&times;</span>
      <h2>Reserve Your Medicine</h2>
      <form id="reservationForm" method="POST" enctype="multipart/form-data">
        <label for="first_name">First Name</label>
        <input type="text" id="first_name" name="first_name" required autocomplete="given-name">

        <label for="last_name">Last Name</label>
        <input type="text" id="last_name" name="last_name" required autocomplete="family-name">

        <label for="contact">Contact Number</label>
        <input type="tel" id="contact" name="contact" required pattern="[0-9]{10,11}">

        <input type="hidden" id="medicine" name="medicine">
        <input type="hidden" id="base_unit_price" name="base_unit_price" value="">

        <fieldset class="order-unit-fieldset">
          <legend>Reserve by</legend>
          <label class="order-unit-label"><input type="radio" name="order_unit" value="piece" checked> Per piece (per tablet / unit)</label>
          <label class="order-unit-label"><input type="radio" name="order_unit" value="ten"> Per 10 pieces</label>
          <label class="order-unit-label"><input type="radio" name="order_unit" value="box"> Per box</label>
        </fieldset>

        <label for="order_count">How many?</label>
        <input type="number" id="order_count" value="1" min="1" step="1" required>
        <p class="order-count-hint" id="orderCountHint"></p>
        <input type="hidden" id="quantity" name="quantity" value="1">

        <label for="notes">Additional Notes (Optional)</label>
        <textarea id="notes" name="notes" placeholder="Any special instructions..."></textarea>

        <div id="prescriptionUploadWrap" class="prescription-upload-wrap" aria-hidden="true">
          <label for="prescription">Upload prescription <span style="color:#b45309;">*</span></label>
          <input type="file" id="prescription" name="prescription" accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf">
          <p class="hint">Required for prescribed medicines. JPG, PNG, or PDF.</p>
        </div>

        <div class="id-upload-wrap">
          <label for="sc_pwd_id">Upload Senior Citizen ID or PWD ID <span style="color:#b45309;">*</span></label>
          <input type="file" id="sc_pwd_id" name="sc_pwd_id" required accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf">
          <p class="hint">Required for the 20% discount. Clear photo or scan. JPG, PNG, or PDF.</p>
          <p class="privacy-note">Your privacy matters: we use this file only to confirm discount eligibility. It is stored securely and handled in line with applicable data-protection practices.</p>
        </div>

        <div class="preview-box" id="previewBox"></div>

        <button type="submit" class="submit-btn" id="submitBtn">Submit Reservation</button>
      </form>
    </div>
  </div>

  <!-- Success Popup -->
  <div class="popup success" id="successPopup">
    <div class="icon check">✓</div>
    <h3 style="margin: 10px 0; color: #28a745;">Reservation Submitted!</h3>
    <p style="margin: 10px 0; color: #666;">Your reservation is waiting to be approved.</p>
    <p style="margin: 10px 0; color: #666;"><b>Reservation code</b> will be generated once confirmed.</p>
    <button onclick="closeSuccessPopup()">OK</button>
  </div>

  <!-- Error Popup -->
  <div class="popup error" id="errorPopup">
    <div class="icon cross">✕</div>
    <h3 style="margin: 10px 0; color: #dc3545;">Reservation Failed</h3>
    <p id="errorMessage" style="margin: 10px 0; color: #666;"></p>
    <button onclick="closeErrorPopup()">Try Again</button>
  </div>

  <script>
    const modal = document.getElementById("reservationModal");
    const closeModalBtn = document.getElementById("closeModal");
    const previewBox = document.getElementById("previewBox");
    const successPopup = document.getElementById("successPopup");
    const errorPopup = document.getElementById("errorPopup");
    const form = document.getElementById("reservationForm");
    const submitBtn = document.getElementById("submitBtn");

    const VAT_INCLUSIVE_DIVISOR = 1.12;
    const SC_PWD_NET_DISCOUNT = 0.2;

    let medName = "";
    let medPrice = 0;
    let medPrescriptionRequired = false;
    let medUnitsPerBox = 100;

    const prescriptionWrap = document.getElementById("prescriptionUploadWrap");
    const prescriptionInput = document.getElementById("prescription");
    const scPwdInput = document.getElementById("sc_pwd_id");
    const orderCountInput = document.getElementById("order_count");
    const orderCountHint = document.getElementById("orderCountHint");
    const quantityHidden = document.getElementById("quantity");

    function getOrderUnit() {
      const r = document.querySelector('#reservationModal input[name="order_unit"]:checked');
      return r ? r.value : "piece";
    }

    function updateOrderCountHint() {
      const u = getOrderUnit();
      const count = parseInt(orderCountInput.value, 10) || 1;
      if (u === "piece") {
        orderCountHint.textContent = "Number of individual tablets or units to reserve.";
      } else if (u === "ten") {
        orderCountHint.textContent = "Number of 10-piece packs. Each pack = 10 units (total " + (count * 10) + " units).";
      } else {
        orderCountHint.textContent = "1 box = " + medUnitsPerBox + " units. Total units: " + (count * medUnitsPerBox) + ".";
      }
    }

    function syncBaseUnitPrice() {
      const el = document.getElementById("base_unit_price");
      if (el) el.value = medPrice > 0 ? String(medPrice) : "";
    }

    function syncReservationQuantity() {
      let count = parseInt(orderCountInput.value, 10);
      if (isNaN(count) || count < 1) count = 1;
      orderCountInput.value = count;
      let pieces = count;
      const u = getOrderUnit();
      if (u === "ten") pieces = count * 10;
      else if (u === "box") pieces = count * medUnitsPerBox;
      quantityHidden.value = String(pieces);
      syncBaseUnitPrice();
      updateOrderCountHint();
      updatePreview();
    }

    function setPrescriptionUploadForMedicine() {
      if (medPrescriptionRequired) {
        prescriptionWrap.classList.add("visible");
        prescriptionWrap.setAttribute("aria-hidden", "false");
        prescriptionInput.setAttribute("required", "required");
      } else {
        prescriptionWrap.classList.remove("visible");
        prescriptionWrap.setAttribute("aria-hidden", "true");
        prescriptionInput.removeAttribute("required");
        prescriptionInput.value = "";
      }
    }

    // Initialize reserve buttons (use data-prescription-required="yes" | "no" on each .reserve-btn)
    function initReserveButtons() {
      document.querySelectorAll(".reserve-btn").forEach(button => {
        button.addEventListener("click", function() {
          medName = this.getAttribute("data-name");
          medPrice = parseFloat(this.getAttribute("data-price"));
          const pr = (this.getAttribute("data-prescription-required") || "no").toLowerCase();
          medPrescriptionRequired = pr === "yes" || pr === "1" || pr === "true";
          medUnitsPerBox = parseInt(this.getAttribute("data-units-per-box"), 10);
          if (isNaN(medUnitsPerBox) || medUnitsPerBox < 1) medUnitsPerBox = 100;

          document.getElementById("medicine").value = medName;
          syncBaseUnitPrice();
          const pieceRadio = document.querySelector('#reservationModal input[name="order_unit"][value="piece"]');
          if (pieceRadio) pieceRadio.checked = true;
          orderCountInput.value = 1;

          // Reset form fields
          document.getElementById("first_name").value = "";
          document.getElementById("last_name").value = "";
          document.getElementById("contact").value = "";
          document.getElementById("notes").value = "";
          prescriptionInput.value = "";
          scPwdInput.value = "";
          setPrescriptionUploadForMedicine();

          syncReservationQuantity();
          modal.style.display = "block";
        });
      });
    }

    // Initialize on page load
    initReserveButtons();

    // Close modal handlers
    closeModalBtn.onclick = function() { 
      modal.style.display = "none"; 
    };

    window.onclick = function(event) { 
      if (event.target === modal) {
        modal.style.display = "none";
      }
    };

    // Update preview
    function updatePreview() {
      const qty = parseInt(quantityHidden.value, 10) || 1;
      const count = parseInt(orderCountInput.value, 10) || 1;
      const u = getOrderUnit();
      const grossSubtotal = medPrice * qty;
      const netAfterVat = grossSubtotal / VAT_INCLUSIVE_DIVISOR;
      const discountOnNet = netAfterVat * SC_PWD_NET_DISCOUNT;
      const amountToPay = netAfterVat * (1 - SC_PWD_NET_DISCOUNT);
      const equivUnit = medPrice / VAT_INCLUSIVE_DIVISOR * (1 - SC_PWD_NET_DISCOUNT);
      let orderLine = "";
      if (u === "piece") {
        orderLine = `<b>Order:</b> ${count} piece(s) · <b>Total units:</b> ${qty}`;
      } else if (u === "ten") {
        orderLine = `<b>Order:</b> ${count} × 10 pieces · <b>Total units:</b> ${qty}`;
      } else {
        orderLine = `<b>Order:</b> ${count} box(es) × ${medUnitsPerBox} units · <b>Total units:</b> ${qty}`;
      }
      const rxLine = medPrescriptionRequired
        ? "<br><b>Prescription:</b> <span style=\"color:#b45309;\">Required — upload your prescription before submitting.</span>"
        : "<br><b>Prescription:</b> Not required for this medicine.";
      previewBox.style.display = "block";
      previewBox.innerHTML = `
        <b>You are reserving:</b> ${medName}<br>
        <span style="font-size:12px;color:#64748b;">List prices include 12% VAT. SC/PWD: VAT removed from the line total, then 20% off that net amount.</span><br>
        <b>List unit price (VAT-inclusive):</b> ₱${medPrice.toFixed(2)} each<br>
        <b>Equiv. unit after VAT out + SC/PWD:</b> ₱${equivUnit.toFixed(2)} each<br>
        ${orderLine}<br>
        <b>Subtotal (VAT-inclusive):</b> ₱${grossSubtotal.toFixed(2)}<br>
        <b>Net after 12% VAT removed:</b> ₱${netAfterVat.toFixed(2)}<br>
        <b>20% SC/PWD discount (on net):</b> −₱${discountOnNet.toFixed(2)}<br>
        <b>Amount to pay:</b> ₱${amountToPay.toFixed(2)}${rxLine}<br>
        <b>Reservation will expire in:</b> 72 hours<br>
        <i style="font-size: 12px; color: #666;">**Please claim your reserved medicine before expiration.**</i>
      `;
    }

    orderCountInput.addEventListener("input", syncReservationQuantity);
    document.querySelectorAll('#reservationModal input[name="order_unit"]').forEach(function (el) {
      el.addEventListener("change", syncReservationQuantity);
    });

    // Handle form submission
    form.addEventListener("submit", function(e) {
      e.preventDefault();

      if (medPrescriptionRequired && (!prescriptionInput.files || prescriptionInput.files.length === 0)) {
        document.getElementById("errorMessage").textContent = "Please upload a prescription image or PDF for this medicine.";
        errorPopup.style.display = "block";
        return;
      }

      if (!scPwdInput.files || scPwdInput.files.length === 0) {
        document.getElementById("errorMessage").textContent = "Please upload your Senior Citizen ID or PWD ID (required for the 20% discount).";
        errorPopup.style.display = "block";
        return;
      }

      syncReservationQuantity();
      
      submitBtn.disabled = true;
      submitBtn.textContent = "Submitting...";

      const formData = new FormData(form);

      fetch("submit_reservation.php", {
        method: "POST",
        body: formData
      })
      .then(response => {
        if (!response.ok) {
          throw new Error("Network response was not ok");
        }
        return response.json();
      })
      .then(result => {
        if (result.success) {
          modal.style.display = "none";
          successPopup.style.display = "block";
          form.reset();
        } else {
          document.getElementById("errorMessage").textContent = result.message || "An error occurred. Please try again.";
          errorPopup.style.display = "block";
        }
      })
      .catch(error => {
        console.error("Error:", error);
        document.getElementById("errorMessage").textContent = "Network error. Please check your connection and try again.";
        errorPopup.style.display = "block";
      })
      .finally(() => {
        submitBtn.disabled = false;
        submitBtn.textContent = "Submit Reservation";
      });
    });

    function closeSuccessPopup() {
      successPopup.style.display = "none";
      location.reload();
    }

    function closeErrorPopup() {
      errorPopup.style.display = "none";
    }
  </script>

</body>
</html>