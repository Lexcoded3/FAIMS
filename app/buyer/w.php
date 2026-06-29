<?php
session_start();

// file_put_contents(__DIR__ . '/debug.txt', "Session ID: " . ($_SESSION['id'] ?? 'not set') . "\n", FILE_APPEND);
 $required_role = 'buyer'; // Only buyer allowed
require_once '../config/auth_check.php';
require_once '../config/db.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'buyer') {
    header("Location: ../../login.php");
    exit;
}
$buyer_id = $_SESSION['id'];

// Get or create wallet
$sql_wallet = "SELECT * FROM wallets WHERE user_id = ?";
$stmt_wallet = $conn->prepare($sql_wallet);
$stmt_wallet->bind_param("i", $buyer_id);
$stmt_wallet->execute();
$wallet = $stmt_wallet->get_result()->fetch_assoc();
$stmt_wallet->close();

if (!$wallet) {
    // Create wallet
    $sql_create = "INSERT INTO wallets (user_id, balance, held_balance) VALUES (?, 0.00, 0.00)";
    $stmt_create = $conn->prepare($sql_create);
    $stmt_create->bind_param("i", $buyer_id);
    $stmt_create->execute();
    $wallet_id = $conn->insert_id;
    $stmt_create->close();

    // Reload wallet
    $sql_wallet = "SELECT * FROM wallets WHERE user_id = ?";
    $stmt_wallet = $conn->prepare($sql_wallet);
    $stmt_wallet->bind_param("i", $buyer_id);
    $stmt_wallet->execute();
    $wallet = $stmt_wallet->get_result()->fetch_assoc();
    $stmt_wallet->close();
}

$balance = $wallet['balance'] ?? 0.00;
$held = $wallet['held_balance'] ?? 0.00;
$available = $balance - $held;  // For display

// Recent transactions
$sql_tx = "
    SELECT * FROM wallet_transactions 
    WHERE wallet_id = ? 
    ORDER BY created_at DESC 
    LIMIT 10
";
$stmt_tx = $conn->prepare($sql_tx);
$stmt_tx->bind_param("i", $wallet['id']);
$stmt_tx->execute();
$transactions = $stmt_tx->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_tx->close();

// Pending/unpaid orders (for quick pay)
$sql_pending = "
    SELECT id, order_code, amount, payment_status, created_at
    FROM orders 
    WHERE buyer_id = ? AND payment_status != 'paid'
    ORDER BY created_at DESC
";
$stmt_pending = $conn->prepare($sql_pending);
$stmt_pending->bind_param("i", $buyer_id);
$stmt_pending->execute();
$pending_orders = $stmt_pending->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_pending->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Wallet & Payments • FAIMS</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen">

  <header class="bg-white shadow-sm">
    <div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
      <a href="index.php" class="text-green-700 hover:text-green-800 flex items-center">
        <i class="fas fa-arrow-left mr-2"></i> Dashboard
      </a>
      <h1 class="text-xl font-bold">Wallet & Payments</h1>
    </div>
  </header>

  <main class="max-w-7xl mx-auto px-4 py-8">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

      <!-- Wallet Balance Card -->
      <div class="lg:col-span-1">
        <div class="bg-white rounded-2xl shadow border border-gray-200 p-6 text-center">
          <h2 class="text-xl font-semibold mb-4">Your Wallet</h2>
          <div class="text-4xl font-bold text-green-700 mb-2">
            UGX <?= number_format($balance, 0) ?>
          </div>
          <p class="text-sm text-gray-600 mb-4">
            Available: UGX <?= number_format($available, 0) ?>  
            (Held: UGX <?= number_format($held, 0) ?>)
          </p>

          <!-- Deposit Button -->
          <button onclick="alert('Deposit via MoMo – feature coming soon!');"
                  class="w-full bg-green-600 hover:bg-green-700 text-white py-3 px-6 rounded-xl font-medium">
            Deposit Funds
          </button>
        </div>
      </div>

      <!-- Recent Transactions -->
      <div class="lg:col-span-2">
        <div class="bg-white rounded-2xl shadow border border-gray-200 p-6">
          <h3 class="text-xl font-semibold mb-5">Recent Transactions</h3>

          <?php if (empty($transactions)): ?>
            <div class="text-center py-8 text-gray-500">
              <i class="fas fa-history text-5xl text-gray-300 mb-4"></i>
              <p>No transactions yet</p>
            </div>
          <?php else: ?>
            <div class="space-y-4">
              <?php foreach ($transactions as $tx): ?>
                <div class="flex justify-between items-center border-b pb-3 last:border-b-0 last:pb-0">
                  <div>
                    <p class="font-medium">
                      <?= ucfirst($tx['type']) ?>
                      <?php if ($tx['reference_type'] === 'order'): ?>
                        - Order #<?= htmlspecialchars($tx['reference_id'] ?? 'N/A') ?>
                      <?php endif; ?>
                    </p>
                    <p class="text-sm text-gray-500"><?= date('d M Y • H:i', strtotime($tx['created_at'])) ?></p>
                  </div>
                  <p class="font-bold <?= $tx['amount'] > 0 ? 'text-green-600' : 'text-red-600' ?>">
                    <?= $tx['amount'] > 0 ? '+' : '-' ?> UGX <?= number_format(abs($tx['amount']), 0) ?>
                  </p>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Pending Payments / Quick Pay -->
      <div class="lg:col-span-3">
        <div class="bg-white rounded-2xl shadow border border-gray-200 p-6">
          <h3 class="text-xl font-semibold mb-5">Pending Payments</h3>

          <?php if (empty($pending_orders)): ?>
            <p class="text-gray-600">No pending payments. All orders are up to date!</p>
          <?php else: ?>
            <div class="space-y-4">
              <?php foreach ($pending_orders as $order): ?>
                <div class="flex justify-between items-center border-b pb-3 last:border-b-0 last:pb-0">
                  <div>
                    <p class="font-medium">Order #<?= htmlspecialchars($order['order_code']) ?></p>
                    <p class="text-sm text-gray-500">UGX <?= number_format($order['amount'], 0) ?> • <?= $order['payment_status'] ?></p>
                  </div>
                  <button onclick="alert('Pay now – MoMo integration coming soon!')"
                          class="bg-green-600 hover:bg-green-700 text-white py-2 px-5 rounded-lg font-medium">
                    Pay Now
                  </button>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <div class="col-span-12 mt-5 sm:col-span-6 sm:mt-0 lg:col-span-8 space-y-6">
  <h3 class="text-lg font-semibold mb-4 text-slate-700 dark:text-navy-100">Recent Deposits</h3>

  <?php
  // Fetch recent deposits only (latest first, limit 10)
  $sql_deposits = "
      SELECT id, amount, description, created_at, type
      FROM wallet_transactions
      WHERE wallet_id = ? AND type = 'deposit'
      ORDER BY created_at DESC
      LIMIT 10
  ";
  $stmt_deposits = $conn->prepare($sql_deposits);
  $stmt_deposits->bind_param("i", $wallet['id']);
  $stmt_deposits->execute();
  $deposits = $stmt_deposits->get_result()->fetch_all(MYSQLI_ASSOC);
  $stmt_deposits->close();
  ?>

  <?php if (empty($deposits)): ?>
    <div class="text-center py-8 bg-navy-700 rounded-2xl shadow">
      <i class="fas fa-wallet text-5xl text-gray-300 mb-4"></i>
      <p class="text-gray-400">No deposits yet</p>
      <p class="text-sm text-gray-500 mt-2">Your recent MoMo, Airtel, or bank deposits will appear here.</p>
    </div>
  <?php else: ?>
    <div class="swiper px-5 sm:pl-0" x-init="$nextTick(()=>new Swiper($el,{
      slidesPerView: 'auto',
      spaceBetween: 16,
      loop: false,
      navigation: {
        nextEl: '.swiper-button-next',
        prevEl: '.swiper-button-prev',
      }
    }))">
      <div class="swiper-wrapper">
        <?php foreach ($deposits as $dep): ?>
          <?php
            // Detect provider from description (or add 'provider' column later)
            $desc_lower = strtolower($dep['description'] ?? '');
            $provider = 'Other';
            $icon = 'fa-money-bill-wave';
            $color = 'bg-gradient-to-br from-indigo-500 to-indigo-600';

            if (strpos($desc_lower, 'mtn') !== false || strpos($desc_lower, 'momo') !== false) {
              $provider = 'MTN MoMo';
              $color = 'bg-gradient-to-br from-yellow-500 to-yellow-600';
              $icon = 'fa-mobile-alt';
            } elseif (strpos($desc_lower, 'airtel') !== false) {
              $provider = 'Airtel MoMo';
              $color = 'bg-gradient-to-br from-red-500 to-red-600';
              $icon = 'fa-mobile-alt';
            } elseif (strpos($desc_lower, 'bank') !== false) {
              $provider = 'Bank Transfer';
              $color = 'bg-gradient-to-br from-blue-500 to-blue-600';
              $icon = 'fa-university';
            }
          ?>

          <div class="swiper-slide relative h-40 w-64 shrink-0 rounded-lg <?= $color ?>">
            <div class="absolute inset-0 flex flex-col justify-between rounded-lg border border-white/10 p-5 text-white space-y-6">
              <div class="flex items-center justify-between">
                <i class="fas <?= $icon ?> text-2xl opacity-90"></i>
                <span class="text-xs font-medium opacity-80"><?= $provider ?></span>
              </div>

              <div>
                <p class="text-2xl font-bold tracking-wide">
                  + UGX <?= number_format($dep['amount'], 0) ?>
                </p>
                <p class="mt-1 text-xs opacity-80">
                  <?= date('d M Y • H:i', strtotime($dep['created_at'])) ?>
                </p>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Navigation arrows (optional) -->
      <div class="swiper-button-prev text-white"></div>
      <div class="swiper-button-next text-white"></div>
    </div>
  <?php endif; ?>
</div>
  </main>
</body>
</html>