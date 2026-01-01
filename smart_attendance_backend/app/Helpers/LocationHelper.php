<?php

namespace App\Helpers;

/**
 * LocationHelper
 * 
 * Helper class untuk operasi GPS dan validasi lokasi
 * Menggunakan Haversine Formula untuk menghitung jarak antara 2 koordinat
 */
class LocationHelper
{
    /**
     * Radius bumi dalam meter
     * Digunakan dalam perhitungan Haversine
     */
    const EARTH_RADIUS_METERS = 6371000;

    /**
     * Hitung jarak antara 2 koordinat GPS menggunakan Haversine Formula
     * 
     * @param float $lat1 Latitude titik 1 (user)
     * @param float $lon1 Longitude titik 1 (user)
     * @param float $lat2 Latitude titik 2 (kantor)
     * @param float $lon2 Longitude titik 2 (kantor)
     * @return float Jarak dalam meter
     * 
     * @example
     * $distance = LocationHelper::calculateDistance(
     *     -6.213725, 106.604024,  // User location
     *     -6.213700, 106.604000   // Office location
     * );
     * // Output: 35.7 (meter)
     */
    public static function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        // Convert degrees to radians
        $lat1 = deg2rad($lat1);
        $lon1 = deg2rad($lon1);
        $lat2 = deg2rad($lat2);
        $lon2 = deg2rad($lon2);

        // Haversine Formula
        $deltaLat = $lat2 - $lat1;
        $deltaLon = $lon2 - $lon1;

        $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
             cos($lat1) * cos($lat2) *
             sin($deltaLon / 2) * sin($deltaLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        // Jarak dalam meter
        $distance = self::EARTH_RADIUS_METERS * $c;

        return round($distance, 2); // Dibulatkan 2 desimal
    }

    /**
     * Validasi apakah user berada dalam radius yang diizinkan dari kantor
     * 
     * @param float $userLat Latitude user
     * @param float $userLon Longitude user
     * @param float|null $officeLat Latitude kantor (default dari config)
     * @param float|null $officeLon Longitude kantor (default dari config)
     * @param int|null $maxRadius Radius maksimal dalam meter (default dari config)
     * @return array [
     *     'valid' => bool,
     *     'distance' => float,
     *     'max_radius' => int,
     *     'office_location' => array
     * ]
     * 
     * @example
     * $result = LocationHelper::validateLocation(-6.213725, 106.604024);
     * if ($result['valid']) {
     *     echo "User dalam radius: " . $result['distance'] . "m";
     * } else {
     *     echo "User di luar radius! Jarak: " . $result['distance'] . "m";
     * }
     */
    public static function validateLocation(
        $userLat, 
        $userLon, 
        $officeLat = null, 
        $officeLon = null, 
        $maxRadius = null
    ) {
        // Ambil dari config jika tidak di-provide
        $officeLat = $officeLat ?? config('office.latitude');
        $officeLon = $officeLon ?? config('office.longitude');
        $maxRadius = $maxRadius ?? config('office.radius', 200);

        // Hitung jarak
        $distance = self::calculateDistance(
            $userLat, 
            $userLon, 
            $officeLat, 
            $officeLon
        );

        // Validasi
        $isValid = $distance <= $maxRadius;

        return [
            'valid' => $isValid,
            'distance' => $distance,
            'max_radius' => $maxRadius,
            'office_location' => [
                'latitude' => $officeLat,
                'longitude' => $officeLon,
                'name' => config('office.name', 'Kantor'),
            ],
            'user_location' => [
                'latitude' => $userLat,
                'longitude' => $userLon,
            ],
        ];
    }

    /**
     * Validasi koordinat GPS apakah valid
     * 
     * @param float $lat Latitude
     * @param float $lon Longitude
     * @return bool
     */
    public static function isValidCoordinate($lat, $lon)
    {
        // Latitude: -90 to 90
        // Longitude: -180 to 180
        return ($lat >= -90 && $lat <= 90) && 
               ($lon >= -180 && $lon <= 180);
    }

    /**
     * Format koordinat GPS untuk display
     * 
     * @param float $lat Latitude
     * @param float $lon Longitude
     * @param int $precision Jumlah digit desimal (default: 6)
     * @return string
     * 
     * @example
     * echo LocationHelper::formatCoordinate(-6.213725, 106.604024);
     * // Output: "-6.213725, 106.604024"
     */
    public static function formatCoordinate($lat, $lon, $precision = 6)
    {
        return sprintf(
            '%s, %s',
            number_format($lat, $precision),
            number_format($lon, $precision)
        );
    }

    /**
     * Format jarak untuk display human-readable
     * 
     * @param float $meters Jarak dalam meter
     * @return string
     * 
     * @example
     * echo LocationHelper::formatDistance(1500);
     * // Output: "1.5 km"
     * 
     * echo LocationHelper::formatDistance(250);
     * // Output: "250 m"
     */
    public static function formatDistance($meters)
    {
        if ($meters >= 1000) {
            $km = $meters / 1000;
            return number_format($km, 1) . ' km';
        }
        
        return round($meters) . ' m';
    }

    /**
     * Generate Google Maps URL dari koordinat
     * 
     * @param float $lat Latitude
     * @param float $lon Longitude
     * @return string
     * 
     * @example
     * $url = LocationHelper::getGoogleMapsUrl(-6.213725, 106.604024);
     * // Output: "https://www.google.com/maps?q=-6.213725,106.604024"
     */
    public static function getGoogleMapsUrl($lat, $lon)
    {
        return sprintf(
            'https://www.google.com/maps?q=%s,%s',
            $lat,
            $lon
        );
    }

    /**
     * Cek apakah GPS validation sedang aktif
     * 
     * @return bool
     */
    public static function isGpsValidationEnabled()
    {
        return config('office.gps_validation_enabled', true);
    }

    /**
     * Get informasi office location dari config
     * 
     * @return array
     */
    public static function getOfficeLocation()
    {
        return [
            'latitude' => config('office.latitude'),
            'longitude' => config('office.longitude'),
            'radius' => config('office.radius', 200),
            'name' => config('office.name', 'Kantor'),
            'buildings' => config('office.buildings', []),
        ];
    }
}