<style>
  .home-sections {
    position: relative;
    left: 250px;
    width: calc(100% - 250px);
    transition: all 0.5s ease;
  }

  .sidebar.close ~ .home-sections {
    left: 78px;
    width: calc(100% - 78px);
  }

  .site-footer .footer-columns {
    display: flex;
    flex-wrap: wrap;
    gap: 30px;
  }

  .site-footer .footer-columns > div {
    flex: 1 1 calc(25% - 23px);
    min-width: 200px;
    margin-bottom: 30px;
  }

  @media (max-width: 991px) {
    .site-footer .footer-columns > div {
      flex: 1 1 calc(50% - 15px);
    }
  }

  @media (max-width: 767px) {
    .home-sections {
      left: 0 !important;
      width: 100% !important;
    }

    .site-footer .footer-columns {
      flex-direction: column;
      gap: 20px;
    }

    .site-footer .footer-columns > div {
      flex: 1 1 100%;
      width: 100%;
    }

    .site-footer .row .col-md-8,
    .site-footer .row .col-md-4 {
      width: 100%;
      text-align: center;
    }

    .site-footer .social-icons {
      justify-content: center;
      margin-top: 15px !important;
    }
  }

  .site-footer .footer-bottom {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
  }

  .site-footer .copyright-text {
    margin: 0;
  }

  .site-footer .social-icons {
    margin: 0;
    display: flex;
    list-style: none;
    padding: 0;
  }

  @media (max-width: 767px) {
    .site-footer .footer-bottom {
      flex-direction: column;
      text-align: center;
    }

    .site-footer .copyright-text {
      margin-bottom: 10px !important;
    }

    .site-footer .social-icons {
      justify-content: center;
    }
  }
</style>

<section class="home-sections">

<footer class="site-footer">
  <div class="container">
    <div class="row footer-columns">
      <div>
        <h6>About PharmAssist</h6>
        <p class="text-justify">PharmAssist is your trusted online medicine reservation system. We provide a seamless platform that allows customers to track medicine availability in real time across multiple pharmacy branches.</p>
      </div>

      <div>
        <h6>Explore</h6>
        <ul class="footer-links">
          <li><a href="homepage.php">Home</a></li>
          <li><a href="guide.php">Guide</a></li>
          <li><a href="medicines.php">Medicine List</a></li>
          <li><a href="user_notif.php">Notifications</a></li>
          <li><a href="vision_mission.php">Mission & Vision</a></li>
        </ul>
      </div>

      <div>
        <h6>Legal</h6>
        <ul class="footer-links">
          <li><a href="terms.php">Terms & Conditions</a></li>
          <li><a href="privacy.php">Privacy Policy</a></li>
        </ul>
      </div>

      <div>
        <h6>Contact Us</h6>
        <ul class="footer-links">
          <li>Email: a.pharmasee@gmail.com</li>
          <li>Phone: (+63) 912 345 6891</li>
          <li>Facebook: PharmAssist</li>
          <li>Instagram: @a.PharmAssist</li>
        </ul>
      </div>
    </div>
    <hr>
  </div>
  <div class="container">
    <div class="row">
      <div class="col-md-12">
        <div class="footer-bottom">
          <p class="copyright-text">Copyright &copy; 2025 All Rights Reserved by 
           <a href="#" class="logo">PharmAssist</a>.
          </p>
          
          <ul class="social-icons">
            <li><a class="facebook" href="https://www.facebook.com/share/17aZcKFSH6/?mibextid=wwXIfr"><i class="bi bi-facebook"></i></a></li>
            <li><a class="instagram" href="https://www.facebook.com/share/17aZcKFSH6/?mibextid=wwXIfr"><i class="bi bi-instagram"></i></a></li> 
          </ul>
        </div>
      </div>
    </div>
  </div>
</footer>

</section>