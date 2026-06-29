<?php

class StockRiskAnalyzer {
    
    // Weather-driven stock risk: How will weather impact your current and future inventory?
    public function analyze(array $weather, string $crop): array {
        if (empty($weather['list'])) return ['status' => 'error', 'risks' => []];

        $dailyData = _aggregateDaily($weather);
        $risks = [];

        // 1. Post-Harvest Storage Risk (Based on current/historical humidity)
        $currentHumidity = $dailyData[0]['humidity_avg'] ?? 0;
        if ($currentHumidity > 85) {
            $risks[] = [
                'type'     => 'storage_loss',
                'severity' => 'high',
                'title'    => 'High Storage Rot Risk',
                'reason'   => "Current ambient humidity is {$currentHumidity}%. Stored crops (especially grains/tubers) are at high risk of fungal rot and aflatoxin.",
                'action'   => 'Improve warehouse ventilation immediately. Use moisture meters to test stock. Consider drying stock if possible.'
            ];
        }

        // 2. Supply Disruption (Floods/Wind destroying standing stock or transport)
        $maxWind = max(array_column($dailyData, 'wind_max'));
        $totalRain5Day = array_sum(array_column($dailyData, 'rain_total'));
        
        if ($totalRain5Day > 40 || $maxWind > 15) {
            $risks[] = [
                'type'     => 'supply_disruption',
                'severity' => 'high',
                'title'    => 'Supply Chain/Field Stock Threat',
                'reason'   => "Heavy rain ({$totalRain5Day}mm) or high winds ({$maxWind}km/h) forecast. Risk of field stock destruction or impassable roads for input/output transport.",
                'action'   => 'Harvest mature crops immediately if safe. Secure input supplies (fertilizer/chemicals) before roads flood.'
            ];
        }

        // 3. Future Yield Shortfall (Drought stress)
        $dryDays = count(array_filter($dailyData, fn($d) => $d['rain_total'] < 1 && $d['temp_avg'] > 30));
        if ($dryDays >= 3) {
            $risks[] = [
                'type'     => 'yield_shortfall',
                'severity' => 'medium',
                'title'    => 'Future Stock Depletion Risk',
                'reason'   => "{$dryDays} days of hot, dry conditions detected. If prolonged, current standing stock will suffer yield loss, leading to future inventory shortages.",
                'action'   => 'Plan for potential deficit. Retain a higher percentage of current stock rather than selling.'
            ];
        }

        if (empty($risks)) {
            $risks[] = [
                'type' => 'none',
                'severity' => 'low',
                'title' => 'Stable Stock Environment',
                'reason' => 'Weather conditions favor normal storage and supply chain operations.',
                'action' => 'Standard monitoring applies.'
            ];
        }

        return [
            'status'      => 'success',
            'crop'        => $crop,
            'overall_risk'=> max(array_column($risks, 'severity')) === 'high' ? 'high' : 'low',
            'risks'       => $risks
        ];
    }
}