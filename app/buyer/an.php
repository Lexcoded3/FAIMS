<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'buyer') {
    header("Location: ../../login.php");
    exit;
}

// Get market snapshot (safe even if crop is NULL)
$market_data = $conn->query("
    SELECT 
        COALESCE(mp.crop, c.name, CONCAT('Category #', mp.category_id)) AS crop_name,
        ROUND(AVG(mp.price), 0) AS avg_price,
        ROUND(MIN(mp.price), 0) AS min_price,
        ROUND(MAX(mp.price), 0) AS max_price,
        COUNT(mp.id) AS data_points,
        MAX(mp.date) AS latest_date
    FROM market_prices mp
    LEFT JOIN categories c ON mp.category_id = c.id
    GROUP BY mp.category_id, mp.crop
    ORDER BY avg_price DESC
    LIMIT 8
")->fetch_all(MYSQLI_ASSOC);

// Get trend data for chart
$trend_raw = $conn->query("
    SELECT 
        DATE_FORMAT(mp.date, '%d %b') AS date_label,
        COALESCE(mp.crop, c.name, CONCAT('Cat-', mp.category_id)) AS crop_name,
        ROUND(AVG(mp.price), 0) AS avg_price
    FROM market_prices mp
    LEFT JOIN categories c ON mp.category_id = c.id
    GROUP BY mp.category_id, mp.crop, mp.date
    ORDER BY mp.date ASC
    LIMIT 100
")->fetch_all(MYSQLI_ASSOC);

// Prepare chart data
$chart_labels = [];
$chart_series = [];

if (!empty($trend_raw)) {
    $crops = array_unique(array_column($trend_raw, 'crop_name'));
    foreach ($crops as $crop) {
        $prices = [];
        foreach ($trend_raw as $row) {
            if ($row['crop_name'] === $crop) {
                $prices[] = (int)$row['avg_price'];
            }
        }
        if (count($prices) > 1) {   // only show crops with multiple points
            $chart_series[] = ['name' => $crop, 'data' => $prices];
        }
    }
    $chart_labels = array_values(array_unique(array_column($trend_raw, 'date_label')));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Marketplace Insights • FAIMS</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen">

<nav class="bg-white shadow-sm sticky top-0 z-50">
  <div class="max-w-7xl mx-auto px-6 py-5 flex items-center justify-between">
    <div class="flex items-center gap-3">
      <i class="fas fa-store text-3xl text-green-600"></i>
      <h1 class="text-2xl font-bold">Marketplace Insights</h1>
    </div>
    <a href="index.php" class="text-green-600 hover:text-green-700 flex items-center gap-2">
      <i class="fas fa-arrow-left"></i> Dashboard
    </a>
  </div>
</nav>

<main class="max-w-7xl mx-auto px-6 py-8">
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

    <div class="lg:col-span-9 space-y-10">

      <!-- Snapshot Cards -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
        <?php if (empty($market_data)): ?>
          <div class="col-span-4 text-center py-12 text-gray-500">No market price data available yet.</div>
        <?php else: ?>
          <?php foreach (array_slice($market_data, 0, 4) as $item): ?>
            <div class="bg-white rounded-2xl p-6 shadow">
              <p class="text-sm text-gray-600"><?= htmlspecialchars($item['crop_name']) ?></p>
              <p class="text-3xl font-bold text-green-700 mt-2">
                UGX <?= number_format($item['avg_price']) ?>
              </p>
              <p class="text-xs text-green-600 mt-3">
                Updated <?= date('d M', strtotime($item['latest_date'] ?? date('Y-m-d'))) ?>
              </p>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <!-- Price Trend Chart -->
      <div class="bg-white rounded-2xl shadow p-6">
        <h3 class="font-semibold text-lg mb-6">Price Trends (Last 30 Days)</h3>
        <?php if (empty($chart_series)): ?>
          <div class="h-96 flex items-center justify-center text-gray-500">
            Not enough historical data to show trends yet.<br>
            Please add more dates to market_prices table.
          </div>
        <?php else: ?>
          <div id="priceTrendChart" class="h-96"></div>
        <?php endif; ?>
      </div>

    </div>
  </div>
</main>

<script>
  const options = {
    series: <?= json_encode($chart_series) ?>,
    chart: { type: 'line', height: 380, toolbar: { show: false } },
    stroke: { curve: 'smooth', width: 3 },
    colors: ['#15803d', '#854d0e', '#1e40af', '#b45309'],
    xaxis: { categories: <?= json_encode($chart_labels) ?> },
    yaxis: { title: { text: 'UGX per kg' } }
  };

  const chart = new ApexCharts(document.getElementById('priceTrendChart'), options);
  chart.render();
</script>
</body>
</html>