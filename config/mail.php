<?php

$mail_config = [
    'brevo_api_key' => getenv('BREVO_API_KEY') ?: '',
    'from_email' => getenv('MAIL_FROM_EMAIL') ?: '',
    'from_name' => getenv('MAIL_FROM_NAME') ?: 'SIMKAS',
    'app_url' => getenv('APP_URL') ?: '',
];

$file_lokal = __DIR__ . '/mail.local.php';

if (is_file($file_lokal)) {
    $nilai_lokal = require $file_lokal;

    if (is_array($nilai_lokal)) {
        foreach ($nilai_lokal as $kunci => $nilai) {
            if ($nilai !== null && $nilai !== '') {
                $mail_config[$kunci] = $nilai;
            }
        }
    }
}

return $mail_config;
