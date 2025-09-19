<?php
// SMTP configuration for email notifications
return [
    'host' => 'smtp.example.com',
    'port' => 587,
    'username' => 'user@example.com',
    'password' => 'yourpassword',
    'from_email' => 'noreply@example.com',
    'from_name' => 'Grievance System',
    'encryption' => 'tls' // or 'ssl'
];
