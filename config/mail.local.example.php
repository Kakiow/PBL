<?php

// Salin file ini menjadi config/mail.local.php, lalu isi nilainya.
// File mail.local.php diabaikan Git (lihat pola *.local.php pada .gitignore).
return [
    // API key Brevo: dashboard Brevo -> SMTP & API -> API Keys.
    // Beri akses pengiriman email transaksional.
    'brevo_api_key' => 'xkeysib-...',

    // Email pengirim yang sudah diverifikasi di Brevo.
    'from_email' => 'email-terverifikasi@domainkamu.com',
    'from_name' => 'SIMKAS',

    // URL aplikasi untuk membentuk link reset. Tanpa trailing slash.
    'app_url' => 'https://domainkamu.com',
];
