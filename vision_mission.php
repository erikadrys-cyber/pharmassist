<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Vision & Mission | PharmAssist</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
  <!-- Icons + Bootstrap -->
  <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,200..800&family=Tinos:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
  <link rel="icon" href="website_icon/favicon.png" type="image/png" sizes="64x64">
  <style>
    body {
      background-color: #f8f9fa;
      font-family: 'tinos', sans-serif;
      color: #212529;
    }

    .hero-section {
    position: relative;
    background: linear-gradient(90deg, #7393A7 0%, #A7C7E7 100%);
    background-size: cover;
    height: 250px;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    color: #7393A7;
    box-shadow: inset 0 0 50px white;
}

h1, h3 {
  font-family: 'Bricolage Grotesque', sans-serif;
  color: #7393A7 !important;
}

.hero-overlay {
    background: white;
    opacity: 0.7;
    padding: 20px;
    border-radius: 10px;
}

    .divider {
      width: 80px;
      height: 4px;
      background-color: #43a047;
      margin: 20px auto 30px;
      border-radius: 2px;
    }

    .card {
      border: none;
      border-radius: 15px;
      box-shadow: 0 6px 15px rgba(0,0,0,0.1);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      background-color: #fff;
      height: 100%;
    }

    .card:hover {
      transform: translateY(-6px);
      box-shadow: 0 10px 20px rgba(0,0,0,0.15);
    }

    .card-body {
      padding: 40px;
      text-align: center;
    }

    .card-body img {
      width: 100px;
      height: 100px;
      margin-bottom: 20px;
      object-fit: contain;
    }

    .card-body h3 {
      font-weight: 700;
      margin-bottom: 15px;
    }

    .card-body p, .card-body ul {
      text-align: justify;
      font-size: 0.95rem;
    }

    ul li {
      margin-bottom: 8px;
    }

    @media (max-width: 991px) {
      .card-body {
        padding: 30px 20px;
      }
    }
  </style>
</head>
<body>

  <!-- Hero Section -->
  <header class="hero-section">
    <div class="hero-overlay">
      <h1>Welcome to PharmAssist</h1>
        <p>Vision & Mission</p>
    </div>
  </header>

  <!-- Vision & Mission Cards -->
  <section class="py-5">
    <div class="container">
      <div class="row justify-content-center g-4" data-aos="fade-up">
        <!-- Mission Card -->
        <div class="col-md-6 col-lg-5 d-flex">
          <div class="card flex-fill">
            <div class="card-body">
              <img src="img/mission_icon.png" alt="Mission Icon">
              <h3>Our Mission</h3>
              <p>
                To empower individuals and communities by bridging the gap between pharmacies and patients through innovation.
                We commit to:
              </p>
              <ul class="text-start">
                <li><strong>Accessibility:</strong> Make healthcare products available anytime, anywhere.</li>
                <li><strong>Trust:</strong> Work only with verified suppliers and licensed pharmacists.</li>
                <li><strong>Affordability:</strong> Offer fair prices without compromising quality.</li>
                <li><strong>Innovation:</strong> Simplify medicine ordering, tracking, and delivery.</li>
                <li><strong>Wellness Advocacy:</strong> Promote a healthy lifestyle and responsible medication use.</li>
              </ul>
            </div>
          </div>
        </div>

        <!-- Vision Card -->
        <div class="col-md-6 col-lg-5 d-flex">
          <div class="card flex-fill">
            <div class="card-body">
              <img src="img/vision_icon.png" alt="Vision Icon">
              <h3>Our Vision</h3>
              <p>
                To redefine digital healthcare by creating a safe, reliable, and accessible online pharmacy ecosystem.
                We envision a future where medicine and wellness are just a click away—affordable, authentic, and always
                within reach for every Filipino family.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
  <script>
    AOS.init({
      duration: 1000,
      once: true
    });
  </script>
</body>
</html>
