<?php
session_start();
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'extension') { header('Location: /login.php'); exit; }
require_once '../config/db.php';

$extension_id   = (int)$_SESSION['id'];
$extension_name = $_SESSION['name'] ?? 'Extension Worker';
$active_page    = 'bulletins.php';

$search    = $conn->real_escape_string(trim($_GET['search'] ?? ''));
$mine_only = isset($_GET['mine']) && $_GET['mine'] === '1';
$page      = max(1,(int)($_GET['page'] ?? 1));
$per_page  = 20;
$offset    = ($page-1)*$per_page;

$where = "WHERE u.role='extension'";
if ($mine_only) $where .= " AND p.user_id=$extension_id";
if ($search !== '') $where .= " AND (p.title LIKE '%$search%' OR p.content LIKE '%$search%')";

$total       = $conn->query("SELECT COUNT(*) AS c FROM posts p JOIN users u ON u.id=p.user_id $where")->fetch_assoc()['c'];
$total_pages = max(1,(int)ceil($total/$per_page));

$bulletins = [];
$res = $conn->query("SELECT p.id, p.user_id,p.title,p.content,p.created_at,u.name AS author,p.user_id FROM posts p JOIN users u ON u.id=p.user_id $where ORDER BY p.created_at DESC LIMIT $per_page OFFSET $offset");
while ($r = $res->fetch_assoc()) $bulletins[] = $r;

function btype(string $title, string $content=''): array {
    $t = strtolower($title.' '.$content);
    if (preg_match('/disease|pest|blight|worm|virus|fungus|rust|outbreak/',$t)) return ['Disease alert','background:#FCEBEB;color:#A32D2D'];
    if (preg_match('/best practice|technique|method|how to|guide|tip/',$t))     return ['Best practice','background:#EAF3DE;color:#3B6D11'];
    if (preg_match('/season|planting|harvest|weather|rain|dry/',$t))            return ['Seasonal','background:#E6F1FB;color:#185FA5'];
    if (preg_match('/price|market|sell|buy|rate/',$t))                          return ['Market info','background:#FAEEDA;color:#854F0B'];
    return ['General info','background:#F1EFE8;color:#5F5E5A'];
}
?>
<!DOCTYPE html><html lang="en"><head>
<title>Agri Bulletins — FAIMS Extension</title>
<?php include '_head.php'; ?>
</head>
<body class="bg-gray-50 text-gray-800" x-data="{ modal: false, item: {} }">
<div class="flex h-screen overflow-hidden">
<?php include '_sidebar.php'; ?>

<main class="flex-1 flex flex-col overflow-hidden">
    <header class="bg-white border-b border-gray-100 px-6 py-4 flex items-center justify-between flex-shrink-0">
        <div>
            <h1 class="text-base text-gray-800" style="font-weight:500">Agri bulletins</h1>
            <p class="text-xs text-gray-400 mt-0.5">Shared agri info from all extension officers</p>
        </div>
        <a href="post_bulletin.php" class="btn-primary">
            <svg width="12" height="12" viewBox="0 0 12 12" fill="none" stroke="white" stroke-width="1.8"><path d="M6 1v10M1 6h10"/></svg>New bulletin
        </a>
    </header>

    <div class="flex-1 overflow-y-auto scrollbar-hide px-6 py-5 fade-in">
        <form method="GET" class="flex items-center gap-3 mb-5">
            <div class="relative flex-1 max-w-xs">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" width="13" height="13" viewBox="0 0 13 13" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="5.5" cy="5.5" r="4"/><path d="M9 9l2.5 2.5"/></svg>
                <input type="text" name="search" value="<?= htmlspecialchars($_GET['search']??'') ?>" placeholder="Search bulletins…" style="padding-left:32px">
            </div>
            <label class="flex items-center gap-2 text-xs text-gray-600 cursor-pointer">
                <input type="checkbox" name="mine" value="1" <?= $mine_only?'checked':'' ?> style="accent-color:#1D9E75">Only mine
            </label>
            <button type="submit" class="btn-ghost">Filter</button>
            <?php if($search||$mine_only): ?><a href="bulletins.php" class="text-xs text-gray-400 hover:text-gray-600">Clear</a><?php endif; ?>
        </form>

        <?php if(empty($bulletins)): ?>
        <div class="text-center py-16">
            <p class="text-xs text-gray-400 mb-3">No bulletins posted yet</p>
            <a href="post_bulletin.php" class="btn-primary">Post the first bulletin</a>
        </div>
        <?php else: ?>
        <div class="grid grid-cols-2 gap-4">
            <?php foreach($bulletins as $b):
                [$type,$style] = btype($b['title'],$b['content']);
                $is_mine = (int)$b['user_id']===$extension_id;
                $preview = mb_strimwidth(strip_tags($b['content']),0,180,'…');
            ?>
            <div class="bg-white rounded-xl border border-gray-100 p-4 cursor-pointer hover:border-gray-200 transition-colors"
                 @click="modal=true;item={title:<?= json_encode($b['title']) ?>,content:<?= json_encode($b['content']) ?>,author:<?= json_encode($b['author']) ?>,date:<?= json_encode(date('d F Y',strtotime($b['created_at']))) ?>}">
                <div class="flex items-start justify-between gap-2 mb-2">
                    <span class="tag" style="<?= $style ?>;font-size:11px"><?= $type ?></span>
                    <?php if($is_mine): ?><span class="text-xs text-gray-400">You</span><?php endif; ?>
                </div>
                <h3 class="text-sm text-gray-800 mb-1 leading-snug" style="font-weight:500"><?= htmlspecialchars($b['title']) ?></h3>
                <p class="text-xs text-gray-500 leading-relaxed mb-3"><?= htmlspecialchars($preview) ?></p>
                <div class="flex items-center justify-between">
                    <p class="text-xs text-gray-400"><?= htmlspecialchars($b['author']) ?></p>
                    <p class="mono text-xs text-gray-400"><?= date('d M Y',strtotime($b['created_at'])) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if($total_pages>1): ?>
        <div class="flex items-center justify-between mt-5">
            <p class="text-xs text-gray-400">Showing <?= $offset+1 ?>–<?= min($offset+$per_page,$total) ?> of <?= $total ?></p>
            <div class="flex items-center gap-1">
                <?php for($p=1;$p<=$total_pages;$p++): ?>
                <a href="?page=<?= $p ?>&search=<?= urlencode($search) ?><?= $mine_only?'&mine=1':'' ?>"
                   class="w-7 h-7 flex items-center justify-center rounded-lg text-xs transition-colors <?= $p===$page?'text-white':'text-gray-500 hover:bg-gray-100' ?>"
                   style="<?= $p===$page?'background:#1D9E75':'' ?>"><?= $p ?></a>
                <?php endfor; ?>
            </div>
        </div>
        <?php endif; endif; ?>
    </div>
</main>
</div>

<!-- Read modal -->
<div x-show="modal" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
     :style="modal?'display:flex':'display:none'" @keydown.escape.window="modal=false"
     style="position:fixed;inset:0;z-index:50;background:rgba(0,0,0,.35);align-items:center;justify-content:center">
    <div class="bg-white rounded-2xl w-full max-w-lg mx-4 overflow-hidden" @click.stop>
        <div class="flex items-start justify-between px-5 py-4 border-b border-gray-100">
            <div>
                <p class="text-sm text-gray-800" style="font-weight:500" x-text="item.title"></p>
                <p class="text-xs text-gray-400 mt-0.5" x-text="item.author+' · '+item.date"></p>
            </div>
            <button @click="modal=false" class="text-gray-300 hover:text-gray-500 ml-4"><svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 3l10 10M13 3L3 13"/></svg></button>
        </div>
        <div class="px-5 py-4 overflow-y-auto" style="max-height:60vh">
            <p class="text-sm text-gray-600 leading-relaxed" x-text="item.content"></p>
        </div>
        <div class="px-5 py-3 border-t border-gray-100 flex justify-end">
            <button @click="modal=false" class="btn-ghost">Close</button>
        </div>
    </div>
</div>
</body></html>
