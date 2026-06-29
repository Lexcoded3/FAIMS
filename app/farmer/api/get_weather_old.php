<?php
// 1. Start Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Now continue with geocoding using $user_city ...

// 2. Output Buffering (Crucial for clean JSON)
ob_start();

// 3. Error Settings (Hide errors from JSON output)
// ini_set('display_errors', 0);
// ini_set('log_errors', 1);
// error_reporting(E_ALL);

// 4. Headers
header('Content-Type: application/json');

// 5. Dependencies
// Adjust the path based on your folder structure.
// If this file is in /api/ and db.php is in /config/, use ../config/db.php
require_once __DIR__ . '../../../config/db.php'; 

// --- CONFIGURATION ---
 $api_key = '21480b77eb33ed197e62b8e6a1422a57'; 

// Default location (used if user is not logged in)
 // $city = 'Kampala, Uganda'; 

// --- FETCH USER LOCATION FROM DB (MySQLi Style) ---
// CONFIG
$default_city = 'Kampala, Uganda';

// Use session location if available, else default
$user_city = $_SESSION['location'] ?? $default_city;

// Make sure it's not empty/whitespace
$user_city = trim($user_city);
if ($user_city === '') {
    $user_city = $default_city;
}

$city = $user_city;  // proceed to geocoding with this

// Debug to confirm
file_put_contents(
    __DIR__ . '/location-debug.log',
    date('c') . " | SESSION[location] = " . ($_SESSION['location'] ?? 'not set') . "\n" .
                " | Final city used   = $city\n" .
                "------------------------\n",
    FILE_APPEND
);
// --- HELPER FUNCTION ---
function get_api_data($url) {
    $response = @file_get_contents($url);
    if ($response === false) return null;
    $data = json_decode($response, true);
    return (json_last_error() === JSON_ERROR_NONE) ? $data : null;
}

// --- GEOCODING (City → Lat/Lon) ---
 $lat = 0.3136;
 $lon = 32.5811;
 $display_location = 'Kampala, UG';

 $geo_url = "http://api.openweathermap.org/geo/1.0/direct?q=" . urlencode($city) . "&limit=1&appid=" . $api_key;
 $geo_data = get_api_data($geo_url);

if ($geo_data && !empty($geo_data[0]['lat'])) {
    $lat = $geo_data[0]['lat'];
    $lon = $geo_data[0]['lon'];
    $display_location = $geo_data[0]['name'] . ', ' . $geo_data[0]['country'];
}

// --- CURRENT WEATHER ---
 $current_url = "https://api.openweathermap.org/data/2.5/weather?lat={$lat}&lon={$lon}&units=metric&appid=" . $api_key;
 $current = get_api_data($current_url);

if (!$current || (isset($current['cod']) && $current['cod'] != 200)) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch weather data']);
    exit;
}

// --- FORECAST (Rain Chance) ---
 $forecast_url = "https://api.openweathermap.org/data/2.5/forecast?lat={$lat}&lon={$lon}&units=metric&appid=" . $api_key;
 $forecast = get_api_data($forecast_url);

 $rain_chance = 0;
if ($forecast && !empty($forecast['list'][0]['pop'])) {
    $rain_chance = round($forecast['list'][0]['pop'] * 100);
}
$forecast_days = [];
if ($forecast && !empty($forecast['list'])) {
    $added_days = [];
    foreach ($forecast['list'] as $f) {
        $date = date('Y-m-d', $f['dt']);
        if (count($added_days) >= 3) break;
        // Pick forecast around 12:00
        if (strpos($f['dt_txt'], '12:00:00') !== false && !in_array($date, $added_days)) {
            $forecast_days[] = [
                'date' => $date,
                'dayName' => date('l', strtotime($date)), // e.g., Monday
                'temp_max' => round($f['main']['temp_max']),
                'temp_min' => round($f['main']['temp_min']),
                'description' => $f['weather'][0]['description'] ?? 'Unknown',
                'icon' => $f['weather'][0]['icon'] ?? '01d',
            ];
            $added_days[] = $date;
        }
    }
}

// --- CALCULATIONS ---
 $fake_uv = min(10, max(0, round($current['main']['temp'] / 5)));

// --- TIME & DATE ---
 $current_time = date('H:i');       // e.g., 14:30
 $current_date = date('F j, Y'); // e.g., Monday, October 25

// --- RESPONSE ---
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
    'time'         => $current_time,
    'date'         => $current_date,
    'forecast'     => $forecast_days,
];

// --- FINAL OUTPUT ---
ob_end_clean();
echo json_encode($response);
exit;

