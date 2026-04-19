<?php
return [
  'host'       => getenv('SMTP_HOST')     ?: 'smtp-relay.brevo.com',  
  'username'   => getenv('SMTP_USERNAME') ?: 'a897b8001@smtp-brevo.com',   
  'password'   => getenv('SMTP_PASSWORD') ?: 'xsmtpsib-08d47a4c0887b0e7c93e61264a827e1d41707c148738565960e82a34f5bc62db-L2wEhL7VmYoxsjRI', 
  'port'       => 587,
  'encryption' => 'smtps',
  'from_email' => getenv('SMTP_USERNAME') ?: 'a.pharmasee@gmail.com',
  'from_name'  => 'PharmaSee Support',
  'reply_to'   => getenv('SMTP_USERNAME') ?: 'a.pharmasee@gmail.com', 
];
