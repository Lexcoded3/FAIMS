<?php
require_once "weather_helpers.php";
function plantingAdvice($weather, $crop) {
    if (empty($weather['list'])) {
        return ['status' => 'error', 'verdict' => 'unknown', 'message' => 'Insufficient data.'];
    }

    $dailyData = _aggregateDaily($weather);
    $next5Days = array_slice($dailyData, 0, 5);

    // Calculate averages for the next 5 days
    $avgTemp = array_sum(array_column($next5Days, 'temp_avg')) / count($next5Days);
    $avgHumidity = array_sum(array_column($next5Days, 'humidity_avg')) / count($next5Days);
    $totalRain = array_sum(array_column($next5Days, 'rain_total'));

    // Crop specific thresholds
    $thresholds = [
        'maize'  => ['temp_min' => 18, 'temp_max' => 30, 'rain_max' => 100],
        'beans'  => ['temp_min' => 15, 'temp_max' => 27, 'rain_max' => 80],
        'tomato' => ['temp_min' => 20, 'temp_max' => 30, 'rain_max' => 60],
        'rice'   => ['temp_min' => 20, 'temp_max' => 35, 'rain_max' => 200], // Rice loves water
    ];

    $t = $thresholds[strtolower($crop)] ?? null;
    
    if (!$t) {
        return [
            'status'  => 'unknown',
            'verdict' => 'unknown',
            'message' => "Crop '{$crop}' not configured for planting analysis."
        ];
    }

    $reasons = [];
    $verdict = 'favorable';

    if ($avgTemp < $t['temp_min']) {
        $verdict = 'unfavorable';
        $reasons[] = "Average temp ({$avgTemp}°C) is too low. Needs > {$t['temp_min']}°C.";
    } elseif ($avgTemp > $t['temp_max']) {
        $verdict = 'unfavorable';
        $reasons[] = "Average temp ({$avgTemp}°C) is too high. Needs < {$t['temp_max']}°C.";
    }

    if ($totalRain > $t['rain_max']) {
        $verdict = 'caution';
        $reasons[] = "Heavy rain forecast ({$totalRain}mm). Risk of seed washout.";
    }

    if (empty($reasons)) {
        $reasons[] = "5-day outlook looks favorable for planting " . ucfirst($crop) . ".";
    }

    return [
        'status'      => 'success',
        'verdict'     => $verdict, // 'favorable', 'unfavorable', 'caution'
        'crop'        => $crop,
        'avg_temp'    => round($avgTemp, 1),
        'expected_rain'=> round($totalRain, 1),
        'reasons'     => $reasons
    ];
}