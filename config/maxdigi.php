<?php

// config/maxdigi.php

return [
    /*
    |--------------------------------------------------------------------------
    | Kredensial API DigiFlazz
    |--------------------------------------------------------------------------
    |
    | Di sini Anda bisa menempatkan kredensial untuk API DigiFlazz.
    | Sangat disarankan untuk menggunakan variabel environment (.env)
    | untuk menyimpan informasi sensitif ini.
    |
    */
    'username' => env('DIGIFLAZZ_USERNAME'),
    
    'api_key' => env('DIGIFLAZZ_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Kunci Rahasia Webhook (Webhook Secret Key)
    |--------------------------------------------------------------------------
    |
    | Kunci rahasia ini digunakan untuk memverifikasi bahwa permintaan webhook
    | yang masuk benar-benar berasal dari DigiFlazz.
    | Pastikan nilai ini sama dengan yang Anda atur di dashboard DigiFlazz.
    |
    */
    'webhook_secret' => env('DIGIFLAZZ_WEBHOOK_SECRET'),
];
