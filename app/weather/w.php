<?php
session_start();
$required_role = 'farmer'; // Only farmers allowed
require_once __DIR__ . '/../config/auth_check.php';
require_once "weather_engine.php";
require_once __DIR__ . '/../config/db.php';

// Default fallback location
$lat = -0.3476;
$lon = 32.5825;
$display_location = "Kampala, Uganda";

$crop = "maize"; // can later come from user profile

// If user is logged in, fetch location from database
if(isset($_SESSION['id'])){

    $user_id = $_SESSION['id'];

    $stmt = $conn->prepare("
        SELECT location_lat, location_lon, location
        FROM users
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->bind_param("i",$user_id);
    $stmt->execute();

    $result = $stmt->get_result()->fetch_assoc();

    if(!empty($result['location_lat']) && !empty($result['location_lon'])){

        $lat = (float)$result['location_lat'];
        $lon = (float)$result['location_lon'];

        $display_location = $result['location'] ?? "Your Farm";

    }

}

// Fetch weather
$data = weatherEngine($lat,$lon,$crop);

if(!$data || empty($data['weather'])){
    $error = "Weather service unavailable for {$display_location}. Please try again later.";
}

$current = $data['weather']['list'][0] ?? [];

$icon_code = $current['weather'][0]['icon'] ?? '01d';

$desc = ucfirst($current['weather'][0]['description'] ?? 'Clear');
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>FAIMS Weather • <?= htmlspecialchars($display_location) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-green-50 via-white to-emerald-50 min-h-screen font-sans antialiased">

  <!-- Header -->
  <header class="bg-gradient-to-r from-green-700 to-emerald-800 text-white shadow-lg">
    <div class="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center space-x-3">
          <i class="fas fa-cloud-sun-rain text-4xl"></i>
          <h1 class="text-2xl font-bold">Weather Intelligence</h1>
        </div>
        <div class="text-right">
          <p class="text-sm opacity-90"><?= htmlspecialchars($display_location) ?></p>
          <p class="text-xs opacity-80"><?= date('l, d M Y • H:i') ?></p>
        </div>
      </div>
    </div>
  </header>

  <?php if (isset($error)): ?>
    <div class="max-w-4xl mx-auto px-4 py-16 text-center">
      <i class="fas fa-exclamation-triangle text-6xl text-red-500 mb-6"></i>
      <h2 class="text-2xl font-bold text-gray-800 mb-4">Oops!</h2>
      <p class="text-lg text-gray-600"><?= htmlspecialchars($error) ?></p>
    </div>
  <?php else: ?>
    <main class="max-w-7xl mx-auto px-4 py-8 sm:px-6 lg:px-8">
      <!-- Hero: Current Weather -->
      <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100 mb-6">
        <div class="relative h-64 bg-gradient-to-br from-blue-600 to-indigo-700 text-white">
          <img src="https://openweathermap.org/img/wn/<?= $icon_code ?>@4x.png" 
               alt="<?= $desc ?>" 
               class="absolute inset-0 w-full h-full object-contain opacity-30">
          <div class="absolute inset-0 flex flex-col justify-end p-6 bg-gradient-to-t from-black/50 via-black/20">
            <p class="text-6xl font-extrabold"><?= round($current['main']['temp'] ?? 0) ?>°C</p>
            <p class="text-2xl font-medium mt-2 capitalize"><?= $desc ?></p>
            <div class="mt-4 flex gap-6 text-sm opacity-90">
              <span><i class="fas fa-tint mr-1"></i> <?= $current['main']['humidity'] ?? 0 ?>%</span>
              <span><i class="fas fa-wind mr-1"></i> <?= $current['wind']['speed'] ?? 0 ?> m/s</span>
              <span><i class="fas fa-feels-like mr-1"></i> <?= round($current['main']['feels_like'] ?? 0) ?>°C</span>
            </div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Irrigation Advice -->
        <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
          <h3 class="text-lg font-semibold mb-3 flex items-center gap-2">
            <i class="fas fa-water text-blue-600 text-xl"></i>
            Irrigation Advice
          </h3>
          <p class="text-gray-700 leading-relaxed">
            <?= nl2br(htmlspecialchars($data['irrigation'] ?? 'No specific advice available.')) ?>
          </p>
        </div>

        <!-- Planting Recommendation -->
        <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
          <h3 class="text-lg font-semibold mb-3 flex items-center gap-2">
            <i class="fas fa-seedling text-green-600 text-xl"></i>
            Planting Recommendation
          </h3>
          <p class="text-gray-700 leading-relaxed">
            <?= nl2br(htmlspecialchars($data['planting'] ?? 'No recommendation available.')) ?>
          </p>
        </div>

        <!-- Crop Risks -->
        <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
          <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
            <i class="fas fa-exclamation-triangle text-orange-500"></i>
            Crop Risks
          </h3>
          <?php if (empty($data['risks'])): ?>
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-green-800 font-medium">
              No crop risks detected right now.
            </div>
          <?php else: ?>
            <div class="space-y-3">
              <?php foreach ($data['risks'] as $risk): ?>
                <div class="bg-orange-50 border border-orange-200 rounded-lg p-4 text-orange-800">
                  <?= htmlspecialchars($risk) ?>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <!-- Disease Prediction -->
        <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
          <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
            <i class="fas fa-biohazard text-red-500"></i>
            Disease Prediction
          </h3>
          <?php if (empty($data['disease'])): ?>
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-green-800 font-medium">
              No disease risks detected.
            </div>
          <?php else: ?>
            <div class="space-y-3">
              <?php foreach ($data['disease'] as $d): ?>
                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                  <p class="font-medium text-red-800"><?= htmlspecialchars($d['name']) ?></p>
                  <p class="text-sm text-red-700 mt-1"><?= htmlspecialchars($d['advice']) ?></p>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <!-- Weather Alerts -->
        <div class="col-span-1 lg:col-span-3 bg-white rounded-2xl shadow-xl p-6 border border-gray-100">
          <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
            <i class="fas fa-bell text-red-500"></i>
            Active Weather Alerts
          </h3>
          <?php if (empty($data['alerts'])): ?>
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-green-800 font-medium">
              No active alerts at the moment.
            </div>
          <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <?php foreach ($data['alerts'] as $alert): ?>
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-red-800">
                  <?= htmlspecialchars($alert) ?>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
      <h3>7-Day Farm Forecast</h3>

<?php foreach($data['forecast'] as $day): ?>

<div style="border:1px solid #ddd;padding:10px;margin-bottom:10px">

<strong>Date:</strong> <?= $day['date'] ?><br>
<strong>Temp:</strong> <?= $day['temp'] ?> °C

<ul>
<?php foreach($day['actions'] as $a): ?>
<li><?= $a ?></li>
<?php endforeach; ?>
</ul>

</div>

<?php endforeach; ?>
    </main>
  <?php endif; ?>

  <footer class="bg-gray-800 text-gray-300 text-center py-6 mt-12">
    <p class="text-sm">Powered by FAIMS • Weather data from OpenWeatherMap</p>
  </footer>

</body>
</html>