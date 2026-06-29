<?php
require_once "weather_helpers.php";
function farmForecast($weather, $crop) {
    if (empty($weather['list'])) {
        return ['status' => 'error', 'forecast' => []];
    }

    $dailyData = _aggregateDaily($weather);
    $forecast = [];

    foreach ($dailyData as $day) {
        $actions = [];
        $icons = []; // For your UI

        // Rain logic
        if ($day['rain_total'] > 10) {
            $actions[] = "Heavy rain ({$day['rain_total']}mm) → Delay fieldwork.";
            $icons[] = '🌧️';
        } elseif ($day['rain_total'] > 0) {
            $actions[] = "Light rain expected → No irrigation needed.";
            $icons[] = '🌦️';
        }

        // Humidity logic
        if ($day['humidity_avg'] > 85) {
            $actions[] = "High humidity ({$day['humidity_avg']}%) → Disease monitoring.";
            $icons[] = '🦠';
        }

        // Crop specific logic
        $cropLower = strtolower($crop);
        if ($cropLower == 'maize' && $day['temp_avg'] >= 18 && $day['temp_avg'] <= 30) {
            $actions[] = "Optimal temperature for maize growth.";
            $icons[] = '🌽';
        }

        if (empty($actions)) {
            $actions[] = "Normal farming conditions.";
            $icons[] = '✅';
        }

        $forecast[] = [
            'date'        => $day['date'],
            'day'         => date("l", strtotime($day['date'])),
            'temp_high'   => round($day['temp_max'], 1),
            'temp_low'    => round($day['temp_min'], 1),
            'humidity'    => round($day['humidity_avg'], 0),
            'rain_mm'     => round($day['rain_total'], 1),
            'wind_max'    => round($day['wind_max'], 1),
            'actions'     => $actions,
            'icons'       => implode(' ', $icons)
        ];

        if (count($forecast) >= 5) break;
    }

    return [
        'status'   => 'success',
        'crop'     => $crop,
        'forecast' => $forecast
    ];
}