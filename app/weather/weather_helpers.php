<?php
// Helper to convert 3-hour intervals into daily summaries
function _aggregateDaily($weather) {
    $days = [];
    foreach ($weather['list'] as $entry) {
        $date = date("Y-m-d", strtotime($entry['dt_txt'] ?? '@'.$entry['dt']));
        
        if (!isset($days[$date])) {
            $days[$date] = [
                'date'       => $date,
                'temps'      => [],
                'humidity'   => [],
                'wind'       => [],
                'rain_total' => 0
            ];
        }
        
        $days[$date]['temps'][]    = $entry['main']['temp'] ?? 0;
        $days[$date]['humidity'][] = $entry['main']['humidity'] ?? 0;
        $days[$date]['wind'][]     = $entry['wind']['speed'] ?? 0;
        $days[$date]['rain_total']+= $entry['rain']['3h'] ?? 0;
    }

    // Calculate max/avg for each day
    foreach ($days as &$day) {
        $day['temp_max']     = max($day['temps']);
        $day['temp_min']     = min($day['temps']);
        $day['temp_avg']     = array_sum($day['temps']) / count($day['temps']);
        $day['humidity_avg'] = array_sum($day['humidity']) / count($day['humidity']);
        $day['wind_max']     = max($day['wind']);
    }

    return array_values($days);
}