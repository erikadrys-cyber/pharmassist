<?php 
include 'config/connection.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forgot Password | PharmAssist</title>
  <link rel="icon" href="website_icon/favicon.png" type="image/png" sizes="64x64">
  <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
      rel="stylesheet"
      integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
      crossorigin="anonymous"
  />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="plugins/forgot_password.css" />
  <link rel="icon" href="website_icon/favicon.png" type="image/png" sizes="64x64">
</head>
<body style="background-image: url('img/loginbg.jpg');">
  <div class="container-fluid p-0 m-0 vh-100 d-flex justify-content-center align-items-center">
    <form action="send_reset_link.php" method="POST" class="bg-body-tertiary px-5 py-5 rounded-4 d-flex flex-column">
        <div class="mb-3 text-center">
           <h2>Forgot Password</h2>
           <label for="email">Enter your email to reset password</label>
        </div>
        <input type="email" class="form-control" name="email" id="email" autocomplete="off" placeholder="Email" required>
        <button type="submit" class="btn btn-primary mt-3">Send Reset Link</button>
        <a href="login.php" class="text-center mt-3" target="_self">Back to login</a>
    </form>
  </div>
</body>
</html>