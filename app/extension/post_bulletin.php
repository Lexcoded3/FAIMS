<?php
session_start();
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'extension') { header("Location: ../auth"); exit; }
require_once '../config/db.php';

$extension_id   = $_SESSION['id'];
$extension_name = $_SESSION['name'] ?? 'Extension Worker';
$active_page    = 'post_bulletin.php';

$success = false;
$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title   = trim($_POST['title']   ?? '');
    $content = trim($_POST['content'] ?? '');
    if ($title === '')   $errors[] = 'Title is required.';
    if ($content === '') $errors[] = 'Content is required.';
    if (empty($errors)) {
        $st = $conn->real_escape_string($title);
        $sc = $conn->real_escape_string($content);
        $conn->query("INSERT INTO posts (user_id,title,content) VALUES ($extension_id,'$st','$sc')");
        $success = true;
    }
}

$v_title   = $success ? '' : ($_POST['title']   ?? '');
$v_content = $success ? '' : ($_POST['content'] ?? '');
?>
<!DOCTYPE html><html lang="en"><head>
<title>Post Bulletin — FAIMS Extension</title>
<?php include '_head.php'; ?>
</head>
<body class="bg-gray-50 text-gray-800">
<div class="flex h-screen overflow-hidden">
<?php include '_sidebar.php'; ?>

<main class="flex-1 flex flex-col overflow-hidden">
    <header class="bg-white border-b border-gray-100 px-6 py-4 flex items-center justify-between flex-shrink-0">
        <div>
            <h1 class="text-base text-gray-800" style="font-weight:500">Post agri bulletin</h1>
            <p class="text-xs text-gray-400 mt-0.5">Share disease alerts, best practices, seasonal advice</p>
        </div>
        <a href="bulletins.php" class="text-xs text-gray-400 hover:text-gray-600">← All bulletins</a>
    </header>

    <div class="flex-1 overflow-y-auto scrollbar-hide px-6 py-6 fade-in"
         x-data="{ title: <?= json_encode($v_title) ?>, content: <?= json_encode($v_content) ?>, len: <?= strlen($v_content) ?> }">
        <div class="max-w-2xl mx-auto">

            <?php if($success): ?>
            <div class="flex items-center gap-3 px-4 py-3 rounded-xl mb-5 text-xs" style="background:#E1F5EE;color:#0F6E56">
                <svg width="15" height="15" viewBox="0 0 15 15" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="7.5" cy="7.5" r="6"/><path d="M4.5 7.5l2 2 4-4"/></svg>
                Bulletin published successfully.
                <a href="bulletins.php" class="ml-auto underline" style="color:#0F6E56">View all bulletins</a>
            </div>
            <?php endif; ?>

            <?php if(!empty($errors)): ?>
            <div class="px-4 py-3 rounded-xl mb-5" style="background:#FCEBEB;color:#A32D2D">
                <?php foreach($errors as $e): ?><p class="text-xs"><?= htmlspecialchars($e) ?></p><?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Type chips (visual guide) -->
            <div class="flex flex-wrap gap-2 mb-5">
                <span class="tag" style="background:#FCEBEB;color:#A32D2D">Disease alert</span>
                <span class="tag" style="background:#EAF3DE;color:#3B6D11">Best practice</span>
                <span class="tag" style="background:#E6F1FB;color:#185FA5">Seasonal advisory</span>
                <span class="tag" style="background:#FAEEDA;color:#854F0B">Market info</span>
                <span class="tag" style="background:#F1EFE8;color:#5F5E5A">General info</span>
                
            </div>
            <p class="text-xs text-gray-400 mb-5">The type is auto-detected from your title — no need to select one.</p>

            <form method="POST" class="space-y-5">
                <div>
                    <label class="field-label" for="title">Bulletin title</label>
                    <input type="text" id="title" name="title" x-model="title" value="<?= htmlspecialchars($v_title) ?>"
                           placeholder="e.g. Fall armyworm alert — Wakiso district" required>
                </div>
                <div>
                    <label class="field-label" for="content">Content</label>
                    <textarea id="content" name="content" x-model="content" @input="len=$el.value.length"
                              style="min-height:220px"
                              placeholder="Describe the situation, affected crops or areas, what farmers should do, and any resources…"
                              required><?= htmlspecialchars($v_content) ?></textarea>
                    <div class="flex items-center justify-between mt-1">
                        <span class="text-xs text-gray-400">Be specific — name crop types, sub-counties, and actionable advice.</span>
                        <span class="mono text-xs text-gray-400" x-text="len+' chars'"></span>
                    </div>
                </div>

                <!-- Live preview -->
                <div class="rounded-xl border border-gray-100 p-4 bg-white" x-show="title.length>0||content.length>0">
                    <p class="text-xs text-gray-400 mb-2">Preview</p>
                    <p class="text-sm text-gray-800 leading-snug" style="font-weight:500" x-text="title||'—'"></p>
                    <p class="text-xs text-gray-500 mt-1 leading-relaxed" x-text="content?content.substring(0,160)+(content.length>160?'…':''):''"></p>
                    <p class="text-xs text-gray-400 mt-2"><?= htmlspecialchars($extension_name) ?> · <?= date('d M Y') ?></p>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="btn-primary">
                        <svg width="13" height="13" viewBox="0 0 13 13" fill="none" stroke="white" stroke-width="1.6"><path d="M1 9.5L3.5 2l8 8-8.5 1L1 9.5z"/></svg>
                        Publish bulletin
                    </button>
                    <a href="bulletins.php" class="text-xs text-gray-400 hover:text-gray-600">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</main>
</div>
</body></html>
