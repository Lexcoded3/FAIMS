<?php
require_once "weather_helpers.php";
function analyzeRisk($weather, $crop) {
    if (empty($weather['list'])) {
        return ['status' => 'error', 'overall_risk' => 0, 'risks' => []];
    }

    $dailyData = _aggregateDaily($weather);
    $risks = [];
    $highestScore = 0;

    // 1. Heat Stress Risk
    foreach ($dailyData as $day) {
        if ($day['temp_max'] > 34) {
            $score = $day['temp_max'] > 38 ? 9 : 6;
            if ($score > $highestScore) $highestScore = $score;
            $risks[] = [
                'type' => 'heat_stress',
                'score' => $score,
                'date' => $day['date'],
                'message' => "Heat stress risk on {$day['date']} (Max: {$day['temp_max']}°C)."
            ];
        }
    }

    // 2. Fungal Disease Risk (Humidity + Temp combo over multiple days)
    $highHumidityDays = 0;
    foreach ($dailyData as $day) {
        if ($day['humidity_avg'] > 85 && $day['temp_avg'] > 20) {
            $highHumidityDays++;
        }
    }
    if ($highHumidityDays >= 2) {
        $score = $highHumidityDays >= 4 ? 8 : 5;
        if ($score > $highestScore) $highestScore = $score;
        $risks[] = [
            'type' => 'fungal_outbreak',
            'score' => $score,
            'message' => "Prolonged high humidity ({$highHumidityDays} days). High fungal disease risk."
        ];
    }

    // 3. Waterlogging Risk
    $next3DaysRain = array_sum(array_column(array_slice($dailyData, 0, 3), 'rain_total'));
    if ($next3DaysRain > 30) {
        $score = $next3DaysRain > 50 ? 9 : 6;
        if ($score > $highestScore) $highestScore = $score;
        $risks[] = [
            'type' => 'waterlogging',
            'score' => $score,
            'message' => "Waterlogging risk. {$next3DaysRain}mm rain expected in 3 days."
        ];
    }

    return [
        'status'      => 'success',
        'crop'        => $crop,
        'overall_risk'=> $highestScore, // 0 = safe, 10 = extreme danger
        'risk_level'  => $highestScore >= 7 ? 'high' : ($highestScore >= 4 ? 'medium' : 'low'),
        'risks'       => $risks
    ];
}