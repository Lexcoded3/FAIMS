<?php

class DiseasePageAnalyzer {
    
    public function analyzeFullTimeline(array $weather, string $crop): array {
        if (empty($weather['list'])) return ['status' => 'error', 'timeline' => []];

        $dailyData = _aggregateDaily($weather);
        $timeline = [];
        $consecutiveHighHumidity = 0;

        foreach ($dailyData as $day) {
            $dayRisk = 'low';
            $diseases = [];
            $crop = strtolower($crop);

            // Track consecutive humidity (critical for fungi)
            if ($day['humidity_avg'] > 80) {
                $consecutiveHighHumidity++;
            } else {
                $consecutiveHighHumidity = 0;
            }

            // --- CROP SPECIFIC LOGIC ---
            
            if ($crop === 'tomato') {
                if ($consecutiveHighHumidity >= 2 && $day['temp_avg'] >= 18 && $day['temp_avg'] <= 26) {
                    $diseases[] = ['name' => 'Late Blight', 'severity' => 'high', 'action' => 'Apply copper fungicide immediately.'];
                    $dayRisk = 'high';
                }
                if ($day['temp_avg'] > 28 && $day['humidity_avg'] < 60) {
                    $diseases[] = ['name' => 'Spider Mites', 'severity' => 'medium', 'action' => 'Increase humidity or use miticide.'];
                    $dayRisk = 'medium';
                }
            }

            if ($crop === 'maize') {
                if ($consecutiveHighHumidity >= 3) {
                    $diseases[] = ['name' => 'Northern Leaf Blight', 'severity' => 'high', 'action' => 'Apply triazole fungicide.'];
                    $dayRisk = 'high';
                }
                if ($day['rain_total'] > 15) {
                    $diseases[] = ['name' => 'Maize Rust', 'severity' => 'medium', 'action' => 'Scout lower leaves for pustules.'];
                    if ($dayRisk === 'low') $dayRisk = 'medium';
                }
            }

            if ($crop === 'beans') {
                if ($day['rain_total'] > 10 && $day['humidity_avg'] > 85) {
                    $diseases[] = ['name' => 'Anthracnose', 'severity' => 'high', 'action' => 'Do not work in wet fields. Apply mancozeb.'];
                    $dayRisk = 'high';
                }
            }

            $timeline[] = [
                'date'         => $day['date'],
                'day'          => date("l", strtotime($day['date'])),
                'risk_level'   => $dayRisk,
                'trigger_data' => [
                    'temp_avg' => round($day['temp_avg'], 1),
                    'humidity_avg' => round($day['humidity_avg'], 0),
                    'rain_mm' => round($day['rain_total'], 1),
                    'consecutive_humid_days' => $consecutiveHighHumidity
                ],
                'diseases'     => $diseases
            ];
        }

        return [
            'status'   => 'success',
            'crop'     => $crop,
            'summary'  => $this->generateSummary($timeline),
            'timeline' => array_slice($timeline, 0, 5)
        ];
    }

    private function generateSummary(array $timeline): array {
        $highRiskDays = count(array_filter($timeline, fn($d) => $d['risk_level'] === 'high'));
        $allDiseases = array_merge(...array_column($timeline, 'diseases'));
        
        return [
            'high_risk_days' => $highRiskDays,
            'top_threats'    => array_unique(array_column($allDiseases, 'name')),
            'overall_status' => $highRiskDays > 2 ? 'critical' : ($highRiskDays > 0 ? 'warning' : 'safe')
        ];
    }
}