/**
 * ════════════════════════════════════════════════════════════════
 *  SCAN LABEL MODULE - Tesseract.js OCR Integration (IMPROVED)
 * ════════════════════════════════════════════════════════════════
 *
 * IMPROVEMENTS IN THIS VERSION:
 *  1. Enhanced image pre-processing: sharpen, adaptive contrast, upscale
 *  2. Multi-pass OCR with PSM 3, 6, and 11 — picks highest confidence
 *  3. Expanded known-brands list (includes Oxcarbazepine, Carbox, etc.)
 *  4. Warning/noise text blocklist prevents "L IS BROKEN"-style false names
 *  5. Stronger medicine-name heuristics: prefer title-case single drug words
 *  6. Claude AI fallback: sends raw OCR text to Anthropic API for smart parsing
 *  7. All original fixes retained (FIX 1–6)
 */

(function () {
  'use strict';

  // ─────────────────────────────────────────────────────────
  // STATE MANAGEMENT
  // ─────────────────────────────────────────────────────────
  let currentMode   = 'upload'; // 'upload' | 'camera'
  let webcamStream  = null;
  let tesseractWorker = null;
  let isProcessing  = false;

  window._slDetectedName = null;
  window._slOcrData = {
    medicine_name: '', strength: '', form: '', type: '',
    ingredient: '', dosage: '', intake: '', indication: '',
    all_text: '', confidence: 0
  };

  // ─────────────────────────────────────────────────────────
  // DOM ELEMENTS CACHING
  // ─────────────────────────────────────────────────────────
  const slUploadModeBtn       = document.getElementById('slUploadModeBtn');
  const slCameraModeBtn       = document.getElementById('slCameraModeBtn');
  const slUploadZone          = document.getElementById('slUploadZone');
  const slCameraFeedContainer = document.getElementById('slCameraFeedContainer');
  const slCameraFeed          = document.getElementById('slCameraFeed');
  const slCameraCanvas        = document.getElementById('slCameraCanvas');
  const slCaptureBtn          = document.getElementById('slCaptureBtn');
  const slFileInput           = document.getElementById('slFileInput');
  const slPreviewImg          = document.getElementById('slPreviewImg');
  const slScanningOverlay     = document.getElementById('slScanningOverlay');
  const slSpinnerText         = document.getElementById('slSpinnerText');

  const slResultIdle    = document.getElementById('slResultIdle');
  const slResultHeader  = document.getElementById('slResultHeader');
  const slResultContent = document.getElementById('slResultContent');
  const slMedName       = document.getElementById('slMedName');
  const slMedSub        = document.getElementById('slMedSub');
  const slConfPct       = document.getElementById('slConfPct');
  const slConfBar       = document.getElementById('slConfBar');
  const slStrength      = document.getElementById('slStrength');
  const slForm          = document.getElementById('slForm');
  const slType          = document.getElementById('slType');
  const slIngredient    = document.getElementById('slIngredient');
  const slDosage        = document.getElementById('slDosage');
  const slIntake        = document.getElementById('slIntake');
  const slIndication    = document.getElementById('slIndication');
  const slRawText       = document.getElementById('slRawText');
  const slBtnReserve    = document.getElementById('slBtnReserve');

  // Guard: warn about missing elements
  const criticalElements = {
    slUploadModeBtn, slCameraModeBtn, slUploadZone, slCameraFeedContainer,
    slCameraFeed, slCameraCanvas, slCaptureBtn, slFileInput, slPreviewImg,
    slScanningOverlay, slSpinnerText, slResultIdle, slResultHeader,
    slResultContent, slMedName, slMedSub, slConfPct, slConfBar,
    slStrength, slForm, slType, slIngredient, slDosage, slIntake,
    slIndication, slRawText, slBtnReserve
  };
  for (const [name, el] of Object.entries(criticalElements)) {
    if (!el) console.error('[ScanLabel] Missing DOM element: #' + name);
  }

  // ─────────────────────────────────────────────────────────
  // TESSERACT.JS INITIALIZATION
  // ─────────────────────────────────────────────────────────
  async function initTesseract() {
    if (tesseractWorker) return tesseractWorker;
    try {
      console.log('[ScanLabel] Initializing Tesseract.js worker...');
      if (typeof Tesseract === 'undefined' || typeof Tesseract.createWorker !== 'function') {
        throw new Error('Tesseract.js not loaded. Ensure CDN <script> is present in medicines.php.');
      }
      tesseractWorker = await Tesseract.createWorker('eng', 1, {
        workerPath: 'https://cdn.jsdelivr.net/npm/tesseract.js@5.0.4/dist/worker.min.js',
        langPath:   'https://tessdata.projectnaptha.com/4.0.0',
        corePath:   'https://cdn.jsdelivr.net/npm/tesseract.js-core@5.0.0/tesseract-core.wasm.js',
        logger: function (m) {
          if (m.status === 'recognizing text' && slSpinnerText) {
            const pct = Math.round((m.progress || 0) * 100);
            slSpinnerText.textContent = 'Reading text\u2026 ' + pct + '%';
          }
        }
      });
      console.log('[ScanLabel] Tesseract.js worker initialized');
      return tesseractWorker;
    } catch (err) {
      console.error('[ScanLabel] Failed to initialize Tesseract.js:', err);
      alert('Error initializing OCR engine: ' + err.message);
      throw err;
    }
  }

  // ─────────────────────────────────────────────────────────
  // IMPROVED IMAGE PRE-PROCESSING
  // ─────────────────────────────────────────────────────────
  function preprocessImage(imageSource) {
    return new Promise(function (resolve) {
      const img = new Image();
      img.onload = function () {
        const canvas = document.createElement('canvas');

        // Step 1: Upscale if needed — target at least 1800px wide for medicine labels
        const MIN_WIDTH = 1800;
        const scale = img.width < MIN_WIDTH ? MIN_WIDTH / img.width : 1;
        canvas.width  = Math.round(img.width  * scale);
        canvas.height = Math.round(img.height * scale);

        const ctx = canvas.getContext('2d');

        // Step 2: Draw original image
        ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

        // Step 3: Apply sharpening via convolution kernel
        const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
        const sharpened = applySharpen(imageData);
        ctx.putImageData(sharpened, 0, 0);

        // Step 4: Convert to greyscale + enhanced contrast (adaptive stretch)
        const data2 = ctx.getImageData(0, 0, canvas.width, canvas.height);
        const d = data2.data;

        // Find min/max luminance for adaptive stretch
        let minL = 255, maxL = 0;
        for (let i = 0; i < d.length; i += 4) {
          const lum = 0.299 * d[i] + 0.587 * d[i + 1] + 0.114 * d[i + 2];
          if (lum < minL) minL = lum;
          if (lum > maxL) maxL = lum;
        }
        const rangeL = maxL - minL || 1;

        for (let i = 0; i < d.length; i += 4) {
          let gray = 0.299 * d[i] + 0.587 * d[i + 1] + 0.114 * d[i + 2];
          // Stretch to 0–255 then apply gamma for bright text
          gray = ((gray - minL) / rangeL) * 255;
          // Mild S-curve for better black/white separation
          gray = Math.min(255, Math.max(0, (gray - 128) * 1.4 + 128));
          d[i] = d[i + 1] = d[i + 2] = Math.round(gray);
        }
        ctx.putImageData(data2, 0, 0);

        resolve(canvas.toDataURL('image/png'));
      };
      img.onerror = function () { resolve(imageSource); };
      img.src = imageSource;
    });
  }

  /**
   * Applies a 3×3 sharpening kernel to an ImageData object.
   * Returns a new (sharpened) ImageData.
   */
  function applySharpen(imageData) {
    const { width, height, data } = imageData;
    const output = new ImageData(width, height);
    const out = output.data;

    // Sharpening kernel:  0 -1  0 / -1  5 -1 / 0 -1  0
    const kernel = [
       0, -1,  0,
      -1,  5, -1,
       0, -1,  0
    ];

    for (let y = 1; y < height - 1; y++) {
      for (let x = 1; x < width - 1; x++) {
        let r = 0, g = 0, b = 0;
        for (let ky = -1; ky <= 1; ky++) {
          for (let kx = -1; kx <= 1; kx++) {
            const idx = ((y + ky) * width + (x + kx)) * 4;
            const k   = kernel[(ky + 1) * 3 + (kx + 1)];
            r += data[idx]     * k;
            g += data[idx + 1] * k;
            b += data[idx + 2] * k;
          }
        }
        const i = (y * width + x) * 4;
        out[i]     = Math.min(255, Math.max(0, r));
        out[i + 1] = Math.min(255, Math.max(0, g));
        out[i + 2] = Math.min(255, Math.max(0, b));
        out[i + 3] = 255;
      }
    }
    return output;
  }

  // ─────────────────────────────────────────────────────────
  // MULTI-PASS OCR
  // ─────────────────────────────────────────────────────────
  /**
   * Runs Tesseract with multiple PSM modes and returns the result
   * with the highest confidence score.
   */
  async function runMultiPassOCR(worker, processedImage) {
    // PSM 6 = Assume single uniform block of text (best for label boxes)
    // PSM 3 = Fully automatic page segmentation (good general fallback)
    // PSM 11= Sparse text — find as much text as possible
    const psmModes = [6, 3, 11];
    let bestResult = null;

    for (const psm of psmModes) {
      try {
        await worker.setParameters({
          tessedit_pageseg_mode:    psm,
          tessedit_ocr_engine_mode: 1,
          // Whitelist useful characters for medicine labels
          tessedit_char_whitelist: ''
        });
        const { data } = await worker.recognize(processedImage);
        const confidence = Math.round(data.confidence || 0);
        console.log('[ScanLabel] PSM', psm, '→ confidence:', confidence);

        if (!bestResult || confidence > bestResult.confidence) {
          bestResult = { text: data.text || '', confidence };
        }
      } catch (e) {
        console.warn('[ScanLabel] PSM', psm, 'failed:', e.message);
      }
    }

    return bestResult || { text: '', confidence: 0 };
  }

  // ─────────────────────────────────────────────────────────
  // CLAUDE AI FALLBACK PARSER
  // ─────────────────────────────────────────────────────────
  /**
   * Sends raw OCR text to the Anthropic API and asks Claude to extract
   * structured medicine label data as JSON.
   */
  async function parseWithClaude(rawText) {
    try {
      console.log('[ScanLabel] Sending OCR text to Claude for smart parsing...');

      const prompt = `You are a pharmacy assistant AI. Below is raw OCR text extracted from a medicine box label. 
It may contain OCR errors, garbled characters, and packaging noise text (e.g. "DO NOT ACCEPT IF SEAL IS BROKEN", barcode numbers, lot numbers, etc.).

Extract the following fields from the label. If a field cannot be found, return an empty string for it.

Return ONLY a valid JSON object with exactly these keys:
{
  "medicine_name": "The brand or generic name of the medicine (e.g. Oxcarbazepine, Biogesic, Amoxicillin). Do NOT include packaging warnings like 'SEAL IS BROKEN' or lot/batch info.",
  "strength": "Dose strength with unit (e.g. 300 mg, 500mg, 10ml)",
  "form": "Dosage form (e.g. Tablet, Capsule, Syrup, Suspension)",
  "type": "Medicine classification (e.g. Anticonvulsant, Antibiotic, Painkiller, Antihypertensive)",
  "ingredient": "Active ingredient(s) if different from brand name",
  "dosage": "Dosage instructions (how many tablets/how often)",
  "intake": "How to take the medicine (e.g. take with food, swallow whole)",
  "indication": "What the medicine is used for / treats"
}

RAW OCR TEXT:
---
${rawText}
---

Respond with only the JSON object, no markdown, no explanation.`;

      const response = await fetch('https://api.anthropic.com/v1/messages', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          model: 'claude-sonnet-4-20250514',
          max_tokens: 1000,
          messages: [{ role: 'user', content: prompt }]
        })
      });

      if (!response.ok) {
        console.warn('[ScanLabel] Claude API returned HTTP', response.status);
        return null;
      }

      const apiData = await response.json();
      const textBlock = (apiData.content || []).find(b => b.type === 'text');
      if (!textBlock) return null;

      // Strip any accidental markdown fences
      const clean = textBlock.text.replace(/```json|```/g, '').trim();
      const parsed = JSON.parse(clean);
      console.log('[ScanLabel] Claude parsed:', parsed);
      return parsed;

    } catch (err) {
      console.warn('[ScanLabel] Claude fallback failed:', err.message);
      return null;
    }
  }

  // ─────────────────────────────────────────────────────────
  // OCR ORCHESTRATION
  // ─────────────────────────────────────────────────────────
  async function performOCR(imageSource) {
    if (isProcessing) return;
    isProcessing = true;

    try {
      if (slScanningOverlay) slScanningOverlay.classList.add('active');
      if (slSpinnerText) slSpinnerText.textContent = 'Preparing image...';

      const processedImage = await preprocessImage(imageSource);

      if (slSpinnerText) slSpinnerText.textContent = 'Initializing OCR engine...';
      const worker = await initTesseract();

      if (slSpinnerText) slSpinnerText.textContent = 'Scanning label text (multi-pass)...';
      const { text: extractedText, confidence } = await runMultiPassOCR(worker, processedImage);

      console.log('[ScanLabel] Best OCR result → confidence:', confidence, '\nRaw text:\n', extractedText);

      if (slSpinnerText) slSpinnerText.textContent = 'Analyzing label data...';

      // Step 1: Rule-based parse
      let parsedData = parseOCRText(extractedText);
      parsedData.all_text   = extractedText;
      parsedData.confidence = confidence;

      // Step 2: If name is missing/suspicious OR confidence < 60, try Claude
      const nameSuspicious = !parsedData.medicine_name
        || parsedData.medicine_name === 'Unknown Medicine'
        || isNoiseLine(parsedData.medicine_name);

      if (nameSuspicious || confidence < 60) {
        if (slSpinnerText) slSpinnerText.textContent = 'AI enhancement in progress...';
        const claudeParsed = await parseWithClaude(extractedText);
        if (claudeParsed) {
          // Merge Claude result, preferring Claude for empty or suspicious fields
          for (const key of ['medicine_name','strength','form','type','ingredient','dosage','intake','indication']) {
            if (claudeParsed[key] && (!parsedData[key] || parsedData[key] === '—' || (key === 'medicine_name' && nameSuspicious))) {
              parsedData[key] = claudeParsed[key];
            }
          }
        }
      }

      if (slScanningOverlay) slScanningOverlay.classList.remove('active');

      window._slOcrData      = parsedData;
      window._slDetectedName = parsedData.medicine_name;

      displayOCRResults(parsedData);
      isProcessing = false;

    } catch (err) {
      console.error('[ScanLabel] OCR failed:', err);
      if (slScanningOverlay) slScanningOverlay.classList.remove('active');
      alert('OCR processing failed. Please try again.\n' + err.message);
      isProcessing = false;
    }
  }

  // ─────────────────────────────────────────────────────────
  // NOISE / WARNING LINE DETECTION
  // ─────────────────────────────────────────────────────────
  // These patterns are common on Philippine medicine packaging and are
  // NOT medicine names — they caused the "L IS BROKEN" false detection.
  const NOISE_PATTERNS = [
    /\bseal\b/i,
    /\bbroken\b/i,
    /\bnot\s+accept/i,
    /\bdo\s+not\b/i,
    /\blot\s*(no|#|:)/i,
    /\bbatch\b/i,
    /\bexp(iry|iration)?\b/i,
    /\bmfg\b/i,
    /\bmanufactured\b/i,
    /\bdistributed\b/i,
    /\bkeep\s+out\b/i,
    /\bstore\b.*\btemperature/i,
    /\bdr\.\s+no/i,
    /\bdr\s*no/i,
    /\bptc\s*no/i,
    /\breg\s*no/i,
    /\bfda\b/i,
    /\bbarcode\b/i,
    /^\d[\d\s\-\/]+$/, // pure numbers / dates
    /\breport\b.*\badverse\b/i,
    /\bprescription\b/i,
    /\brx\s+only\b/i,
    /\bkeep\s+away\b/i,
    /\bfood\s+drug\b/i,
    /\bcosmetic\b/i,
    /\bphilippine\b/i,
  ];

  function isNoiseLine(text) {
    if (!text) return true;
    return NOISE_PATTERNS.some(re => re.test(text));
  }

  // ─────────────────────────────────────────────────────────
  // TEXT PARSING  (rule-based, with improved name detection)
  // ─────────────────────────────────────────────────────────
  function parseOCRText(rawText) {
    const lines = rawText.split('\n').map(l => l.trim()).filter(Boolean);
    const upper = rawText.toUpperCase();

    const result = {
      medicine_name: '',
      strength: '',
      form: '',
      type: '',
      ingredient: '',
      dosage: '',
      intake: '',
      indication: ''
    };

    // ── 1. MEDICINE NAME ──────────────────────────────────────
    // Expanded list — includes anticonvulsants, newer generics, common PH brands
    const knownBrands = [
      // Anticonvulsants / Neuro
      'Oxcarbazepine','Carbamazepine','Phenytoin','Valproic Acid','Levetiracetam',
      'Lamotrigine','Topiramate','Gabapentin','Pregabalin','Clonazepam',
      'Carbox','Trileptal',
      // Common PH OTC
      'Biogesic','Neozep','Diatabs','Dolfenal','Alaxan','Decolgen',
      'Medicol','Flanax','Solmux','Lagundi','Ascof','Bioflu',
      'Imodium','Kremil-S','Kremils','Buscopan',
      // Analgesics/NSAID
      'Mefenamic','Ponstan','Cataflam','Diclofenac','Celecoxib','Celebrex',
      'Paracetamol','Ibuprofen','Aspirin','Naproxen','Tramadol',
      // Antihypertensives
      'Losartan','Amlodipine','Atorvastatin','Metoprolol','Valsartan','Lisinopril',
      // Antidiabetics
      'Metformin','Glimepiride','Insulin','Sitagliptin','Empagliflozin',
      // Antibiotics
      'Amoxicillin','Augmentin','Azithromycin','Cotrimoxazole','Ciprofloxacin',
      'Cephalexin','Doxycycline','Clindamycin','Erythromycin',
      // Antihistamines
      'Cetirizine','Loratadine','Fexofenadine','Diphenhydramine','Chlorphenamine',
      // GI / antacids
      'Omeprazole','Pantoprazole','Esomeprazole','Ranitidine','Famotidine',
      'Loperamide','Ambroxol','Carbocisteine',
      // Vitamins
      'Vitamin C','Vitamin B','Vitamin D','Folic Acid','Ferrous','Calcium',
      'Magnesium','Zinc','Multivitamin','Ascorbic',
      // Cholesterol
      'Simvastatin','Rosuvastatin','Pravastatin',
      // Antispasmodics
      'Hyoscine','Butylscopolamine','Dicyclomine',
    ];

    for (const brand of knownBrands) {
      if (upper.includes(brand.toUpperCase())) {
        result.medicine_name = brand;
        break;
      }
    }

    // If not found in known list, apply heuristic search on lines
    if (!result.medicine_name) {
      const skipLine = /^(lot|batch|exp|mfg|reg|ua|ptc|dr\.|manufactured|distributed|\d{1,2}[\/\-]\d{1,2})/i;

      // Filter out noise lines explicitly
      const cleanLines = lines.filter(l =>
        l.length >= 3 &&
        !skipLine.test(l) &&
        !isNoiseLine(l)
      );

      // Strategy A: Find a line that has a dose unit — strip the dose to get the name
      const withDose = cleanLines.find(l => /\d+\s*(mg|g|ml|mcg|iu)/i.test(l));
      if (withDose) {
        const candidate = withDose.replace(/\s*\d+\s*(mg|g|ml|mcg|iu).*/i, '').trim();
        if (candidate.length >= 3 && !isNoiseLine(candidate)) {
          result.medicine_name = candidate;
        }
      }

      // Strategy B: Highest "uppercase capital letter density" among clean lines
      // (drug names are often written in ALL CAPS or Title Case on packaging)
      if (!result.medicine_name && cleanLines.length) {
        const scored = cleanLines.slice(0, 8).map(l => ({
          line: l,
          score: scoreNameCandidate(l)
        }));
        scored.sort((a, b) => b.score - a.score);
        if (scored[0].score > 0) {
          result.medicine_name = scored[0].line.substring(0, 60);
        }
      }
    }

    if (!result.medicine_name || isNoiseLine(result.medicine_name)) {
      result.medicine_name = lines.find(l => l.length >= 3 && !isNoiseLine(l)) || 'Unknown Medicine';
    }

    // ── 2. STRENGTH ───────────────────────────────────────────
    const strengthRe = /(\d{1,4}(?:\.\d+)?)\s*(mg|g|ml|mcg|iu|%)/gi;
    const strengthMatches = [];
    let sm;
    while ((sm = strengthRe.exec(rawText)) !== null) {
      const before = rawText.substring(Math.max(0, sm.index - 30), sm.index).toLowerCase();
      if (/exp|lot|batch|mfg|reg|\d{2}[\/\-]\d{2}/.test(before)) continue;
      strengthMatches.push(sm[0]);
    }
    if (strengthMatches.length) result.strength = strengthMatches[0];

    // ── 3. FORM ───────────────────────────────────────────────
    const formMatch = rawText.match(
      /(film[\s\-]coated\s+tablet|tablet|capsule|caplet|softgel|syrup|suspension|liquid|injection|ointment|cream|gel|patch|powder|solution|drops|spray|lozenge|suppository)/i
    );
    if (formMatch) {
      result.form = formMatch[1].charAt(0).toUpperCase() + formMatch[1].slice(1).toLowerCase();
    }

    // ── 4. ACTIVE INGREDIENT ──────────────────────────────────
    const ingredientRe = [
      /(?:active\s+ingredient[s]?|each\s+(?:tablet|capsule|ml)\s+contains?|contains?)[\s:]*([A-Za-z][\w\s\-]+?)(?:\s+\d|\n|$)/i,
      /(?:generic\s+name|inn)[\s:]*([A-Za-z][\w\s\-]+?)(?:\n|$)/i
    ];
    for (const re of ingredientRe) {
      const m = rawText.match(re);
      if (m && m[1] && m[1].trim().length > 2) {
        result.ingredient = m[1].trim().substring(0, 60);
        break;
      }
    }

    // ── 5. DOSAGE ─────────────────────────────────────────────
    const dosageRe = /(?:dosage(?:\s+and\s+administration)?|recommended\s+dose|suggested\s+dose|dose)[\s:]*([^\n]{5,120})/i;
    const dm = rawText.match(dosageRe);
    if (dm) result.dosage = dm[1].trim();

    // ── 6. INTAKE / HOW TO TAKE ───────────────────────────────
    const intakeRe = [
      /(?:direction[s]?\s+for\s+use|how\s+to\s+(?:take|use)|administration)[\s:]*([^\n]{5,120})/i,
      /(?:take|administer|swallow|dissolve)[\s:]+([^\n]{5,120})/i,
      /(?:adult[s]?|children|pediatric)[\s:]*([^\n]{5,120})/i
    ];
    for (const re of intakeRe) {
      const m = rawText.match(re);
      if (m && m[1]) { result.intake = m[1].trim().substring(0, 120); break; }
    }

    // ── 7. INDICATION ─────────────────────────────────────────
    const indicRe = [
      /(?:indication[s]?|used?\s+for|for\s+the\s+(?:relief|treatment)|treats?)[\s:]*([^\n]{5,150})/i,
      /(?:relieves?|reduces?|helps?\s+(?:relieve|treat))[\s:]*([^\n]{5,150})/i
    ];
    for (const re of indicRe) {
      const m = rawText.match(re);
      if (m && m[1]) { result.indication = m[1].trim().substring(0, 150); break; }
    }

    // ── 8. TYPE CLASSIFICATION ────────────────────────────────
    const typeMap = [
      { type: 'Anticonvulsant',     keys: ['oxcarbazepine','carbamazepine','phenytoin','valproic','levetiracetam','lamotrigine','topiramate','gabapentin','pregabalin','clonazepam','trileptal','carbox'] },
      { type: 'Antibiotic',         keys: ['amoxicillin','azithromycin','cephalosporin','penicillin','cephalexin','cotrimoxazole','ciprofloxacin','doxycycline','clindamycin','erythromycin'] },
      { type: 'Painkiller/NSAID',   keys: ['paracetamol','ibuprofen','aspirin','tramadol','mefenamic','naproxen','diclofenac','celecoxib','ketorolac','dolfenal','alaxan','biogesic','medicol'] },
      { type: 'Vitamin/Supplement', keys: ['vitamin','ascorbic','calcium','iron','ferrous','zinc','folic','multivitamin','magnesium'] },
      { type: 'Antacid',            keys: ['antacid','omeprazole','ranitidine','famotidine','pantoprazole','esomeprazole','kremil','simethicone'] },
      { type: 'Antihypertensive',   keys: ['losartan','enalapril','amlodipine','metoprolol','valsartan','candesartan','lisinopril'] },
      { type: 'Antidiabetic',       keys: ['metformin','glimepiride','insulin','gliclazide','sitagliptin','empagliflozin'] },
      { type: 'Antihistamine',      keys: ['cetirizine','loratadine','fexofenadine','diphenhydramine','chlorphenamine','hydroxyzine'] },
      { type: 'Antidiarrheal',      keys: ['loperamide','diatabs','attapulgite','bismuth'] },
      { type: 'Cough/Cold',         keys: ['dextromethorphan','guaifenesin','phenylephrine','pseudoephedrine','lagundi','carbocisteine','ambroxol','neozep','bioflu','solmux','decolgen'] },
      { type: 'Cholesterol',        keys: ['atorvastatin','simvastatin','rosuvastatin','pravastatin'] },
      { type: 'Antispasmodic',      keys: ['hyoscine','buscopan','butylscopolamine','dicyclomine'] }
    ];

    for (const { type, keys } of typeMap) {
      if (keys.some(k => upper.includes(k.toUpperCase()))) {
        result.type = type;
        break;
      }
    }
    if (!result.type) result.type = 'Medicine';

    return result;
  }

  /**
   * Score a line as a medicine name candidate.
   * Higher = more likely to be a drug name.
   */
  function scoreNameCandidate(line) {
    if (!line || isNoiseLine(line)) return -1;

    let score = 0;

    // Reward ALL_CAPS words (drug names are often fully capitalised)
    const capsWords = (line.match(/\b[A-Z]{3,}\b/g) || []);
    score += capsWords.reduce((s, w) => s + w.length, 0);

    // Reward lines that look like single medical words or brand names
    if (/^[A-Z][a-z]+(?:\s+[A-Z][a-z]+)*$/.test(line)) score += 10; // Title Case

    // Penalise lines that contain digits (likely doses or dates, not names)
    if (/\d/.test(line)) score -= 5;

    // Penalise very long lines (packaging info, not drug names)
    if (line.length > 50) score -= 8;

    // Penalise lines with common packaging words
    if (/\b(film|coated|seal|broken|store|keep|away|children|prescription)\b/i.test(line)) score -= 20;

    return score;
  }

  // ─────────────────────────────────────────────────────────
  // DISPLAY RESULTS
  // ─────────────────────────────────────────────────────────
  function displayOCRResults(data) {
    if (slResultIdle)    slResultIdle.style.display    = 'none';
    if (slResultHeader)  slResultHeader.style.display  = 'flex';
    if (slResultContent) slResultContent.style.display = 'block';

    if (slMedName) slMedName.textContent = data.medicine_name || 'Unknown Medicine';
    if (slMedSub)  slMedSub.textContent  = 'Detected from label scan';

    const confValue = Math.min(Math.max(data.confidence || 0, 0), 100);
    if (slConfPct) slConfPct.textContent = confValue.toFixed(1) + '%';
    if (slConfBar) slConfBar.style.width = confValue + '%';

    if (slStrength)   slStrength.textContent   = data.strength   || '—';
    if (slForm)       slForm.textContent        = data.form       || '—';
    if (slType)       slType.textContent        = data.type       || '—';
    if (slIngredient) slIngredient.textContent  = data.ingredient || '—';
    if (slDosage)     slDosage.textContent      = data.dosage     || '—';
    if (slIntake)     slIntake.textContent      = data.intake     || '—';
    if (slIndication) slIndication.textContent  = data.indication || '—';
    if (slRawText)    slRawText.textContent     = data.all_text   || '';

    if (slBtnReserve) {
      slBtnReserve.disabled = (!data.medicine_name || data.medicine_name === 'Unknown Medicine');
    }
  }

  function resetResults() {
    if (slResultIdle)    slResultIdle.style.display    = 'flex';
    if (slResultHeader)  slResultHeader.style.display  = 'none';
    if (slResultContent) slResultContent.style.display = 'none';
    if (slBtnReserve)    slBtnReserve.disabled         = true;
    if (slPreviewImg)    slPreviewImg.src              = '';
    if (slUploadZone)    slUploadZone.classList.remove('has-image');

    window._slDetectedName = null;
    window._slOcrData = {
      medicine_name: '', strength: '', form: '', type: '',
      ingredient: '', dosage: '', intake: '', indication: '',
      all_text: '', confidence: 0
    };
  }

  // ─────────────────────────────────────────────────────────
  // MODE SWITCHING
  // ─────────────────────────────────────────────────────────
  if (slUploadModeBtn) {
    slUploadModeBtn.addEventListener('click', function () {
      if (currentMode === 'camera') releaseCamera();
      currentMode = 'upload';
      slUploadModeBtn.classList.add('active');
      if (slCameraModeBtn)       slCameraModeBtn.classList.remove('active');
      if (slUploadZone)          slUploadZone.style.display          = 'flex';
      if (slCameraFeedContainer) slCameraFeedContainer.style.display = 'none';
    });
  }

  if (slCameraModeBtn) {
    slCameraModeBtn.addEventListener('click', async function () {
      currentMode = 'camera';
      slCameraModeBtn.classList.add('active');
      if (slUploadModeBtn)       slUploadModeBtn.classList.remove('active');
      if (slUploadZone)          slUploadZone.style.display          = 'none';
      if (slCameraFeedContainer) slCameraFeedContainer.style.display = 'flex';
      await startCamera();
    });
  }

  // ─────────────────────────────────────────────────────────
  // FILE UPLOAD HANDLING
  // ─────────────────────────────────────────────────────────
  if (slFileInput) {
    slFileInput.addEventListener('change', function (e) {
      const file = e.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function (event) {
          const imageData = event.target.result;
          if (slPreviewImg) slPreviewImg.src = imageData;
          if (slUploadZone)  slUploadZone.classList.add('has-image');
          performOCR(imageData);
        };
        reader.readAsDataURL(file);
      }
    });
  }

  if (slUploadZone) {
    slUploadZone.addEventListener('dragover', function (e) {
      e.preventDefault();
      slUploadZone.classList.add('dragover');
    });

    slUploadZone.addEventListener('dragleave', function () {
      slUploadZone.classList.remove('dragover');
    });

    slUploadZone.addEventListener('drop', function (e) {
      e.preventDefault();
      slUploadZone.classList.remove('dragover');
      const file = e.dataTransfer.files[0];
      if (file && file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = function (event) {
          const imageData = event.target.result;
          if (slPreviewImg) slPreviewImg.src = imageData;
          slUploadZone.classList.add('has-image');
          performOCR(imageData);
        };
        reader.readAsDataURL(file);
      }
    });

    slUploadZone.addEventListener('click', function (e) {
      if (slUploadZone.classList.contains('has-image')) return;
      if (e.target.closest('button')) return;
      if (slFileInput) slFileInput.click();
    });
  }

  // ─────────────────────────────────────────────────────────
  // CAMERA HANDLING
  // ─────────────────────────────────────────────────────────
  async function startCamera() {
    try {
      console.log('[ScanLabel] Requesting camera access...');
      webcamStream = await navigator.mediaDevices.getUserMedia({
        video: { facingMode: 'environment', width: { ideal: 1920 }, height: { ideal: 1080 } }
      });
      if (slCameraFeed) {
        slCameraFeed.srcObject = webcamStream;
        slCameraFeed.onloadedmetadata = function () { slCameraFeed.play(); };
      }
      console.log('[ScanLabel] Camera started');
    } catch (err) {
      console.error('[ScanLabel] Camera access denied:', err);
      alert('Camera access denied or unavailable. Please allow camera permissions.');
      currentMode = 'upload';
      if (slUploadModeBtn) slUploadModeBtn.click();
    }
  }

  function releaseCamera() {
    if (webcamStream) {
      webcamStream.getTracks().forEach(track => track.stop());
      webcamStream = null;
      if (slCameraFeed) slCameraFeed.srcObject = null;
      console.log('[ScanLabel] Camera released');
    }
  }

  if (slCaptureBtn) {
    slCaptureBtn.addEventListener('click', function () {
      if (!webcamStream) { alert('Camera is not ready. Please try again.'); return; }
      if (!slCameraCanvas || !slCameraFeed) return;

      const context = slCameraCanvas.getContext('2d');
      slCameraCanvas.width  = slCameraFeed.videoWidth;
      slCameraCanvas.height = slCameraFeed.videoHeight;
      context.drawImage(slCameraFeed, 0, 0);

      const imageData = slCameraCanvas.toDataURL('image/jpeg', 0.95);
      if (slPreviewImg) slPreviewImg.src = imageData;
      if (slUploadZone) slUploadZone.classList.add('has-image');

      currentMode = 'upload';
      if (slUploadModeBtn) slUploadModeBtn.click();

      performOCR(imageData);
    });
  }

  // ─────────────────────────────────────────────────────────
  // MODAL LIFECYCLE
  // ─────────────────────────────────────────────────────────
  const scanLabelOverlay = document.getElementById('scanLabelOverlay');

  if (scanLabelOverlay) {
    const observer = new MutationObserver(function () {
      const isActive = scanLabelOverlay.classList.contains('active');
      if (isActive) {
        resetResults();
      } else {
        if (currentMode === 'camera') releaseCamera();
      }
    });
    observer.observe(scanLabelOverlay, { attributes: true, attributeFilter: ['class'] });
  }

  window.addEventListener('beforeunload', function () {
    releaseCamera();
    if (tesseractWorker) {
      tesseractWorker.terminate().catch(err => console.warn('[ScanLabel] Tesseract termination:', err));
    }
  });

  console.log('[ScanLabel] Improved module loaded and ready');
})();