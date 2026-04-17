<?php
include 'config/connection.php';
session_start();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms and Conditions | PharmAssist</title>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,200..800&display=swap" rel="stylesheet">
    <link rel="icon" href="website_icon/favicon.png" type="image/png" sizes="64x64">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Tinos', serif;
        }

        body {
            background: linear-gradient(135deg, #E8ECF1 0%, #B5CFD8 100%);
            font-family: "Bricolage Grotesque", sans-serif;
            color: #6C737E;
            line-height: 1.6;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 900px;
            margin: 40px auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(115, 147, 167, 0.2);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #7393A7 0%, #6C737E 100%);
            color: white;
            padding: 60px 40px;
            text-align: center;
        }

        .header h1 {
            font-size: 42px;
            font-weight: 700;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }

        .header p {
            font-size: 16px;
            opacity: 0.95;
        }

        .content {
            padding: 50px 40px;
        }

        .section {
            margin-bottom: 40px;
            padding: 30px;
            background: #E8ECF1;
            border-radius: 15px;
            border-left: 5px solid #7393A7;
            transition: transform 0.2s ease;
        }

        .section:hover {
            transform: translateX(5px);
        }

        .section h2 {
            color: #6C737E;
            font-size: 28px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #B5CFD8;
        }

        .section h3 {
            color: #7393A7;
            font-size: 22px;
            margin-bottom: 15px;
            margin-top: 25px;
        }

        .section p {
            margin-bottom: 15px;
            text-align: justify;
            color: #6C737E;
        }

        .section li {
            margin-bottom: 12px;
            margin-left: 25px;
            text-align: justify;
            color: #6C737E;
            position: relative;
            padding-left: 15px;
        }

        .section li::before {
            content: "▸";
            color: #7393A7;
            font-weight: bold;
            position: absolute;
            left: -5px;
        }

        .highlight-box {
            background: white;
            border: 2px solid #B5CFD8;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }

        .last-updated {
            display: inline-block;
            background: #B5CFD8;
            color: white;
            padding: 8px 20px;
            border-radius: 25px;
            font-size: 14px;
            margin-bottom: 20px;
        }

        strong {
            color: #6C737E;
            font-weight: 600;
        }

        a {
            color: #7393A7;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        a:hover {
            color: #6C737E;
            text-decoration: underline;
        }

        .divider {
            height: 3px;
            background: linear-gradient(90deg, #B5CFD8, #7393A7, #B5CFD8);
            border: none;
            margin: 30px 0;
            border-radius: 3px;
        }

        @media (max-width: 768px) {
            .header h1 {
                font-size: 32px;
            }
            
            .content {
                padding: 30px 20px;
            }
            
            .section {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>PharmAssist</h1>
            <p>Terms and Conditions</p>
        </div>

        <div class="content">
            <span class="last-updated">Last updated: October 20, 2025</span>
            <p style="margin-bottom: 30px;">Please read these terms and conditions carefully before using Our Service.</p>

            <div class="section">
                <h3>Account Creation and Registration</h3>
                <li>To use our medicine reservation platform, you must create an account by providing accurate, current, and complete information including your name, contact details, and prescription information where applicable.</li>
                <li>You are responsible for maintaining the confidentiality of your account credentials and all activities that occur under your account.</li>
                <li>You agree to comply with all applicable laws and regulations, including those related to healthcare, pharmacy services, and prescription medication when using our services.</li>
                <li>If you are under 18 years of age, you may only use our services with the consent of a parent or legal guardian. By using our services, you represent and warrant that you have obtained such consent.</li>
                <li>We may require users under 18 to provide proof of parental consent. If we are unable to verify parental consent, we may restrict or terminate your access to our services.</li>
            </div>

            <div class="section">
                <h3>Medicine Reservation Service</h3>
                <li>PharmAssist provides an online platform for reserving medicines at participating pharmacy branches. We facilitate reservations but do not directly dispense medications.</li>
                <li>All reservations are subject to availability and confirmation by the pharmacy. We provide real-time tracking of medicine availability, but cannot guarantee stock at all times.</li>
                <li>Prescription medications require a valid prescription from a licensed healthcare provider. You must present your prescription at the pharmacy when collecting reserved medicines.</li>
                <li>Reserved medicines must be collected within the timeframe specified by the pharmacy. Failure to collect within this period may result in cancellation of your reservation.</li>
                <li>You agree to provide accurate prescription and health information necessary for processing your medicine reservation.</li>
            </div>

            <div class="section">
                <h3>User Responsibilities and Conduct</h3>
                <li>You agree to use the platform only for legitimate healthcare and medicine reservation purposes.</li>
                <li>You must not misuse prescription information, share account credentials, or attempt to reserve medicines without proper authorization or valid prescriptions.</li>
                <li>You agree not to use the service to engage in any unlawful, harmful, or fraudulent activity, including but not limited to prescription fraud or medication abuse.</li>
                <li>You are responsible for verifying the accuracy of your reservation details, including medicine names, dosages, and pharmacy locations before confirmation.</li>
            </div>

            <div class="section">
                <h3>Privacy and Health Information</h3>
                <li>By using our service, you consent to our collection, use, and storage of your personal and health information as described in our Privacy Policy.</li>
                <li>We implement appropriate security measures to protect your sensitive health information and prescription data.</li>
                <li>Your health information will only be shared with participating pharmacies and healthcare providers as necessary to fulfill your reservations and provide our services.</li>
                <li>We comply with applicable healthcare privacy laws and regulations in the Philippines and internationally where our services are offered.</li>
            </div>

            <div class="section">
                <h3>Pharmacy Partner Relationships</h3>
                <li>PharmAssist partners with licensed pharmacies to provide medicine reservation and availability tracking services.</li>
                <li>We are not responsible for the quality, safety, or efficacy of medicines dispensed by partner pharmacies. All medication-related concerns should be directed to the dispensing pharmacy.</li>
                <li>Pharmacy partners maintain independent responsibility for medication dispensing, counseling, and compliance with pharmaceutical regulations.</li>
                <li>Availability information is provided by pharmacy partners and updated in real-time, but PharmAssist does not guarantee absolute accuracy due to rapidly changing inventory.</li>
            </div>

            <div class="section">
                <h3>Intellectual Property</h3>
                <li>All intellectual property rights, including copyrights, trademarks, patents, and proprietary technology in and to the PharmAssist platform and services are owned by us or our licensors.</li>
                <li>You agree not to use, reproduce, modify, adapt, publish, translate, create derivative works from, distribute, or display any content from the Services without our prior written consent.</li>
                <li>We grant you a limited, non-exclusive, non-transferable license to use our platform and services for personal, non-commercial healthcare purposes.</li>
                <li>The PharmAssist name, logo, and all related marks are trademarks owned by PharmAssist and may not be used without permission.</li>
            </div>

            <div class="section">
                <h3>Disclaimer of Medical Advice</h3>
                <li>PharmAssist is a medicine reservation platform and does not provide medical advice, diagnosis, or treatment recommendations.</li>
                <li>You should always consult with qualified healthcare professionals regarding your medical conditions, treatment options, and medication use.</li>
                <li>We do not endorse or recommend specific medications, and all medicine selection should be based on prescriptions from licensed healthcare providers.</li>
                <li>Emergency medical situations should be handled by calling emergency services or visiting the nearest hospital, not through our platform.</li>
            </div>

            <div class="section">
                <h3>Disclaimer of Warranties</h3>
                <li>The Services are provided "as is" without warranty of any kind, either express or implied, including, but not limited to, the implied warranties of merchantability, fitness for a particular purpose, and non-infringement.</li>
                <li>We do not warrant that the Services will be uninterrupted, timely, secure, or error-free, or that medicine availability information will always be accurate in real-time.</li>
                <li>We make no warranties regarding the quality, safety, or suitability of medicines provided by partner pharmacies.</li>
            </div>

            <div class="section">
                <h3>Limitation of Liability</h3>
                <li>In no event shall PharmAssist be liable for any indirect, incidental, special, consequential, or punitive damages, including but not limited to adverse medication reactions, prescription errors by pharmacies, or health complications arising from medicine use.</li>
                <li>To the maximum extent permitted by applicable law, PharmAssist shall not be liable for any damages resulting from your use or inability to use the service, delays in medicine availability, errors in reservation processing, or any health outcomes related to medications obtained through our platform.</li>
                <li>Our total liability for any claims related to the service shall not exceed the fees paid by you for the service in the twelve months preceding the claim.</li>
                <li>Some jurisdictions do not allow the exclusion or limitation of liability for certain damages, which means that some of the above limitations may not apply to you.</li>
            </div>

            <div class="section">
                <h3>"AS IS" and "AS AVAILABLE" Disclaimer</h3>
                <li>The Service is provided to You "AS IS" and "AS AVAILABLE" with all faults and defects without warranty of any kind. To the maximum extent permitted under applicable law, PharmAssist expressly disclaims all warranties, whether express, implied, statutory or otherwise.</li>
                <li>PharmAssist makes no representation or warranty that: (i) the Service will meet your healthcare needs or that medicine will always be available; (ii) the Service will be uninterrupted or error-free; (iii) real-time availability tracking will be completely accurate; or (iv) the Service will be free from technical issues or security vulnerabilities.</li>
                <li>You acknowledge that medicine availability can change rapidly and that reservations are subject to pharmacy confirmation and stock availability.</li>
            </div>

            <div class="section">
                <h3>Cancellation and Refund Policy</h3>
                <li>You may cancel a medicine reservation through the platform before pharmacy confirmation, subject to the cancellation policies of individual pharmacy partners.</li>
                <li>Once a pharmacy confirms your reservation and prepares your medicine, cancellation may not be possible or may be subject to fees as determined by the pharmacy.</li>
                <li>PharmAssist does not process payments for medicines; all transactions are handled directly with pharmacy partners according to their respective payment and refund policies.</li>
            </div>

            <div class="section">
                <h3>Governing Law</h3>
                <li>These Terms shall be governed by and construed in accordance with the laws of the Philippines, without regard to its conflict of law provisions.</li>
                <li>Your use of the Service may also be subject to other local, national, or international laws, particularly those relating to healthcare, pharmacy services, and prescription medications.</li>
            </div>

            <div class="section">
                <h3>Dispute Resolution</h3>
                <li>Any dispute arising out of or in connection with these Terms shall be resolved first through good-faith negotiations between the parties.</li>
                <li>If negotiations fail, disputes shall be resolved through arbitration or mediation in accordance with Philippine law.</li>
            </div>

            <div class="section">
                <h3>Modifications to Terms</h3>
                <li>We reserve the right to modify or replace these Terms at any time. If a revision is material, we will make reasonable efforts to provide at least 30 days' notice prior to any new terms taking effect.</li>
                <li>By continuing to access or use our Service after revisions become effective, you agree to be bound by the revised terms. If you do not agree to the new terms, please stop using the platform.</li>
                <li>We may also update our services, features, or policies to improve medicine accessibility and user experience.</li>
            </div>

            <hr class="divider">

            <div class="section">
                <h2>Interpretation and Definitions</h2>
                
                <h3>Interpretation</h3>
                <p>The words of which the initial letter is capitalized have meanings defined under the following conditions. The following definitions shall have the same meaning regardless of whether they appear in singular or in plural.</p>

                <h3>Definitions</h3>
                <p>For the purposes of these Terms and Conditions:</p>
                <li><strong>Affiliate</strong> means an entity that controls, is controlled by or is under common control with a party, where "control" means ownership of 50% or more of the shares, equity interest or other securities entitled to vote for election of directors or other managing authority.</li>
                <li><strong>Country</strong> refers to: Philippines</li>
                <li><strong>Company</strong> (referred to as either "the Company", "PharmAssist", "We", "Us" or "Our" in this Agreement) refers to PharmAssist, a medicine reservation platform.</li>
                <li><strong>Device</strong> means any device that can access the Service such as a computer, cellphone, or digital tablet.</li>
                <li><strong>Medicine</strong> refers to pharmaceutical products, prescription medications, over-the-counter drugs, and health products available through our platform.</li>
                <li><strong>Partner Pharmacy</strong> refers to licensed pharmacies that participate in the PharmAssist platform and fulfill medicine reservations.</li>
                <li><strong>Prescription</strong> refers to a valid written or electronic order for medicine issued by a licensed healthcare provider.</li>
                <li><strong>Reservation</strong> refers to the process of securing medicine availability at a Partner Pharmacy through the PharmAssist platform.</li>
                <li><strong>Service</strong> refers to the PharmAssist website and mobile platform, including medicine reservation, availability tracking, and communication features.</li>
                <li><strong>Terms and Conditions</strong> (also referred to as "Terms") mean these Terms and Conditions that form the entire agreement between you and PharmAssist regarding the use of the Service.</li>
                <li><strong>Website</strong> refers to PharmAssist, accessible online through our web and mobile platforms.</li>
                <li><strong>You</strong> means the individual accessing or using the Service, or the company, or other legal entity on behalf of which such individual is accessing or using the Service, as applicable.</li>
            </div>

            <div class="section">
                <h2>Acknowledgement</h2>
                <p>These are the Terms and Conditions governing the use of the PharmAssist medicine reservation platform and the agreement that operates between You and the Company. These Terms and Conditions set out the rights and obligations of all users regarding the use of the Service.</p>
                <p>Your access to and use of the Service is conditioned on your acceptance of and compliance with these Terms and Conditions. These Terms and Conditions apply to all visitors, users, patients, and healthcare consumers who access or use the Service.</p>
                <p>By accessing or using the Service, you agree to be bound by these Terms and Conditions. If you disagree with any part of these Terms and Conditions, then you may not access the Service.</p>
                <p>Your access to and use of the Service is also conditioned on your acceptance of and compliance with the Privacy Policy of PharmAssist. Our Privacy Policy describes our policies and procedures on the collection, use, and disclosure of your personal and health information when you use the platform, and tells you about your privacy rights and how the law protects you. Please read our Privacy Policy carefully before using our Service.</p>
            </div>

            <div class="section">
                <h2>Links to Other Websites</h2>
                <p>Our Service may contain links to third-party websites or services, including partner pharmacy websites, healthcare resources, or payment processors that are not owned or controlled by PharmAssist.</p>
                <p>PharmAssist has no control over, and assumes no responsibility for, the content, privacy policies, or practices of any third-party websites or services. You further acknowledge and agree that PharmAssist shall not be responsible or liable, directly or indirectly, for any damage or loss caused or alleged to be caused by or in connection with the use of or reliance on any such content, goods, or services available on or through any such websites or services.</p>
                <p>We strongly advise you to read the terms and conditions and privacy policies of any third-party websites or services that you visit, particularly those of partner pharmacies.</p>
            </div>

            <div class="section">
                <h2>Termination</h2>
                <p>We may terminate or suspend your access immediately, without prior notice or liability, for any reason whatsoever, including without limitation if you breach these Terms and Conditions, misuse prescription information, or engage in fraudulent activity.</p>
                <p>Upon termination, your right to use the Service will cease immediately, and any pending reservations may be cancelled.</p>
                <p>You may terminate your account at any time by contacting our support team. Upon termination, we will retain your data as required by applicable healthcare and pharmacy regulations.</p>
            </div>

            <div class="section">
                <h2>Severability and Waiver</h2>
                
                <h3>Severability</h3>
                <p>If any provision of these Terms is held to be unenforceable or invalid, such provision will be changed and interpreted to accomplish the objectives of such provision to the greatest extent possible under applicable law, and the remaining provisions will continue in full force and effect.</p>
                
                <h3>Waiver</h3>
                <p>Except as provided herein, the failure to exercise a right or to require performance of an obligation under these Terms shall not affect a party's ability to exercise such right or require such performance at any time thereafter, nor shall the waiver of a breach constitute a waiver of any subsequent breach.</p>
            </div>

            <div class="section">
                <h2>Translation Interpretation</h2>
                <p>These Terms and Conditions may have been translated if we have made them available to you on our Service. You agree that the original English text shall prevail in the case of a dispute.</p>
            </div>

            <div class="section">
                <h2>Contact Information</h2>
                <p>If you have any questions about these Terms and Conditions, please contact us through the PharmAssist platform or visit our support center for assistance.</p>
                <p>For medical emergencies, please call emergency services or visit the nearest hospital immediately.</p>
            </div>
        </div>
    </div>
</body>
</html>