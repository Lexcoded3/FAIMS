<?php
require_once "weather_helpers.php";
function predictDisease($weather, $crop) {
    if (empty($weather['list'])) {
        return ['status' => 'error', 'diseases' => []];
    }

    $dailyData = _aggregateDaily($weather);
    $diseases = [];

    // Track consecutive days of bad conditions (diseases need time to develop)
    $highHumidDays = 0;
    $wetDays = 0;

    foreach ($dailyData as $day) {
        if ($day['humidity_avg'] > 85) $highHumidDays++;
        if ($day['rain_total'] > 5) $wetDays++;

        // TOMATO BLIGHT: Needs 2+ days of high humidity + temps 20-30
        if (strtolower($crop) === 'tomato' && $highHumidDays >= 2 && $day['temp_avg'] >= 20 && $day['temp_avg'] <= 30) {
            $diseases[] = [
                'name'      => 'Tomato Late Blight',
                'likelihood'=> 'high',
                'trigger'   => "{$highHumidDays} days high humidity + temp {$day['temp_avg']}°C",
                'advice'    => "Apply preventive copper-based fungicide immediately. Improve air circulation."
            ];
            break; // Stop checking after first trigger
        }

        // MAIZE RUST: Needs high humidity
        if (strtolower($crop) === 'maize' && $highHumidDays >= 3) {
            $diseases[] = [
                'name'      => 'Maize Leaf Rust',
                'likelihood'=> 'medium',
                'trigger'   => "Prolonged humidity ({$highHumidDays} days)",
                'advice'    => "Inspect lower leaves for pustules. Apply Triazole fungicide if found."
            ];
            break;
        }

        // BEANS ANTHRACNOSE: Needs wet foliage (rain + humidity)
        if (strtolower($crop) === 'beans' && $wetDays >= 2 && $highHumidDays >= 2) {
            $diseases[] = [
                'name'      => 'Bean Anthracnose',
                'likelihood'=> 'high',
                'trigger'   => "Rain and high humidity combo",
                'advice'    => "Avoid working in wet fields. Remove infected plant debris."
            ];
            break;
        }
    }

    if (empty($diseases)) {
        $diseases[] = [
            'name'      => 'None',
            'likelihood'=> 'low',
            'message'   => 'Current weather patterns do not favor major disease outbreaks.'
        ];
    }

    return [
        'status'   => 'success',
        'crop'     => $crop,
        'diseases' => $diseases
    ];
}