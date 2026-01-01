<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Office Location Configuration
    |--------------------------------------------------------------------------
    |
    | Konfigurasi lokasi kantor untuk validasi GPS attendance
    | Digunakan oleh sistem untuk memvalidasi apakah user berada
    | di dalam radius yang diizinkan saat melakukan check-in/check-out
    |
    */

    // Koordinat GPS kantor (titik pusat kompleks)
    'latitude' => -6.213877086806657,
    'longitude' => 106.60451640840081,
     

    // Radius maksimal yang diizinkan (dalam meter)
    // 200 meter cukup untuk cover semua bangunan dalam kompleks
    'radius' => 200,

    // Nama lokasi (untuk display/logging)
    'name' => 'Kompleks Kantor PC',

    /*
    |--------------------------------------------------------------------------
    | Building Names (Optional - untuk referensi)
    |--------------------------------------------------------------------------
    |
    | Daftar nama bangunan dalam kompleks
    | Ini hanya untuk dokumentasi, tidak digunakan dalam validasi GPS
    |
    */
    'buildings' => [
        'Gedung Serba Guna',
        'Masjid',
        'Kantor PC',
    ],

    /*
    |--------------------------------------------------------------------------
    | GPS Validation Settings
    |--------------------------------------------------------------------------
    */

    // Apakah validasi GPS aktif? (true/false)
    // Set false untuk disable GPS validation (testing/development)
    'gps_validation_enabled' => true,

    // Tolerance untuk GPS error (dalam meter)
    // GPS indoor bisa error 20-50 meter, ini sudah di-cover oleh radius
    'gps_error_tolerance' => 20,
];