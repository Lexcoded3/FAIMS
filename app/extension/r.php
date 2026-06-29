<?php
session_start();
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'extension') { header("Location: ../auth"); exit; }
require_once '../config/db.php';

$extension_id   = (int)$_SESSION['id'];
$extension_name = $_SESSION['name'] ?? 'Extension Worker';
$active_page    = 'reports.php';

$search   = $conn->real_escape_string(trim($_GET['search'] ?? ''));
$district = $conn->real_escape_string(trim($_GET['district'] ?? ''));
$page     = max(1,(int)($_GET['page'] ?? 1));
$per_page = 15;
$offset   = ($page-1)*$per_page;

$where = "WHERE extension_id=$extension_id";
if ($district !== '') $where .= " AND district='$district'";
if ($search !== '')   $where .= " AND (title LIKE '%$search%' OR report LIKE '%$search%')";

$total       = $conn->query("SELECT COUNT(*) AS c FROM extension_reports $where")->fetch_assoc()['c'];
$total_pages = max(1,(int)ceil($total/$per_page));

$reports = [];
$res = $conn->query("SELECT id,title,district,report,created_at FROM extension_reports $where ORDER BY created_at DESC LIMIT $per_page OFFSET $offset");
while ($r = $res->fetch_assoc()) $reports[] = $r;

$districts = [];
$res = $conn->query("SELECT DISTINCT district FROM extension_reports WHERE extension_id=$extension_id ORDER BY district");
while ($r = $res->fetch_assoc()) $districts[] = $r['district'];

function detect_tag(string $t): string {
    $t = strtolower($t);
    if (preg_match('/disease|pest|blight|worm|virus|fungus|rust|armyworm/',$t)) return 'disease';
    if (preg_match('/yield|harvest|crop|produce/',$t))  return 'yield';
    if (preg_match('/soil|erosion|degrad|fertility/',$t)) return 'soil';
    if (preg_match('/water|irrigation|flood|drought|rain/',$t)) return 'water';
    return 'general';
}
?>
<!DOCTYPE html><html lang="en"><head>
<title>My Reports — FAIMS Extension</title>
<?php include '_head.php'; ?>
</head>
<body class="bg-gray-50 text-gray-800" x-data="{ modal: false, item: {} }">
<div class="flex h-screen overflow-hidden">
<?php include '_sidebar.php'; ?>

<main class="flex-1 flex flex-col overflow-hidden">
    <header class="bg-white border-b border-gray-100 px-6 py-4 flex items-center justify-between flex-shrink-0">
        <div>
            <h1 class="text-base text-gray-800" style="font-weight:500">My reports</h1>
            <p class="text-xs text-gray-400 mt-0.5"><?= $total ?> total report<?= $total!=1?'s':'' ?></p>
        </div>
        <a href="submit_report.php" class="btn-primary">
            <svg width="12" height="12" viewBox="0 0 12 12" fill="none" stroke="white" stroke-width="1.8"><path d="M6 1v10M1 6h10"/></svg>New report
        </a>
    </header>

    <div class="flex-1 overflow-y-auto scrollbar-hide px-6 py-5 fade-in">
        <!-- Filters -->
        <form method="GET" class="flex items-center gap-3 mb-5">
            <div class="relative flex-1 max-w-xs">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" width="13" height="13" viewBox="0 0 13 13" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="5.5" cy="5.5" r="4"/><path d="M9 9l2.5 2.5"/></svg>
                <input type="text" name="search" value="<?= htmlspecialchars($_GET['search']??'') ?>" placeholder="Search reports…" style="padding-left:32px">
            </div>
            <select name="district" style="width:auto">
                <option value="">All districts</option>
                <?php foreach($districts as $d): ?>
                <option value="<?= htmlspecialchars($d) ?>" <?= ($district===$d)?'selected':'' ?>><?= htmlspecialchars($d) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-ghost">Filter</button>
            <?php if($district||$search): ?><a href="reports.php" class="text-xs text-gray-400 hover:text-gray-600">Clear</a><?php endif; ?>
        </form>

        <!-- Table -->
        <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
            <table class="w-full">
                <thead><tr class="border-b border-gray-100">
                    <th class="text-left px-4 py-3 text-xs text-gray-400" style="font-weight:500">#</th>
                    <th class="text-left px-4 py-3 text-xs text-gray-400" style="font-weight:500">Title</th>
                    <th class="text-left px-4 py-3 text-xs text-gray-400" style="font-weight:500">District</th>
                    <th class="text-left px-4 py-3 text-xs text-gray-400" style="font-weight:500">Type</th>
                    <th class="text-left px-4 py-3 text-xs text-gray-400" style="font-weight:500">Date</th>
                    <th class="px-4 py-3"></th>
                </tr></thead>
                <tbody class="divide-y divide-gray-50">
                <?php if(empty($reports)): ?>
                    <tr><td colspan="6" class="px-4 py-10 text-center text-xs text-gray-400">No reports found</td></tr>
                <?php else: foreach($reports as $i=>$r): $tag=detect_tag($r['title']); ?>
                    <tr class="cursor-pointer hover:bg-gray-50 transition-colors"
                        @click="modal=true;item={title:<?= json_encode($r['title']) ?>,district:<?= json_encode($r['district']) ?>,report:<?= json_encode($r['report']) ?>,date:<?= json_encode(date('d F Y',strtotime($r['created_at']))) ?>,tag:'<?= $tag ?>'}">
                        <td class="px-4 py-3 mono text-xs text-gray-400"><?= $offset+$i+1 ?></td>
                        <td class="px-4 py-3">
                            <p class="text-xs text-gray-700" style="font-weight:500"><?= htmlspecialchars($r['title']) ?></p>
                            <p class="text-xs text-gray-400 mt-0.5"><?= htmlspecialchars(mb_strimwidth($r['report'],0,70,'…')) ?></p>
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-600"><?= htmlspecialchars($r['district']) ?></td>
                        <td class="px-4 py-3"><span class="tag tag-<?= $tag ?>"><?= ucfirst($tag) ?></span></td>
                        <td class="px-4 py-3 mono text-xs text-gray-400"><?= date('d M Y',strtotime($r['created_at'])) ?></td>
                        <td class="px-4 py-3"><a href="submit_report.php?edit=<?= $r['id'] ?>" @click.stop class="text-xs text-gray-400 hover:text-gray-600">Edit</a></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if($total_pages>1): ?>
        <div class="flex items-center justify-between mt-4">
            <p class="text-xs text-gray-400">Showing <?= $offset+1 ?>–<?= min($offset+$per_page,$total) ?> of <?= $total ?></p>
            <div class="flex items-center gap-1">
                <?php for($p=1;$p<=$total_pages;$p++): ?>
                <a href="?page=<?= $p ?>&district=<?= urlencode($district) ?>&search=<?= urlencode($search) ?>"
                   class="w-7 h-7 flex items-center justify-center rounded-lg text-xs transition-colors <?= $p===$page?'text-white':'text-gray-500 hover:bg-gray-100' ?>"
                   style="<?= $p===$page?'background:#1D9E75':'' ?>"><?= $p ?></a>
                <?php endfor; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</main>
</div>

<!-- Modal -->
<div x-show="modal" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
     :style="modal?'display:flex':'display:none'" @keydown.escape.window="modal=false"
     style="position:fixed;inset:0;z-index:50;background:rgba(0,0,0,.35);align-items:center;justify-content:center">
    <div class="bg-white rounded-2xl w-full max-w-lg mx-4 overflow-hidden" @click.stop>
        <div class="flex items-start justify-between px-5 py-4 border-b border-gray-100">
            <div>
                <p class="text-sm text-gray-800" style="font-weight:500" x-text="item.title"></p>
                <p class="text-xs text-gray-400 mt-0.5" x-text="item.district+' · '+item.date"></p>
            </div>
            <button @click="modal=false" class="text-gray-300 hover:text-gray-500 ml-4"><svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 3l10 10M13 3L3 13"/></svg></button>
        </div>
        <div class="px-5 py-4">
            <span class="tag mb-3 inline-block" :class="'tag-'+item.tag" x-text="item.tag?item.tag.charAt(0).toUpperCase()+item.tag.slice(1):''"></span>
            <p class="text-sm text-gray-600 leading-relaxed" x-text="item.report"></p>
        </div>
        <div class="px-5 py-3 border-t border-gray-100 flex justify-end">
            <button @click="modal=false" class="btn-ghost">Close</button>
        </div>
    </div>
</div>
</body></html>
