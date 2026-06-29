<?php
// api/get_weather.php
header('Content-Type: application/json');
// header('Access-Control-Allow-Origin: *');   // ← uncomment only if needed; better to set specific origin

require_once '../config/db.php';   // gets $conn

session_start();

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$api_key = '21480b77eb33ed197e62b8e6a1422a57';  // ← move to config later!

// Get user's location from session (after you apply Option A above)
$city = $_SESSION['locatiion'] ?? 'Kampala, Uganda';

// 1. Geocode city → lat/lon
$geo_url = "http://api.openweathermap.org/geo/1.0/direct?q=" . urlencode($city) . "&limit=1&appid=" . $api_key;
$geo_response = @file_get_contents($geo_url);

if ($geo_response === false) {
    // fallback
    $lat = 0.3136;
    $lon = 32.5811;
    $display_location = 'Kampala, UG';
} else {
    $geo_data = json_decode($geo_response, true);
    if (!empty($geo_data[0]['lat'])) {
        $lat = $geo_data[0]['lat'];
        $lon = $geo_data[0]['lon'];
        $display_location = $geo_data[0]['name'] . ', ' . $geo_data[0]['country'];
    } else {
        $lat = 0.3136;
        $lon = 32.5811;
        $display_location = 'Kampala, UG';
    }
}

// 2. Current weather
$current_url = "https://api.openweathermap.org/data/2.5/weather?lat={$lat}&lon={$lon}&units=metric&appid=" . $api_key;
$current_response = @file_get_contents($current_url);
$current = json_decode($current_response, true);

if (json_last_error() !== JSON_ERROR_NONE || (isset($current['cod']) && $current['cod'] != 200)) {
    http_response_code(500);
    echo json_encode(['error' => $current['message'] ?? 'Current weather failed']);
    exit;
}

// 3. Forecast (for rain chance)
$forecast_url = "https://api.openweathermap.org/data/2.5/forecast?lat={$lat}&lon={$lon}&units=metric&appid=" . $api_key;
$forecast_response = @file_get_contents($forecast_url);
$forecast = json_decode($forecast_response, true);

$rain_chance = 0;
if (!empty($forecast['list'][0]['pop'])) {
    $rain_chance = round($forecast['list'][0]['pop'] * 100);
}

// Fake UV (as in your original JS)
$fake_uv = min(10, max(0, round($current['main']['temp'] / 5)));

// Response
$response = [
    'location'     => trim($display_location),
    'temp'         => round($current['main']['temp']),
    'feels_like'   => round($current['main']['feels_like']),
    'humidity'     => $current['main']['humidity'],
    'wind_speed'   => round($current['wind']['speed'] * 3.6), // km/h
    'uv'           => $fake_uv,
    'description'  => $current['weather'][0]['description'] ?? 'Unknown',
    'icon'         => $current['weather'][0]['icon'] ?? '01d',
    'rain_chance'  => $rain_chance,
];

echo json_encode($response);