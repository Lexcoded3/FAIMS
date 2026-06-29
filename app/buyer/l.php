<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'buyer') {
    header("Location: ../../login.php");
    exit;
}

// 1. Get all unique crops/categories with latest stats
$market_data = $conn->query("
    SELECT 
        mp.category_id,c.image AS category_image,
        COALESCE(mp.crop, c.name, CONCAT('Category ', mp.category_id)) AS crop_name,
        ROUND(AVG(mp.price), 0) AS avg_price,
        ROUND(MIN(mp.price), 0) AS min_price,
        ROUND(MAX(mp.price), 0) AS max_price,
        COUNT(mp.id) AS data_points,
        MAX(mp.date) AS latest_date,
        (SELECT price FROM market_prices mp2 
         WHERE mp2.category_id = mp.category_id 
         ORDER BY mp2.date DESC LIMIT 1) AS current_price,
        (SELECT price FROM market_prices mp3 
         WHERE mp3.category_id = mp.category_id 
         ORDER BY mp3.date ASC LIMIT 1) AS start_price
    FROM market_prices mp
    LEFT JOIN categories c ON mp.category_id = c.id
    GROUP BY mp.category_id, mp.crop
    ORDER BY current_price DESC
")->fetch_all(MYSQLI_ASSOC);

// 2. Prepare mini-chart data for each crop (last 7 prices or less)
$mini_charts = [];
foreach ($market_data as &$item) {
    $cat_id = $item['category_id'];
    $prices = $conn->query("
        SELECT price, date 
        FROM market_prices 
        WHERE category_id = $cat_id 
        ORDER BY date DESC 
        LIMIT 7
    ")->fetch_all(MYSQLI_ASSOC);

    // Use array_map to ensure every price is a numeric float
    $item['chart_data'] = array_reverse(array_map('floatval', array_column($prices, 'price')));
    $item['price_change'] = round(($item['current_price'] - $item['start_price']) / $item['start_price'] * 100, 1);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Live Market Prices • FAIMS</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen font-sans antialiased">

  <!-- Top Bar -->
  <nav class="bg-white shadow-sm sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <i class="fas fa-chart-line text-3xl text-green-600"></i>
        <h1 class="text-xl font-bold text-gray-900">Live Market Prices</h1>
      </div>
      <a href="index.php" class="text-green-600 hover:text-green-700 flex items-center gap-2">
        <i class="fas fa-arrow-left"></i> Dashboard
      </a>
    </div>
  </nav>

  <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Watchlist / Live Prices Carousel -->
    <div class="card mt-4 pb-1 sm:mt-5 lg:mt-6">
      <div class="my-3 flex items-center justify-between px-4 sm:px-5">
        <h2 class="font-medium tracking-wide text-slate-700 dark:text-navy-100">
          Live Market Watchlist
        </h2>
        <div class="text-sm text-gray-500">
          Updated <?= date('d M Y H:i') ?>
        </div>
      </div>

      <div class="scrollbar-sm flex space-x-4 overflow-x-auto overflow-y-hidden px-4 pb-6 sm:px-5">
        <?php foreach ($market_data as $item):
        // Inside your foreach ($market_data as &$item) loop:
$item['chart_color'] = ($item['price_change'] >= 0) ? '#10b981' : '#ef4444'; // Green vs Red
 ?>

          <div class="flex w-72 shrink-0 flex-col">
            <div class="flex items-center space-x-2">
              <div class="size-8 rounded-full bg-slate-100 flex items-center justify-center text-green-700 overflow-hidden border border-slate-200">
  <?php 
    $img = $item['category_image'];
    // We check if the string isn't empty and if the file actually exists on the server
    if (!empty($img) && file_exists('../' . $img)): 
  ?>
    <img src="../<?= htmlspecialchars($img) ?>" 
         alt="icon" 
         class="size-6 object-contain">
  <?php else: ?>
    <i class="fas fa-seedling text-sm"></i>
  <?php endif; ?>
</div>


              <div>
                <span class="font-medium"><?= htmlspecialchars($item['crop_name']) ?></span>
                <span class="text-xs uppercase text-slate-400 dark:text-navy-300 block">
                  <?= htmlspecialchars($item['category_id'] ? 'Cat ' . $item['category_id'] : 'N/A') ?>
                </span>
              </div>
            </div>

            <div class="mt-2.5 flex justify-between rounded-lg bg-slate-50 py-3 pr-3 dark:bg-navy-600">
              <!-- Mini Chart -->
              <!-- Mini Chart Container -->
              <!-- Mini Chart Container -->
            <div class="w-32 h-16"> 
              <div x-init="
                setTimeout(() => {
                  new ApexCharts($el, {
                    // Use .map(Number) just in case the PHP strings persist
                    series: [{ data: <?= json_encode($item['chart_data']) ?>.map(Number) }],
                    chart: { 
                      type: 'area', 
                      height: 60, 
                      sparkline: { enabled: true },
                      animations: { enabled: true } 
                    },
                    stroke: { 
                      curve: 'smooth', 
                      width: 3 
                    },
                    fill: {
                        type: 'gradient',
                        gradient: {
                            shadeIntensity: 1,
                            inverseColors: false,
                            opacityFrom: 0.45,
                            opacityTo: 0.05,
                            stops: [20, 100, 100, 100]
                          },
                      },
                    // Use the color from our PHP logic
                    colors: ['<?= $item['chart_color'] ?>'], 
                    tooltip: { enabled: false }
                  }).render();
                }, 150)
              "></div>
            </div>


              <!-- Current Price + Change -->
              <div class="flex w-36 flex-col items-center rounded-lg bg-slate-100 py-2 font-inter dark:bg-navy-500">
                <p class="text-xl font-medium text-slate-700 dark:text-navy-100">
                  UGX <?= number_format($item['current_price'] ?? 0, 0) ?>
                </p>
                <p class="mt-1 flex items-center space-x-1 text-xs <?= $item['price_change'] >= 0 ? 'text-success' : 'text-error' ?>">
                  <i class="fas fa-<?= $item['price_change'] >= 0 ? 'arrow-up' : 'arrow-down' ?>"></i>
                  <span><?= abs($item['price_change']) ?>%</span>
                </p>
              </div>
            </div>
          </div>
        <?php endforeach; ?>

        <?php if (empty($market_data)): ?>
          <div class="w-full text-center py-12 text-gray-500">
            No market prices recorded yet. Add data to see live insights.
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Market Insights Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-10">
      <div class="card p-6">
        <h3 class="font-medium text-lg mb-4">Market Overview</h3>
        <div class="space-y-3 text-sm">
          <div class="flex justify-between">
            <span class="text-gray-600">Total Crops Tracked</span>
            <span class="font-medium"><?= count($market_data) ?></span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-600">Last Update</span>
            <span class="font-medium"><?= date('d M Y') ?></span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-600">Avg Price Change (30d)</span>
            <span class="text-green-600 font-medium">+8.4%</span>
          </div>
        </div>
      </div>

      <div class="card p-6">
        <h3 class="font-medium text-lg mb-4">Highest Price Today</h3>
        <?php if (!empty($market_data)): $top = $market_data[0]; ?>
          <p class="text-2xl font-bold text-green-700">
            UGX <?= number_format($top['current_price'] ?? 0, 0) ?>
          </p>
          <p class="text-sm text-gray-600 mt-1">
            <?= htmlspecialchars($top['crop_name']) ?> • Category <?= $top['category_id'] ?>
          </p>
        <?php else: ?>
          <p class="text-gray-500">No data yet</p>
        <?php endif; ?>
      </div>

      <div class="card p-6">
        <h3 class="font-medium text-lg mb-4">Most Volatile Crop</h3>
        <?php if (!empty($market_data)): $volatile = array_reduce($market_data, function($carry, $item) {
            $vol = ($item['max_price'] - $item['min_price']) / $item['avg_price'] * 100;
            return ($vol > $carry['vol']) ? ['vol' => $vol, 'crop' => $item['crop_name']] : $carry;
        }, ['vol' => 0, 'crop' => 'None']); ?>
          <p class="text-2xl font-bold text-amber-600">
            <?= htmlspecialchars($volatile['crop']) ?>
          </p>
          <p class="text-sm text-gray-600 mt-1">
            Volatility: <?= round($volatile['vol'], 1) ?>%
          </p>
        <?php else: ?>
          <p class="text-gray-500">No data yet</p>
        <?php endif; ?>
      </div>
    </div>
  </main>

</body>
</html>
