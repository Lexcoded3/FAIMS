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

// Mark all as read (optional - on page load)
if (isset($_GET['mark_read'])) {
    $sql_read = "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0";
    $stmt_read = $conn->prepare($sql_read);
    $stmt_read->bind_param("i", $buyer_id);
    $stmt_read->execute();
    $stmt_read->close();
    header("Location: notifications.php");
    exit;
}

// Get unread count for badge
$sql_unread = "SELECT COUNT(*) AS unread FROM notifications WHERE user_id = ? AND is_read = 0";
$stmt_unread = $conn->prepare($sql_unread);
$stmt_unread->bind_param("i", $buyer_id);
$stmt_unread->execute();
$unread_count = $stmt_unread->get_result()->fetch_assoc()['unread'] ?? 0;
$stmt_unread->close();

// Fetch all notifications
$sql = "
    SELECT * FROM notifications 
    WHERE user_id = ? 
    ORDER BY created_at DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $buyer_id);
$stmt->execute();
$notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Notifications • FAIMS</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen">

  <header class="bg-white shadow-sm">
    <div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
      <a href="index.php" class="text-green-700 hover:text-green-800 flex items-center">
        <i class="fas fa-arrow-left mr-2"></i> Dashboard
      </a>
      <h1 class="text-xl font-bold">Notifications</h1>
      <?php if ($unread_count > 0): ?>
        <a href="?mark_read=1" class="text-sm text-green-600 hover:underline">
          Mark all as read
        </a>
      <?php endif; ?>
    </div>
  </header>

  <main class="max-w-4xl mx-auto px-4 py-8">
    <?php if (empty($notifications)): ?>
      <div class="text-center py-16 bg-white rounded-2xl shadow border border-gray-200">
        <i class="fas fa-bell-slash text-7xl text-gray-300 mb-6"></i>
        <h2 class="text-2xl font-semibold text-gray-700">No notifications yet</h2>
        <p class="text-gray-500 mt-3">Stay tuned for updates on your offers and orders.</p>
      </div>
    <?php else: ?>
      <div class="space-y-4">
        <?php foreach ($notifications as $notif): ?>
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 hover:shadow-md transition <?= $notif['is_read'] ? '' : 'bg-blue-50 border-blue-200' ?>">
            <div class="flex items-start gap-4">
              <div class="size-10 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-bell text-blue-600"></i>
              </div>
              <div class="flex-1">
                <h4 class="font-semibold text-gray-900 <?= $notif['is_read'] ? '' : 'text-blue-700' ?>">
                  <?= htmlspecialchars($notif['title']) ?>
                  <?php if (!$notif['is_read']): ?>
                    <span class="ml-2 inline-block size-2 bg-blue-600 rounded-full"></span>
                  <?php endif; ?>
                </h4>
                <p class="mt-1 text-gray-700"><?= nl2br(htmlspecialchars($notif['message'])) ?></p>
                <p class="mt-2 text-xs text-gray-500">
                  <?= date('d M Y • H:i', strtotime($notif['created_at'])) ?>
                </p>
                <?php if ($notif['reference_type'] && $notif['reference_id']): ?>
                  <a href="<?= $notif['reference_type'] === 'negotiation' ? 'negotiation-details.php?id=' . $notif['reference_id'] : '#' ?>" 
                     class="mt-2 inline-block text-green-600 hover:underline text-sm">
                    View Details →
                  </a>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </main>
</body>
</html>