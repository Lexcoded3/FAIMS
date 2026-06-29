<?php
// app/common/create-group.php
session_start();
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['id'])) {
    header("Location: ../../login.php");
    exit;
}

$user_id = $_SESSION['id'];
$user_role = $_SESSION['role'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $type        = $_POST['type'] ?? 'other';
    $location    = trim($_POST['location'] ?? '');

    if (empty($name)) {
        $error = "Group name is required";
    } else {
        $stmt = $conn->prepare("
            INSERT INTO groups 
            (name, description, type, location, created_by, approved, created_at)
            VALUES (?, ?, ?, ?, ?, 0, NOW())
        ");
        $stmt->bind_param("ssssi", $name, $description, $type, $location, $user_id);
        if ($stmt->execute()) {
            $success = "Group request submitted! Waiting for admin approval.";
        } else {
            $error = "Failed to submit group request.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Group Request • FAIMS</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">

  <div class="max-w-2xl mx-auto px-4 py-12">
    <div class="bg-white rounded-xl shadow-lg p-8">
      <h1 class="text-2xl font-bold mb-8">Request a New Group</h1>

      <?php if (isset($success)): ?>
        <div class="bg-green-100 border border-green-200 text-green-800 p-6 rounded-lg mb-6">
          <?= htmlspecialchars($success) ?>
        </div>
      <?php endif; ?>

      <?php if (isset($error)): ?>
        <div class="bg-red-100 border border-red-200 text-red-800 p-6 rounded-lg mb-6">
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <form method="POST" class="space-y-6">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Group Name *</label>
          <input type="text" name="name" required class="w-full px-4 py-3 border rounded-lg focus:ring-green-500 focus:border-green-500">
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Group Type</label>
          <select name="type" class="w-full px-4 py-3 border rounded-lg">
            <option value="farmer_coop">Farmer Cooperative</option>
            <option value="buyer_group">Buyer Group</option>
            <option value="village">Village / Community Group</option>
            <option value="other">Other</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Location / District</label>
          <input type="text" name="location" class="w-full px-4 py-3 border rounded-lg" placeholder="e.g. Wakiso, Mbale">
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
          <textarea name="description" rows="5" class="w-full px-4 py-3 border rounded-lg"></textarea>
        </div>

        <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white py-3 px-6 rounded-lg font-medium transition">
          Submit Group Request
        </button>
      </form>
    </div>
  </div>

</body>
</html>