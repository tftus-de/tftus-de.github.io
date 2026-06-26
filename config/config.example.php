<?php
// Copy this file to config.php and fill in real values. config.php must never be committed.
return [
    'db' => [
        'host' => '127.0.0.1',
        'name' => 'giic_cms',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],
    'mail' => [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'username' => 'info@tftus.com',
        'password' => '', // Google Workspace App Password (not the regular login password)
        'from_email' => 'info@tftus.com',
        'from_name' => 'TFT Deutschland Website',
        'to_email' => 'info@tftus.com', // where lead notifications are delivered
    ],
];
