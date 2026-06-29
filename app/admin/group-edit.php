<?php
// app/admin/group-edit.php
session_start();
require_once __DIR__ . '../../config/db.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

$group_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$group = null;

if ($group_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM groups WHERE id = ?");
    $stmt->bind_param("i", $group_id);
    $stmt->execute();
    $group = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$leaders = $conn->query("SELECT id, name FROM users WHERE role IN ('farmer', 'buyer') ORDER BY name")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $group ? 'Edit' : 'Create' ?> Group • Admin</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen">

  <div class="max-w-4xl mx-auto px-4 py-8">
    <div class="bg-white rounded-xl shadow p-8">
      <h1 class="text-2xl font-bold mb-8"><?= $group ? 'Edit Group' : 'Create New Group' ?></h1>

      <form id="groupForm" action="ajax/group-save.php" method="POST" class="space-y-6">
        <?php if ($group): ?>
          <input type="hidden" name="id" value="<?= $group['id'] ?>">
        <?php endif; ?>

        <!-- Name -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Group Name *</label>
          <input type="text" name="name" required
                 value="<?= htmlspecialchars($group['name'] ?? '') ?>"
                 class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
        </div>

        <!-- Type -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Group Type</label>
          <select name="type" class="w-full px-4 py-3 border rounded-lg">
            <option value="farmer_coop"   <?= ($group['type'] ?? '') === 'farmer_coop' ? 'selected' : '' ?>>Farmer Cooperative</option>
            <option value="buyer_group"   <?= ($group['type'] ?? '') === 'buyer_group' ? 'selected' : '' ?>>Buyer Group</option>
            <option value="village"       <?= ($group['type'] ?? '') === 'village'     ? 'selected' : '' ?>>Village Group</option>
            <option value="other"         <?= ($group['type'] ?? 'other') === 'other'   ? 'selected' : '' ?>>Other</option>
          </select>
        </div>

        <!-- Leader -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Group Leader</label>
          <select name="leader_id" class="w-full px-4 py-3 border rounded-lg">
            <option value="">No leader assigned</option>
            <?php foreach ($leaders as $user): ?>
              <option value="<?= $user['id'] ?>" <?= ($group['leader_id'] ?? 0) == $user['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($user['name']) ?> (ID: <?= $user['id'] ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Location -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Location / District</label>
          <input type="text" name="location"
                 value="<?= htmlspecialchars($group['location'] ?? '') ?>"
                 class="w-full px-4 py-3 border rounded-lg" placeholder="e.g. Wakiso, Mbale">
        </div>

        <!-- Description -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
          <textarea name="description" rows="5"
                    class="w-full px-4 py-3 border rounded-lg"><?= htmlspecialchars($group['description'] ?? '') ?></textarea>
        </div>

        <!-- Active status -->
        <div class="flex items-center">
          <input type="checkbox" name="is_active" id="is_active" value="1" <?= !isset($group['is_active']) || $group['is_active'] ? 'checked' : '' ?> class="h-5 w-5 text-green-600">
          <label for="is_active" class="ml-2 text-gray-700">Group is active</label>
        </div>

        <!-- Submit -->
        <div class="flex gap-4 mt-8">
          <button type="submit" class="flex-1 bg-green-600 hover:bg-green-700 text-white py-3 px-6 rounded-lg font-medium transition">
            <?= $group ? 'Save Changes' : 'Create Group' ?>
          </button>
          <?php if ($group): ?>
            <a href="groups.php" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 py-3 px-6 rounded-lg font-medium text-center transition">
              Cancel
            </a>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>

</body>
</html>