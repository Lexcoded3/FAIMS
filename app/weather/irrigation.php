<?php
require_once "weather_helpers.php";
function irrigationAdvice($weather) {
    if (empty($weather['list'])) {
        return ['status' => 'error', 'advice' => 'No weather data available.'];
    }

    $dailyData = _aggregateDaily($weather);
    $today = $dailyData[0] ?? null;
    $next3Days = array_slice($dailyData, 0, 3);

    $totalRainNext3Days = array_sum(array_column($next3Days, 'rain_total'));
    $todayRain = $today['rain_total'] ?? 0;
    $todayTemp = $today['temp_avg'] ?? 25;

    $advice = [];
    $action = 'irrigate'; // default

    if ($todayRain > 5) {
        $action = 'skip';
        $advice[] = "Rainfall today ({$todayRain}mm). Skip irrigation to prevent waterlogging.";
    } elseif ($totalRainNext3Days > 15) {
        $action = 'reduce';
        $advice[] = "Significant rain expected over next 3 days ({$totalRainNext3Days}mm). Reduce irrigation schedule.";
    } else {
        if ($todayTemp > 30) {
            $advice[] = "High temperatures ({$todayTemp}°C). Increase irrigation volume to prevent wilting.";
        } else {
            $advice[] = "No significant rain expected. Standard irrigation recommended.";
        }
    }

    return [
        'status'      => 'success',
        'action'      => $action, // 'irrigate', 'skip', 'reduce'
        'today_rain'  => round($todayRain, 1),
        'forecast_rain' => round($totalRainNext3Days, 1),
        'advice'      => $advice
    ];
}