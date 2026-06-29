<?php

class PriceRiskAnalyzer {
    
    // Basic economic principle: Bad Weather = Low Yield = High Prices. Good Weather = High Yield = Low Prices.
    public function forecast(array $weather, string $crop): array {
        if (empty($weather['list'])) return ['status' => 'error', 'forecast' => []];

        $dailyData = _aggregateDaily($weather);
        
        $totalRain = array_sum(array_column($dailyData, 'rain_total'));
        $dryHotDays = count(array_filter($dailyData, fn($d) => $d['rain_total'] < 2 && $d['temp_avg'] > 32));
        $floodDays = count(array_filter($dailyData, fn($d) => $d['rain_total'] > 20));

        $trend = 'stable';
        $confidence = 'low';
        $factors = [];

        // Scenario A: Adverse Weather (Prices go UP)
        if ($dryHotDays >= 3 || $floodDays >= 2) {
            $trend = 'spike_likely';
            $confidence = 'medium';
            
            if ($dryHotDays >= 3) {
                $factors[] = "Drought stress ({$dryHotDays} dry/hot days) will likely reduce local yield, decreasing market supply.";
            }
            if ($floodDays >= 2) {
                $factors[] = "Flooding conditions ({$floodDays} heavy rain days) may destroy standing crops, causing immediate supply shocks.";
            }
            $factors[] = "Expect buyers to bid up prices for available stock. Hold onto inventory if you have spare capacity.";
        } 
        // Scenario B: Perfect Weather (Prices go DOWN)
        elseif ($totalRain > 15 && $totalRain < 60 && empty(array_filter($dailyData, fn($d) => $d['temp_max'] > 35))) {
            $trend = 'drop_likely';
            $confidence = 'medium';
            $factors[] = "Near-optimal growing conditions detected (Good rain, safe temps).";
            $factors[] = "Market anticipates a good harvest. Bulk buyers may delay purchasing, depressing current prices.";
            $factors[] = "Consider locking in current prices via forward contracts if available.";
        } 
        // Scenario C: Stable/Normal
        else {
            $factors[] = "Weather is variable but lacks extreme destructive or perfect conditions.";
            $factors[] = "Prices will likely be driven by standard market forces (demand, transport costs) rather than weather.";
        }

        return [
            'status'       => 'success',
            'crop'         => $crop,
            'market_trend' => $trend, // 'spike_likely', 'drop_likely', 'stable'
            'confidence'   => $confidence,
            'drivers'      => $factors,
            'advice'       => $this->getFarmerAdvice($trend)
        ];
    }

    private function getFarmerAdvice(string $trend): string {
        return match($trend) {
            'spike_likely' => "Strategy: Delay selling if possible. Target bulk buyers during the predicted shortage window.",
            'drop_likely'  => "Strategy: Sell current stock now before the anticipated harvest surplus floods the market.",
            default        => "Strategy: Follow normal sales cycles. Monitor local market boards closely."
        };
    }
}