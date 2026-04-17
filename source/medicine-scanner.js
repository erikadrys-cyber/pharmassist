/* ════════════════════════════════════════════════════════════════
   MEDICINE SCANNER - TensorFlow.js Integration
   Teachable Machine Image Model for Medicine Detection
════════════════════════════════════════════════════════════════ */

let model = null;
let webcam = null;
let labelContainer = null;
let maxPredictions = 0;

function getTmModelBaseUrl() {
    const fromPage = typeof window.MEDICINE_TM_MODEL_BASE === 'string' ? window.MEDICINE_TM_MODEL_BASE.trim() : '';
    const base = fromPage || 'datasets/tm-models/';
    return base.endsWith('/') ? base : base + '/';
}

const MODEL_URL = getTmModelBaseUrl();
let currentDetectedMedicine = null;
let currentConfidence = 0;

// Medicine database mapping detected names to detailed info
const MEDICINE_DATABASE = {
    'Ibuprofen': {
        name: 'Ibuprofen',
        strength: '200mg',
        form: 'Tablet',
        type: 'NSAID',
        activeIngredient: 'Ibuprofen USP',
        description: 'Over-the-counter pain reliever and fever reducer'
    },
    'Biogesic': {
        name: 'Biogesic',
        strength: '500mg',
        form: 'Tablet',
        type: 'Analgesic',
        activeIngredient: 'Paracetamol',
        description: 'Pain reliever and fever reducer'
    }
};

/* ════════════════════════════════════════════
   INITIALIZATION
════════════════════════════════════════════ */

async function initializeModel() {
    try {
        updateProgress('Loading model...', 10);

        if (typeof tmImage === 'undefined') {
            throw new Error('Teachable Machine library (tmImage) did not load. Check script tags / network.');
        }
        if (typeof tf === 'undefined') {
            throw new Error('TensorFlow.js (tf) did not load. Check script tags / network.');
        }

        // Load the model from Teachable Machine
        const modelURL = MODEL_URL + 'model.json';
        model = await tmImage.load(modelURL, MODEL_URL + 'metadata.json');
        
        updateProgress('Model loaded. Initializing...', 40);
        maxPredictions = model.getTotalClasses();
        
        updateProgress('Ready to scan!', 100);
        console.log('✓ Model loaded successfully. Classes:', maxPredictions);
        return true;
    } catch (error) {
        console.error('❌ Model loading failed:', error);
        const hint = error && error.message ? ` ${error.message}` : '';
        updateProgress('Failed to load model.' + hint, 0, true);
        return false;
    }
}

async function ensureModelLoaded() {
    if (model) return true;
    return initializeModel();
}

function createCamerafeedPlaceholder() {
    const el = document.createElement('video');
    el.id = 'camerafeed';
    el.setAttribute('playsinline', '');
    el.setAttribute('autoplay', '');
    return el;
}

/** TM.Webcam builds its own <video> (not in DOM). Swap it in for #camerafeed and satisfy autoplay policy. */
function mountTeachableWebcamVideo(w) {
    const slot = document.getElementById('camerafeed');
    const v = w.webcam;
    if (!slot || !v) return;
    v.muted = true;
    v.setAttribute('playsinline', '');
    v.setAttribute('webkit-playsinline', '');
    v.autoplay = true;
    v.style.display = 'block';
    v.style.width = '100%';
    v.style.height = '100%';
    v.style.objectFit = 'cover';
    v.id = 'camerafeed';
    slot.replaceWith(v);
}

function releaseWebcamToUploadMode() {
    if (!webcam) return;
    try {
        webcam.stop();
    } catch (e) { /* ignore */ }
    const v = webcam.webcam;
    const container = document.getElementById('cameraFeedContainer');
    if (v && container && container.contains(v)) {
        v.replaceWith(createCamerafeedPlaceholder());
    }
    webcam = null;
}

async function initializeWebcam() {
    try {
        updateProgress('Requesting camera access...', 20);

        const flip = true;
        webcam = new tmImage.Webcam(224, 224, flip);

        await webcam.setup();

        // Off-DOM video often never paints; unmuted play() can hang on autoplay rules
        mountTeachableWebcamVideo(webcam);

        await webcam.play();

        updateProgress('Camera ready!', 50);
        document.getElementById('camerafeed').style.display = 'block';

        window.requestAnimationFrame(predictLoop);
        console.log('✓ Webcam initialized');
        return true;
    } catch (error) {
        releaseWebcamToUploadMode();
        console.error('❌ Camera initialization failed:', error);
        updateProgress('Camera access denied or unavailable.', 0, true);
        return false;
    }
}

/* ════════════════════════════════════════════
   PREDICTION LOOP
════════════════════════════════════════════ */

async function predictLoop() {
    if (!webcam || !model) return;

    try {
        // Required: copies the live video frame into webcam.canvas (see TM Webcam.renderCameraToCanvas)
        webcam.update();

        const canvas = document.getElementById('camerafeedcanvas');
        const ctx = canvas.getContext('2d');

        canvas.width = webcam.canvas.width;
        canvas.height = webcam.canvas.height;

        ctx.drawImage(webcam.canvas, 0, 0);
        
        // Make prediction
        const prediction = await model.predict(canvas);
        
        // Find best prediction
        let bestPrediction = null;
        let bestConfidence = 0;
        
        for (let i = 0; i < maxPredictions; i++) {
            if (prediction[i].probability > bestConfidence) {
                bestConfidence = prediction[i].probability;
                bestPrediction = prediction[i];
            }
        }
        
        // High confidence threshold for medicine detection
        if (bestConfidence > 0.7) {
            showWebcamResult(bestPrediction.className, bestConfidence);
        }
        
    } catch (error) {
        console.error('Prediction error:', error);
    }
    
    window.requestAnimationFrame(predictLoop);
}

function showWebcamResult(className, confidence) {
    const reticle = document.querySelector('.reticle');
    if (reticle && confidence > 0.7) {
        reticle.classList.add('success');
        updateProgress(`✓ Detected: ${className} (${(confidence * 100).toFixed(1)}%)`, 100);
    }
}

/* ════════════════════════════════════════════
   IMAGE UPLOAD & PROCESSING
════════════════════════════════════════════ */

function setupImageUpload() {
    const uploadZone = document.getElementById('uploadZone');
    const fileInput = document.getElementById('scannerFileInput');
    
    // Drag and drop
    uploadZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadZone.classList.add('dragover');
    });
    
    uploadZone.addEventListener('dragleave', () => {
        uploadZone.classList.remove('dragover');
    });
    
    uploadZone.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadZone.classList.remove('dragover');
        const files = e.dataTransfer.files;
        if (files.length) processImageFile(files[0]);
    });
    
    // File input change
    fileInput.addEventListener('change', (e) => {
        if (e.target.files.length) processImageFile(e.target.files[0]);
    });
}

function processImageFile(file) {
    if (!file.type.startsWith('image/')) {
        alert('Please select an image file');
        return;
    }
    
    if (file.size > 10 * 1024 * 1024) {
        alert('File is too large (max 10MB)');
        return;
    }
    
    const reader = new FileReader();
    reader.onload = (e) => {
        const img = new Image();
        img.onload = async () => {
            const loaded = await ensureModelLoaded();
            if (!loaded) return;
            await analyzeImage(img);
        };
        img.src = e.target.result;
    };
    reader.readAsDataURL(file);
}

async function analyzeImage(imgElement) {
    try {
        const uploadZone = document.getElementById('uploadZone');
        const scanningOverlay = document.getElementById('scanningOverlay');
        
        // Show preview and start scanning
        const previewImg = document.getElementById('previewImg');
        previewImg.src = imgElement.src;
        uploadZone.classList.add('has-image');
        
        scanningOverlay.classList.add('active');
        updateProgress('Analyzing medicine...', 50);
        
        // Resize image to model input size (224x224)
        const canvas = document.createElement('canvas');
        canvas.width = 224;
        canvas.height = 224;
        const ctx = canvas.getContext('2d');
        
        // Calculate aspect ratio and center crop
        const size = Math.min(imgElement.width, imgElement.height);
        const x = (imgElement.width - size) / 2;
        const y = (imgElement.height - size) / 2;
        
        ctx.drawImage(imgElement, x, y, size, size, 0, 0, 224, 224);
        
        // Get prediction
        const predictions = await model.predict(canvas);
        
        updateProgress('Processing results...', 90);
        
        // Find best prediction
        let bestPrediction = null;
        let bestConfidence = 0;
        let allPredictions = [];
        
        for (let i = 0; i < maxPredictions; i++) {
            const pred = predictions[i];
            allPredictions.push({
                label: pred.className,
                probability: pred.probability
            });
            
            if (pred.probability > bestConfidence) {
                bestConfidence = pred.probability;
                bestPrediction = pred;
            }
        }
        
        // Sort by confidence descending
        allPredictions.sort((a, b) => b.probability - a.probability);
        
        updateProgress('Complete!', 100);
        scanningOverlay.classList.remove('active');
        
        // Display results
        displayResults(bestPrediction.className, bestConfidence, allPredictions);
        
        // Enable reserve button
        document.getElementById('btnReserve').disabled = false;
        
    } catch (error) {
        console.error('Analysis error:', error);
        updateProgress('Error analyzing image. Try again.', 0, true);
    }
}

/* ════════════════════════════════════════════
   RESULTS DISPLAY
════════════════════════════════════════════ */

function displayResults(detectedName, confidence, allPredictions) {
    const resultCol = document.getElementById('resultCol');
    const resultIdle = document.getElementById('resultIdle');
    const resultHeader = document.getElementById('resultHeader');
    const resultContent = document.getElementById('resultContent');
    
    // Store detection for reserve function
    window._szDetectedName = detectedName;
    currentDetectedMedicine = detectedName;
    currentConfidence = confidence;
    
    // Hide idle state, show results
    resultIdle.style.display = 'none';
    resultHeader.style.display = 'flex';
    resultContent.style.display = 'block';
    
    // Get medicine info from database
    const medInfo = MEDICINE_DATABASE[detectedName] || {
        name: detectedName,
        strength: 'Unknown',
        form: 'Unknown',
        type: 'Unknown',
        activeIngredient: 'Unknown',
        description: 'Detected by AI model'
    };
    
    // Medicine name
    document.getElementById('resMedName').textContent = medInfo.name;
    document.getElementById('resMedSub').textContent = medInfo.description;
    
    // Confidence score
    const confPct = (confidence * 100).toFixed(1);
    document.getElementById('confPct').textContent = `${confPct}%`;
    document.getElementById('confBar').style.width = `${confidence * 100}%`;
    
    // Medicine details
    document.getElementById('resStrength').textContent = medInfo.strength;
    document.getElementById('resForm').textContent = medInfo.form;
    document.getElementById('resType').textContent = medInfo.type;
    document.getElementById('resIngredient').textContent = medInfo.activeIngredient;
    
    // All predictions (show if confidence < 95%)
    if (confidence < 0.95) {
        displayAllPredictions(allPredictions);
    }
    
    // Scroll to results
    resultCol.scrollTop = 0;
}

function displayAllPredictions(predictions) {
    const container = document.getElementById('allPredictions');
    
    let html = '<div style="margin-bottom: 8px; font-size: 11px; font-weight: 700; color: #3b82f6; text-transform: uppercase; letter-spacing: 0.5px;">All Predictions</div>';
    
    predictions.forEach((pred, idx) => {
        const barWidth = Math.max(pred.probability * 100, 2);
        const isPrimary = idx === 0;
        html += `
            <div class="prediction-row">
                <div class="pred-label" style="${isPrimary ? 'color: #3b82f6; font-weight: 700;' : ''}">${pred.label}</div>
                <div class="pred-bar-bg">
                    <div class="pred-bar" style="width: ${barWidth}%; ${isPrimary ? 'background: linear-gradient(90deg, #10b981, #059669);' : ''}"></div>
                </div>
                <div class="pred-pct">${(pred.probability * 100).toFixed(1)}%</div>
            </div>
        `;
    });
    
    container.innerHTML = html;
    container.style.display = 'block';
}

/* ════════════════════════════════════════════
   CAMERA FUNCTIONALITY
════════════════════════════════════════════ */

function setupCameraMode() {
    const uploadModeBtn = document.getElementById('uploadModeBtn');
    const cameraModeBtn = document.getElementById('cameraModeBtn');
    const uploadZone = document.getElementById('uploadZone');
    const cameraFeedContainer = document.getElementById('cameraFeedContainer');
    const capturePhotoBtn = document.getElementById('capturePhotoBtn');
    const scannerCameraBtn = document.getElementById('scannerCameraBtn');
    
    function switchToUploadMode() {
        uploadModeBtn.classList.add('active');
        cameraModeBtn.classList.remove('active');
        uploadZone.style.display = 'flex';
        cameraFeedContainer.style.display = 'none';
        releaseWebcamToUploadMode();
    }
    
    async function switchToCameraMode() {
        uploadModeBtn.classList.remove('active');
        cameraModeBtn.classList.add('active');
        uploadZone.style.display = 'none';
        cameraFeedContainer.style.display = 'block';
        
        if (!model) {
            updateProgress('Loading model...', 30);
            const loaded = await initializeModel();
            if (!loaded) {
                switchToUploadMode();
                return;
            }
        }
        
        if (!webcam) {
            await initializeWebcam();
        } else {
            await webcam.play();
        }
    }
    
    uploadModeBtn.addEventListener('click', switchToUploadMode);
    cameraModeBtn.addEventListener('click', switchToCameraMode);
    scannerCameraBtn.addEventListener('click', switchToCameraMode);
    
    capturePhotoBtn.addEventListener('click', async () => {
        if (webcam && model) {
            webcam.update();
            const canvas = document.createElement('canvas');
            canvas.width = webcam.canvas.width;
            canvas.height = webcam.canvas.height;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(webcam.canvas, 0, 0);
            
            // Convert to image
            canvas.toBlob(blob => {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const img = new Image();
                    img.onload = async () => {
                        await analyzeImage(img);
                    };
                    img.src = e.target.result;
                };
                reader.readAsDataURL(blob);
            });
        }
    });
}

/* ════════════════════════════════════════════
   UTILITY FUNCTIONS
════════════════════════════════════════════ */

function updateProgress(message, percent, isError = false) {
    const ocrProgressWrap = document.getElementById('ocrProgressWrap');
    const ocrProgressLabel = document.getElementById('ocrProgressLabel');
    const ocrBar = document.getElementById('ocrBar');
    const scanSpinnerText = document.getElementById('scanSpinnerText');
    
    if (scanSpinnerText) scanSpinnerText.textContent = message;
    if (ocrProgressLabel) ocrProgressLabel.textContent = message;
    if (ocrBar) ocrBar.style.width = percent + '%';
    
    if (percent > 0) {
        ocrProgressWrap.style.display = 'block';
    } else if (isError) {
        ocrProgressWrap.style.display = 'block';
        ocrProgressWrap.style.opacity = '0.7';
    }
}

/* ════════════════════════════════════════════
   INITIALIZATION ON PAGE LOAD
════════════════════════════════════════════ */

document.addEventListener('DOMContentLoaded', async () => {
    console.log('🔧 Initializing Medicine Scanner...');
    
    setupImageUpload();
    setupCameraMode();
    
    // Initialize model when scanner opens
    const scannerOverlay = document.getElementById('scannerOverlay');
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            if (scannerOverlay.classList.contains('active') && !model) {
                console.log('📱 Scanner opened. Loading model...');
                initializeModel();
            }
        });
    });
    
    observer.observe(scannerOverlay, { attributes: true, attributeFilter: ['class'] });
});