<?php
// app/admin/group-members.php
session_start();
require_once __DIR__ . '../../config/db.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

$group_id = (int)($_GET['id'] ?? 0);
if ($group_id <= 0) {
    header("Location: groups.php?error=invalid_group");
    exit;
}

// Get group info
$group_stmt = $conn->prepare("SELECT name FROM groups WHERE id = ?");
$group_stmt->bind_param("i", $group_id);
$group_stmt->execute();
$group = $group_stmt->get_result()->fetch_assoc();
$group_stmt->close();

if (!$group) {
    header("Location: groups.php?error=group_not_found");
    exit;
}

// Get current members
$members = $conn->query("
    SELECT u.id, u.name, u.email, gm.role, gm.joined_at
    FROM group_members gm
    JOIN users u ON gm.user_id = u.id
    WHERE gm.group_id = $group_id
    ORDER BY gm.role DESC, u.name
")->fetch_all(MYSQLI_ASSOC);

// Get all users who are NOT in this group (for adding)
$available_users = $conn->query("
    SELECT id, name, email, role
    FROM users
    WHERE id NOT IN (SELECT user_id FROM group_members WHERE group_id = $group_id)
    ORDER BY name
")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Members of <?= htmlspecialchars($group['name']) ?> • Admin</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen">

  <div class="max-w-6xl mx-auto px-4 py-8">
    <div class="bg-white rounded-xl shadow p-8">
      <div class="flex justify-between items-center mb-8">
        <h1 class="text-2xl font-bold">Members of <?= htmlspecialchars($group['name']) ?></h1>
        <a href="groups.php" class="text-green-600 hover:underline flex items-center gap-2">
          <i class="fas fa-arrow-left"></i> Back to Groups
        </a>
      </div>

      <!-- Current Members -->
      <div class="mb-10">
        <h2 class="text-xl font-semibold mb-4">Current Members (<?= count($members) ?>)</h2>
        <?php if (empty($members)): ?>
          <p class="text-gray-500">No members yet.</p>
        <?php else: ?>
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="bg-gray-100">
                  <th class="p-4">Name</th>
                  <th class="p-4">Email</th>
                  <th class="p-4">Role</th>
                  <th class="p-4">Joined</th>
                  <th class="p-4">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($members as $m): ?>
                  <tr class="border-t hover:bg-gray-50">
                    <td class="p-4"><?= htmlspecialchars($m['name']) ?></td>
                    <td class="p-4 text-gray-600"><?= htmlspecialchars($m['email']) ?></td>
                    <td class="p-4">
                      <?php
						$roleClass = 'bg-gray-100 text-gray-700';
						if ($m['role'] === 'leader') {
						    $roleClass = 'bg-purple-100 text-purple-800';
						} elseif ($m['role'] === 'admin') {
						    $roleClass = 'bg-blue-100 text-blue-800';
						}
						?>

						<span class="px-3 py-1 rounded-full text-xs font-medium <?= $roleClass ?>">
						  <?= ucfirst($m['role']) ?>
						</span>
                    </td>
                    <td class="p-4 text-gray-600"><?= date('d M Y', strtotime($m['joined_at'])) ?></td>
                    <td class="p-4">
                      <form action="ajax/member-remove.php" method="POST" class="inline">
                        <input type="hidden" name="group_id" value="<?= $group_id ?>">
                        <input type="hidden" name="user_id" value="<?= $m['id'] ?>">
                        <button type="submit" onclick="return confirm('Remove <?= htmlspecialchars($m['name']) ?> from group?')" 
                                class="text-red-600 hover:text-red-800">
                          Remove
                        </button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>

      <!-- Add Member -->
      <div>
        <h2 class="text-xl font-semibold mb-4">Add New Member</h2>
        <?php if (empty($available_users)): ?>
          <p class="text-gray-500">No available users (all are already in this group or no users exist).</p>
        <?php else: ?>
          <form action="ajax/member-add.php" method="POST" class="flex gap-4">
            <input type="hidden" name="group_id" value="<?= $group_id ?>">
            <select name="user_id" required class="flex-1 px-4 py-3 border rounded-lg">
              <option value="">Select user...</option>
              <?php foreach ($available_users as $u): ?>
                <option value="<?= $u['id'] ?>">
                  <?= htmlspecialchars($u['name']) ?> (<?= htmlspecialchars($u['email']) ?>) - <?= ucfirst($u['role']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <select name="role" class="w-40 px-4 py-3 border rounded-lg">
              <option value="member">Member</option>
              <option value="admin">Admin</option>
              <option value="leader">Leader</option>
            </select>
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg">
              Add Member
            </button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>

</body>
</html>