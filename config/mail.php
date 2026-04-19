<?php
return [
  'host'       => getenv('SMTP_HOST')     ?: 'smtp-relay.brevo.com',  
  'username'   => getenv('SMTP_USERNAME') ?: '',   
  'password'   => getenv('SMTP_PASSWORD') ?: '', 
  'port'       => 587,
  'encryption' => 'smtps',
  'from_email' => getenv('SMTP_USERNAME') ?: 'a.pharmasee@gmail.com',
  'from_name'  => 'PharmaSee Support',
  'reply_to'   => getenv('SMTP_USERNAME') ?: 'a.pharmasee@gmail.com', 
];
