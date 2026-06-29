<?php
session_start();

// file_put_contents(__DIR__ . '/debug.txt', "Session ID: " . ($_SESSION['id'] ?? 'not set') . "\n", FILE_APPEND);
 $required_role = 'buyer'; // Only farmers allowed
require_once '../config/auth_check.php';
require_once '../config/db.php';

$buyer_id = $_SESSION['id'] ?? 0;  // ← make sure this is set during login
// ─── Filters & Search ────────────────────────────────────────
$search     = $_GET['search'] ?? '';
$category   = (int)($_GET['category'] ?? 0);
$min_price  = (float)($_GET['min_price'] ?? 0);
$max_price  = (float)($_GET['max_price'] ?? 0);
$district   = $_GET['district'] ?? '';
$sort       = $_GET['sort'] ?? 'newest';   // newest, price_asc, price_desc, quantity_desc

// Build WHERE clause
$where = ["p.status = 'active'"];   // ← fixed: specify p.status
$params = [];
$types  = "";

if ($search !== '') {
    $where[] = "(p.name LIKE ? OR p.description LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $types .= "ss";
}
if ($category > 0) {
    $where[] = "p.category_id = ?";
    $params[] = $category;
    $types .= "i";
}
if ($min_price > 0) {
    $where[] = "p.price >= ?";
    $params[] = $min_price;
    $types .= "d";
}
if ($max_price > 0) {
    $where[] = "p.price <= ?";
    $params[] = $max_price;
    $types .= "d";
}
if ($district !== '') {
    $where[] = "u.location LIKE ?";
    $params[] = "%$district%";
    $types .= "s";
}

$where_sql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
// Sorting
$order_by = match($sort) {
    'price_asc'    => "p.price ASC",
    'price_desc'   => "p.price DESC",
    'quantity_desc'=> "p.quantity DESC",
    default        => "p.created_at DESC"   // newest
};

// Pagination (simple)
$page       = max(1, (int)($_GET['page'] ?? 1));
$per_page   = 12;
$offset     = ($page - 1) * $per_page;

$count_sql = "SELECT COUNT(*) as total 
              FROM products p 
              LEFT JOIN users u ON p.farmer_id = u.id 
              $where_sql";

$stmt_count = $conn->prepare($count_sql);
if ($types) $stmt_count->bind_param($types, ...$params);
$stmt_count->execute();
$total_rows = $stmt_count->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $per_page);

$sql = "SELECT p.*, c.name as category_name, u.name as farmer_name, u.location as farmer_location
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN users u ON p.farmer_id = u.id
        $where_sql
        ORDER BY $order_by
        LIMIT ? OFFSET ?";

$params_full = array_merge($params, [$per_page, $offset]);
$types_full  = $types . "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types_full, ...$params_full);
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Load categories for filter dropdown
$categories = $conn->query("SELECT id, name FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Marketplace - FAIMS Buyer</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen">

  <!-- Header / Navbar (reuse from dashboard if you have one) -->
  <nav class="bg-green-800 text-white p-4">
    <div class="max-w-7xl mx-auto flex justify-between items-center">
      <h1 class="text-2xl font-bold">FAIMS Marketplace</h1>
      <a href="index.php" class="hover:underline">Back to Dashboard</a>
    </div>
  </nav>

  <main class="max-w-7xl mx-auto px-4 py-8">

    <!-- Filters & Search -->
    <form method="GET" class="bg-white p-6 rounded-xl shadow mb-10">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <!-- Search -->
        <div>
          <label class="block text-sm font-medium mb-1">Search produce</label>
          <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                 class="w-full border rounded-lg px-4 py-2" placeholder="e.g. maize, beans...">
        </div>

        <!-- Category -->
        <div>
          <label class="block text-sm font-medium mb-1">Category</label>
          <select name="category" class="w-full border rounded-lg px-4 py-2">
            <option value="">All Categories</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat['id'] ?>" <?= $category == $cat['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($cat['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Price Range -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium mb-1">Min Price (UGX)</label>
            <input type="number" name="min_price" value="<?= $min_price ?: '' ?>" 
                   class="w-full border rounded-lg px-4 py-2" min="0">
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Max Price (UGX)</label>
            <input type="number" name="max_price" value="<?= $max_price ?: '' ?>" 
                   class="w-full border rounded-lg px-4 py-2" min="0">
          </div>
        </div>

        <!-- Sort -->
        <div class="flex items-end">
          <button type="submit" class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 w-full font-medium">
            Apply Filters
          </button>
        </div>
      </div>
    </form>

    <!-- Products Grid -->
    <?php if (empty($products)): ?>
      <div class="text-center py-12 bg-white rounded-xl shadow">
        <i class="fas fa-box-open text-6xl text-gray-300 mb-4"></i>
        <p class="text-xl text-gray-600">No products found matching your filters.</p>
        <p class="text-gray-500 mt-2">Try adjusting your search or filters.</p>
      </div>
    <?php else: ?>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <?php foreach ($products as $product): ?>
          <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition">
            <!-- Image -->
            <div class="h-48 bg-gray-200 relative">
              <?php if ($product['image']): ?>
                <img src="<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" 
                     class="w-full h-full object-cover">
              <?php else: ?>
                <div class="flex items-center justify-center h-full text-gray-400">
                  <i class="fas fa-seedling text-6xl"></i>
                </div>
              <?php endif; ?>
              <span class="absolute top-3 left-3 bg-green-600 text-white px-3 py-1 rounded-full text-xs font-medium">
                <?= htmlspecialchars($product['category_name'] ?? 'Uncategorized') ?>
              </span>
            </div>

            <!-- Content -->
            <div class="p-5">
              <h3 class="font-bold text-lg mb-2 line-clamp-2">
                <?= htmlspecialchars($product['name']) ?>
              </h3>
              <p class="text-2xl font-bold text-green-700 mb-3">
                UGX <?= number_format($product['price'], 0) ?> / <?= htmlspecialchars($product['unit'] ?? 'kg') ?>
              </p>
              <div class="text-sm text-gray-600 space-y-1 mb-4">
                <p><strong>Available:</strong> <?= number_format($product['quantity']) ?> <?= htmlspecialchars($product['unit'] ?? 'kg') ?></p>
                <p><strong>Farmer:</strong> <?= htmlspecialchars($product['farmer_name'] ?? 'Unknown') ?></p>
                <p><strong>Location:</strong> <?= htmlspecialchars($product['farmer_location'] ?? 'N/A') ?></p>
              </div>

              <!-- Actions -->
              <div class="flex gap-3">
                <a href="product-details.php?id=<?= $product['id'] ?>" 
                   class="flex-1 bg-blue-600 text-white text-center py-2 rounded-lg hover:bg-blue-700">
                  View Details
                </a>
                <button class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700"
                        onclick="alert('Start negotiation with farmer (to be implemented)')">
                  <i class="fas fa-handshake"></i>
                </button>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Pagination -->
      <?php if ($total_pages > 1): ?>
        <div class="mt-10 flex justify-center gap-3">
          <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?= $i ?>&<?= http_build_query($_GET) ?>" 
               class="px-4 py-2 rounded-lg <?= $page == $i ? 'bg-green-600 text-white' : 'bg-gray-200 hover:bg-gray-300' ?>">
              <?= $i ?>
            </a>
          <?php endfor; ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>

  </main>
</body>
</html>