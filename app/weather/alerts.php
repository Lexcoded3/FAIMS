<?php
require_once "weather_helpers.php";
function generateAlerts($weather) {
    if (empty($weather['list'])) return ['status' => 'ok', 'alerts' => []];

    $alerts = [];
    $dailyData = _aggregateDaily($weather);

    foreach ($dailyData as $day) {
        $date = $day['date'];
        $maxTemp = $day['temp_max'];
        $maxWind = $day['wind_max'];
        $totalRain = $day['rain_total'];

        // Extreme Heat (Checks daily max)
        if ($maxTemp > 36) {
            $alerts[] = [
                'severity' => 'critical',
                'type'     => 'heat',
                'date'     => $date,
                'message'  => "Extreme heat warning ({$maxTemp}°C). Protect crops from heat stress."
            ];
        } elseif ($maxTemp > 33) {
            $alerts[] = [
                'severity' => 'warning',
                'type'     => 'heat',
                'date'     => $date,
                'message'  => "High temperature ({$maxTemp}°C). Ensure adequate irrigation."
            ];
        }

        // Strong Winds
        if ($maxWind > 18) {
            $alerts[] = [
                'severity' => 'warning',
                'type'     => 'wind',
                'date'     => $date,
                'message'  => "Strong winds ({$maxWind} km/h). Secure greenhouses and shade nets."
            ];
        }

        // Heavy Rain / Flooding
        if ($totalRain > 20) {
            $alerts[] = [
                'severity' => 'critical',
                'type'     => 'rain',
                'date'     => $date,
                'message'  => "Heavy rain expected ({$totalRain}mm). Check drainage systems."
            ];
        }
    }

    // Deduplicate and return
    return [
        'status'     => !empty($alerts) ? 'active' : 'ok',
        'count'      => count($alerts),
        'alerts'     => $alerts
    ];
}

