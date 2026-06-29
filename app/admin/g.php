<?php
session_start();
require_once __DIR__ . '../../config/db.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

// Fetch all groups
$groups = $conn->query("
    SELECT g.*, u.name AS leader_name, 
           COUNT(gm.user_id) AS member_count
    FROM groups g
    LEFT JOIN users u ON g.leader_id = u.id
    LEFT JOIN group_members gm ON gm.group_id = g.id
    GROUP BY g.id
    ORDER BY g.created_at DESC
")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Groups Management • FAIMSAdmin</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen">

  <div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
      <h1 class="text-3xl font-bold">Groups Management</h1>
      <a href="group-edit.php" class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700">
        + Create New Group
      </a>
    </div>

    <?php if (empty($groups)): ?>
      <div class="bg-white p-12 text-center rounded-xl shadow">
        <i class="fas fa-users-slash text-6xl text-gray-300 mb-4"></i>
        <p class="text-xl text-gray-600">No groups yet.</p>
      </div>
    <?php else: ?>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($groups as $group): ?>
          <div class="bg-white rounded-xl shadow p-6 hover:shadow-lg transition">
            <div class="flex justify-between items-start mb-4">
              <div>
                <h3 class="text-xl font-semibold"><?= htmlspecialchars($group['name']) ?></h3>
                <p class="text-sm text-gray-500"><?= htmlspecialchars($group['type']) ?></p>
              </div>
              <span class="px-3 py-1 rounded-full text-xs font-medium
                <?= $group['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                <?= $group['is_active'] ? 'Active' : 'Inactive' ?>
              </span>
            </div>

            <div class="space-y-2 text-sm text-gray-600 mb-6">
              <p><i class="fas fa-user-tie mr-2"></i> Leader: <?= htmlspecialchars($group['leader_name'] ?: 'None') ?></p>
              <p><i class="fas fa-users mr-2"></i> Members: <?= $group['member_count'] ?></p>
              <p><i class="fas fa-map-marker-alt mr-2"></i> <?= htmlspecialchars($group['location'] ?: 'Not set') ?></p>
              <p><i class="fas fa-calendar-alt mr-2"></i> Created: <?= date('d M Y', strtotime($group['created_at'])) ?></p>
            </div>

            <div class="flex gap-3">
              <a href="group-edit.php?id=<?= $group['id'] ?>" class="flex-1 bg-blue-600 text-white py-2 rounded-lg text-center hover:bg-blue-700">
                Edit
              </a>
              <a href="group-members.php?id=<?= $group['id'] ?>" class="flex-1 bg-indigo-600 text-white py-2 rounded-lg text-center hover:bg-indigo-700">
                Members
              </a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

</body>
</html>