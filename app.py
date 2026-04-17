#!/usr/bin/env python3
"""
PharmaSee Medicine Scanner - Flask Web Interface
Provides web-based UI for scanning medicine labels
"""

from flask import Flask, render_template, request, jsonify
from werkzeug.utils import secure_filename
import os
import json
from dotenv import load_dotenv
from google.cloud import vision
from pathlib import Path

# Load environment variables
load_dotenv()

# ═══════════════════════════════════════════════════════════
# FLASK APP SETUP
# ═══════════════════════════════════════════════════════════

app = Flask(__name__)
app.config['MAX_CONTENT_LENGTH'] = 10 * 1024 * 1024  # 10 MB
app.config['UPLOAD_FOLDER'] = 'uploads'

# Create uploads folder if not exists
os.makedirs(app.config['UPLOAD_FOLDER'], exist_ok=True)

# Initialize Vision API
try:
    credentials_path = os.getenv('GOOGLE_APPLICATION_CREDENTIALS', './vision-api-project-492402-367d3501e068.json')
    os.environ['GOOGLE_APPLICATION_CREDENTIALS'] = credentials_path
    vision_client = vision.ImageAnnotatorClient()
except Exception as e:
    print(f"⚠️  Vision API Error: {e}")
    vision_client = None

# ═══════════════════════════════════════════════════════════
# MEDICINE PARSER (Same as CLI version)
# ═══════════════════════════════════════════════════════════

import re

class MedicineParser:
    """Parses OCR text into structured medicine data"""
    
    MEDICINE_CATEGORIES = {
        'paracetamol|acetaminophen|tylenol': 'Analgesic & Antipyretic',
        'ibuprofen|advil|brufen': 'Anti-inflammatory',
        'amoxicillin|penicillin|antibiotic': 'Antibiotic',
        'omeprazole|pantoprazole|antacid': 'Antacid / GI',
        'vitamin|supplement|multivitamin': 'Vitamin / Supplement',
        'aspirin': 'Anticoagulant',
        'metformin|diabetes': 'Antidiabetic',
        'atorvastatin|lipitor|cholesterol': 'Cardiovascular',
        'loratadine|cetirizine|antihistamine': 'Allergy / Antihistamine',
        'cough|dextromethorphan': 'Cough & Cold',
    }
    
    @staticmethod
    def parse(raw_text):
        """Parse raw OCR text"""
        if not raw_text or not raw_text.strip():
            return MedicineParser._empty_result()
        
        return {
            'name': MedicineParser._extract_name(raw_text),
            'strength': MedicineParser._extract_strength(raw_text),
            'form': MedicineParser._extract_form(raw_text),
            'ingredient': MedicineParser._extract_ingredient(raw_text),
            'dosage': MedicineParser._extract_dosage(raw_text),
            'frequency': MedicineParser._extract_frequency(raw_text),
            'category': MedicineParser._extract_category(raw_text),
            'rx_type': MedicineParser._extract_rx_type(raw_text)[0],
            'rx_sub': MedicineParser._extract_rx_type(raw_text)[1],
            'raw_text': raw_text
        }
    
    @staticmethod
    def _empty_result():
        return {
            'name': '—',
            'strength': '—',
            'form': '—',
            'ingredient': '—',
            'dosage': '—',
            'frequency': '—',
            'category': 'Medicine',
            'rx_type': 'Unknown',
            'rx_sub': 'consult pharmacist',
            'raw_text': ''
        }
    
    @staticmethod
    def _extract_name(text):
        patterns = [
            r'^([A-Z][A-Za-z\s\-®™]+(?:\s+\d+\s*(?:mg|mcg|ml|g|IU))?)',
            r'(?:brand|product|medicine|drug)[\s:]+([^\n]+)',
            r'([A-Z][a-z]+(?:\s+[A-Z][a-z]+){0,3})\s+\d+\s*mg'
        ]
        
        for pattern in patterns:
            match = re.search(pattern, text, re.MULTILINE | re.IGNORECASE)
            if match:
                name = (match.group(1) or match.group(0)).strip()
                if 2 < len(name) < 80:
                    return name
        
        lines = [l.strip() for l in text.split('\n') if 2 < len(l.strip()) < 50]
        return lines[0] if lines else 'Unknown Medicine'
    
    @staticmethod
    def _extract_strength(text):
        match = re.search(r'(\d+(?:\.\d+)?\s*(?:mg|mcg|ml|g|IU|%))', text, re.IGNORECASE)
        return match.group(1).strip() if match else '—'
    
    @staticmethod
    def _extract_form(text):
        match = re.search(
            r'(tablet|capsule|syrup|suspension|injection|cream|gel|ointment|drops|patch|suppository|lozenge)',
            text, re.IGNORECASE
        )
        return match.group(1).capitalize() if match else '—'
    
    @staticmethod
    def _extract_ingredient(text):
        patterns = [
            r'active\s+ingredient[s]?[\s:]+([^\n]+)',
            r'contains?[\s:]+([^\n]+)',
            r'generic[\s:]+([^\n]+)',
            r'composition[\s:]+([^\n]+)'
        ]
        
        for pattern in patterns:
            match = re.search(pattern, text, re.IGNORECASE)
            if match:
                ingredient = match.group(1).strip()
                if len(ingredient) < 100:
                    return ingredient
        
        return '—'
    
    @staticmethod
    def _extract_dosage(text):
        patterns = [
            r'dosage[\s:]+([^\n]+)',
            r'dose[\s:]+([^\n]+)',
            r'take[\s:]+([^\n]+)',
            r'directions[\s:]+([^\n]+)'
        ]
        
        for pattern in patterns:
            match = re.search(pattern, text, re.IGNORECASE)
            if match:
                dosage = match.group(1).strip()
                if 5 < len(dosage) < 200:
                    return dosage
        
        return '—'
    
    @staticmethod
    def _extract_frequency(text):
        match = re.search(
            r'(once|twice|three times|every\s+\d+\s+hours?|daily|weekly|as\s+needed)[^\n]*',
            text, re.IGNORECASE
        )
        return match.group(1).strip() if match else 'As directed by physician'
    
    @staticmethod
    def _extract_category(text):
        search_text = text.lower()
        
        for keywords, category in MedicineParser.MEDICINE_CATEGORIES.items():
            if any(re.search(keyword, search_text) for keyword in keywords.split('|')):
                return category
        
        return 'Medicine'
    
    @staticmethod
    def _extract_rx_type(text):
        text_lower = text.lower()
        
        if re.search(r'over.the.counter|otc|without\s+prescription', text_lower):
            return ('Over-the-Counter', 'no prescription needed')
        elif re.search(r'non.prescription|non prescription', text_lower):
            return ('Non-Prescription', 'no prescription needed')
        else:
            return ('Prescription Required', 'prescription needed')

# ═══════════════════════════════════════════════════════════
# ROUTES
# ═══════════════════════════════════════════════════════════

@app.route('/')
def index():
    """Home page"""
    return render_template('scanner.html')

@app.route('/api/scan', methods=['POST'])
def api_scan():
    """API endpoint for scanning medicine label"""
    
    if not vision_client:
        return jsonify({'error': 'Vision API not configured'}), 500
    
    if 'image' not in request.files:
        return jsonify({'error': 'No image provided'}), 400
    
    file = request.files['image']
    
    if file.filename == '':
        return jsonify({'error': 'No file selected'}), 400
    
    if not allowed_file(file.filename):
        return jsonify({'error': 'Unsupported file format'}), 400
    
    try:
        # Read image
        image_data = file.read()
        
        # Validate size
        if len(image_data) > app.config['MAX_CONTENT_LENGTH']:
            return jsonify({'error': 'File too large'}), 400
        
        # Call Vision API
        image = vision.Image(content=image_data)
        response = vision_client.text_detection(image=image)
        
        if not response.text_annotations:
            return jsonify({
                'success': False,
                'error': 'No text detected in image',
                'message': 'Try a clearer photo with better lighting'
            }), 400
        
        # Extract text
        raw_text = response.text_annotations[0].description
        confidence = min(95, 70 + (len(response.text_annotations) * 2))
        
        # Parse medicine info
        medicine_data = MedicineParser.parse(raw_text)
        medicine_data['confidence'] = confidence
        
        return jsonify({
            'success': True,
            'data': medicine_data
        }), 200
    
    except Exception as e:
        return jsonify({
            'success': False,
            'error': str(e)
        }), 500

@app.route('/api/health', methods=['GET'])
def health_check():
    """Health check endpoint"""
    return jsonify({
        'status': 'ok',
        'vision_api': 'configured' if vision_client else 'not_configured'
    }), 200

def allowed_file(filename):
    """Check if file extension is allowed"""
    ALLOWED_EXTENSIONS = {'jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp'}
    return '.' in filename and filename.rsplit('.', 1)[1].lower() in ALLOWED_EXTENSIONS

# ═══════════════════════════════════════════════════════════
# ERROR HANDLERS
# ═══════════════════════════════════════════════════════════

@app.errorhandler(413)
def request_entity_too_large(error):
    """Handle file too large error"""
    return jsonify({'error': 'File too large. Maximum: 10 MB'}), 413

@app.errorhandler(404)
def not_found(error):
    """Handle 404 errors"""
    return jsonify({'error': 'Not found'}), 404

@app.errorhandler(500)
def internal_error(error):
    """Handle 500 errors"""
    return jsonify({'error': 'Internal server error'}), 500

# ═══════════════════════════════════════════════════════════
# RUN APPLICATION
# ═══════════════════════════════════════════════════════════

if __name__ == '__main__':
    app.run(
        debug=True,
        host='0.0.0.0',
        port=5000,
        threaded=True
    )