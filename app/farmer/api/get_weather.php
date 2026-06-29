<?php
// weather/api.php

// 1. Start Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Output Buffering for clean JSON
ob_start();

// 3. Headers
header('Content-Type: application/json');

// 4. Config
require_once __DIR__ . '/../../config/db.php'; // adjust path
$api_key = '21480b77eb33ed197e62b8e6a1422a57';
$default_city = 'Kampala, Uganda';
$city = trim($_SESSION['location'] ?? $default_city);
if ($city === '') $city = $default_city;

// 5. Helper function
function get_api_data($url) {
    $response = @file_get_contents($url);
    if (!$response) return null;
    $data = json_decode($response, true);
    return json_last_error() === JSON_ERROR_NONE ? $data : null;
}

// 6. Geocoding: City → Lat/Lon
$lat = 0.3136; $lon = 32.5811; $display_location = 'Kampala, UG';
$geo_url = "http://api.openweathermap.org/geo/1.0/direct?q=" . urlencode($city) . "&limit=1&appid=$api_key";
$geo_data = get_api_data($geo_url);
if ($geo_data && !empty($geo_data[0]['lat'])) {
    $lat = $geo_data[0]['lat'];
    $lon = $geo_data[0]['lon'];
    $display_location = $geo_data[0]['name'] . ', ' . $geo_data[0]['country'];
}

// 7. Current Weather
$current_url = "https://api.openweathermap.org/data/2.5/weather?lat={$lat}&lon={$lon}&units=metric&appid=$api_key";
$current = get_api_data($current_url);
if (!$current || (isset($current['cod']) && $current['cod'] != 200)) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch weather data']);
    exit;
}

// 8. Forecast (3-day, around 12:00)
$forecast_url = "https://api.openweathermap.org/data/2.5/forecast?lat={$lat}&lon={$lon}&units=metric&appid=$api_key";
$forecast = get_api_data($forecast_url);

$rain_chance = 0;
$forecast_days = [];
if ($forecast && !empty($forecast['list'])) {
    $added_days = [];
    foreach ($forecast['list'] as $f) {
        $date = date('Y-m-d', $f['dt']);
        if (count($added_days) >= 3) break;
        if (strpos($f['dt_txt'], '12:00:00') !== false && !in_array($date, $added_days)) {
            $forecast_days[] = [
                'date' => $date,
                'dayName' => date('l', strtotime($date)),
                'temp_max' => round($f['main']['temp_max']),
                'temp_min' => round($f['main']['temp_min']),
                'description' => $f['weather'][0]['description'] ?? 'Unknown',
                'icon' => $f['weather'][0]['icon'] ?? '01d',
            ];
            $added_days[] = $date;
            $rain_chance = max($rain_chance, round($f['pop'] * 100));
        }
    }
}

// 9. Fake UV (for demo)
$fake_uv = min(10, max(0, round($current['main']['temp'] / 5)));

// 10. Time & Date
$current_time = date('H:i');
$current_date = date('F j, Y');

// 11. Build Response
$response = [
    'location'    => trim($display_location),
    'temp'        => round($current['main']['temp']),
    'feels_like'  => round($current['main']['feels_like']),
    'humidity'    => $current['main']['humidity'],
    'wind_speed'  => round($current['wind']['speed'] * 3.6), // km/h
    'uv'          => $fake_uv,
    'description' => $current['weather'][0]['description'] ?? 'Unknown',
    'icon'        => $current['weather'][0]['icon'] ?? '01d',
    'rain_chance' => $rain_chance,
    'time'        => $current_time,
    'date'        => $current_date,
    'forecast'    => $forecast_days,
];

// 12. Output JSON
ob_end_clean();
echo json_encode($response);
exit;