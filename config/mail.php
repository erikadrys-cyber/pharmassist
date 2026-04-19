<?php
return [
  'host'       => getenv('MAIL_HOST')     ?: 'smtp.gmail.com',  
  'username'   => getenv('MAIL_USERNAME') ?: 'a.pharmasee@gmail.com',   
  'password'   => getenv('MAIL_PASSWORD') ?: 'ujct nsjw ptzq ahnk', 
  'port'       => 587,
  'encryption' => 'tls',
  'from_email' => getenv('MAIL_USERNAME') ?: 'a.pharmasee@gmail.com',
  'from_name'  => 'PharmaSee Support',
  'reply_to'   => getenv('MAIL_USERNAME') ?: 'a.pharmasee@gmail.com', 
];
