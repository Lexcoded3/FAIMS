<?php
session_start();
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'extension') { header('Location: /login.php'); exit; }
require_once '../config/db.php';

$extension_id   = (int)$_SESSION['id'];
$extension_name = $_SESSION['name'] ?? 'Extension Worker';
$active_page    = 'farmers.php';

$search   = $conn->real_escape_string(trim($_GET['search'] ?? ''));
$page     = max(1,(int)($_GET['page'] ?? 1));
$per_page = 15;
$offset   = ($page-1)*$per_page;

$where = "WHERE u.role='farmer'";
if ($search !== '') $where .= " AND (u.name LIKE '%$search%' OR u.location LIKE '%$search%')";

$total       = $conn->query("SELECT COUNT(*) AS c FROM users u $where")->fetch_assoc()['c'];
$total_pages = max(1,(int)ceil($total/$per_page));

$farmers = [];
$res = $conn->query("
    SELECT u.id, u.name, u.phone, u.location, u.created_at, u.last_login,
           COUNT(DISTINCT p.id) AS product_count,
           COUNT(DISTINCT o.id) AS order_count,
           SUM(CASE WHEN l.status IN ('active','disbursed') THEN 1 ELSE 0 END) AS active_loans
    FROM users u
    LEFT JOIN products p ON p.farmer_id=u.id
    LEFT JOIN orders   o ON o.farmer_id=u.id
    LEFT JOIN loans    l ON l.farmer_id=u.id
    $where
    GROUP BY u.id
    ORDER BY u.created_at DESC
    LIMIT $per_page OFFSET $offset
");
while ($r = $res->fetch_assoc()) $farmers[] = $r;

$avatar_colors = ['#1D9E75','#0F6E56','#378ADD','#185FA5','#D85A30','#993C1D','#BA7517'];
?>
<!DOCTYPE html><html lang="en"><head>
<title>Farmer Activity — FAIMS Extension</title>
<?php include '_head.php'; ?>
</head>
<body class="bg-gray-50 text-gray-800">
<div class="flex h-screen overflow-hidden">
<?php include '_sidebar.php'; ?>

<main class="flex-1 flex flex-col overflow-hidden">
    <header class="bg-white border-b border-gray-100 px-6 py-4 flex-shrink-0">
        <h1 class="text-base text-gray-800" style="font-weight:500">Farmer activity</h1>
        <p class="text-xs text-gray-400 mt-0.5"><?= $total ?> registered farmer<?= $total!=1?'s':'' ?></p>
    </header>

    <div class="flex-1 overflow-y-auto scrollbar-hide px-6 py-5 fade-in">
        <form method="GET" class="flex items-center gap-3 mb-5">
            <div class="relative flex-1 max-w-xs">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" width="13" height="13" viewBox="0 0 13 13" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="5.5" cy="5.5" r="4"/><path d="M9 9l2.5 2.5"/></svg>
                <input type="text" name="search" value="<?= htmlspecialchars($_GET['search']??'') ?>" placeholder="Search by name or location…" style="padding-left:32px">
            </div>
            <button type="submit" class="btn-ghost">Search</button>
            <?php if($search): ?><a href="farmers.php" class="text-xs text-gray-400 hover:text-gray-600">Clear</a><?php endif; ?>
        </form>

        <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
            <table class="w-full">
                <thead><tr class="border-b border-gray-100">
                    <th class="text-left px-4 py-3 text-xs text-gray-400" style="font-weight:500">Farmer</th>
                    <th class="text-left px-4 py-3 text-xs text-gray-400" style="font-weight:500">Location</th>
                    <th class="text-left px-4 py-3 text-xs text-gray-400" style="font-weight:500">Products</th>
                    <th class="text-left px-4 py-3 text-xs text-gray-400" style="font-weight:500">Orders</th>
                    <th class="text-left px-4 py-3 text-xs text-gray-400" style="font-weight:500">Active loans</th>
                    <th class="text-left px-4 py-3 text-xs text-gray-400" style="font-weight:500">Last login</th>
                    <th class="text-left px-4 py-3 text-xs text-gray-400" style="font-weight:500">Joined</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-50">
                <?php if(empty($farmers)): ?>
                    <tr><td colspan="7" class="px-4 py-10 text-center text-xs text-gray-400">No farmers found</td></tr>
                <?php else: foreach($farmers as $i=>$f):
                    $color = $avatar_colors[$i % count($avatar_colors)]; ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs text-white flex-shrink-0" style="background:<?= $color ?>;font-weight:500"><?= strtoupper(substr($f['name'],0,1)) ?></div>
                                <div>
                                    <p class="text-xs text-gray-700" style="font-weight:500"><?= htmlspecialchars($f['name']) ?></p>
                                    <?php if($f['phone']): ?><p class="mono text-xs text-gray-400"><?= htmlspecialchars($f['phone']) ?></p><?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-500"><?= htmlspecialchars($f['location']??'—') ?></td>
                        <td class="px-4 py-3">
                            <span class="tag" style="background:#EAF3DE;color:#3B6D11"><?= (int)$f['product_count'] ?></span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="tag" style="background:#E6F1FB;color:#185FA5"><?= (int)$f['order_count'] ?></span>
                        </td>
                        <td class="px-4 py-3">
                            <?php if($f['active_loans']>0): ?>
                            <span class="tag" style="background:#FAEEDA;color:#854F0B"><?= (int)$f['active_loans'] ?> loan<?= $f['active_loans']!=1?'s':'' ?></span>
                            <?php else: ?><span class="text-xs text-gray-400">—</span><?php endif; ?>
                        </td>
                        <td class="px-4 py-3 mono text-xs text-gray-400"><?= $f['last_login']?date('d M Y',strtotime($f['last_login'])):'—' ?></td>
                        <td class="px-4 py-3 mono text-xs text-gray-400"><?= date('d M Y',strtotime($f['created_at'])) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <?php if($total_pages>1): ?>
        <div class="flex items-center justify-between mt-4">
            <p class="text-xs text-gray-400">Showing <?= $offset+1 ?>–<?= min($offset+$per_page,$total) ?> of <?= $total ?></p>
            <div class="flex items-center gap-1">
                <?php for($p=1;$p<=$total_pages;$p++): ?>
                <a href="?page=<?= $p ?>&search=<?= urlencode($search) ?>"
                   class="w-7 h-7 flex items-center justify-center rounded-lg text-xs transition-colors <?= $p===$page?'text-white':'text-gray-500 hover:bg-gray-100' ?>"
                   style="<?= $p===$page?'background:#1D9E75':'' ?>"><?= $p ?></a>
                <?php endfor; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</main>
</div>
</body></html>
