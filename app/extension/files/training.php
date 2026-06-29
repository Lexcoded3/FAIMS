<?php
session_start();
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'extension') { header('Location: /login.php'); exit; }
require_once '../../config/db.php';

$extension_id   = (int)$_SESSION['id'];
$extension_name = $_SESSION['name'] ?? 'Extension Worker';
$active_page    = 'training.php';

// Handle delete course
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_course') {
    $cid = (int)$_POST['course_id'];
    // Only allow deleting own courses
    $conn->query("DELETE FROM training_courses WHERE id=$cid AND created_by=$extension_id");
    // Lessons + progress cascade if FK set; otherwise:
    $conn->query("DELETE FROM training_progress WHERE lesson_id IN (SELECT id FROM training_lessons WHERE course_id=$cid)");
    $conn->query("DELETE FROM training_lessons WHERE course_id=$cid");
    header('Location: training.php'); exit;
}

$search   = $conn->real_escape_string(trim($_GET['search'] ?? ''));
$filter   = $conn->real_escape_string(trim($_GET['category'] ?? ''));

$where = "WHERE 1=1";
if ($search !== '') $where .= " AND (tc.title LIKE '%$search%' OR tc.description LIKE '%$search%')";
if ($filter !== '') $where .= " AND tc.category='$filter'";

// Courses with lesson count + progress count
$courses = [];
$res = $conn->query("
    SELECT tc.*,
           u.name AS author,
           COUNT(DISTINCT tl.id)           AS lesson_count,
           COUNT(DISTINCT tp.id)           AS progress_count,
           SUM(tp.status='completed')      AS completed_count
    FROM training_courses tc
    LEFT JOIN users u           ON u.id = tc.created_by
    LEFT JOIN training_lessons tl ON tl.course_id = tc.id
    LEFT JOIN training_progress tp ON tp.lesson_id = tl.id
    $where
    GROUP BY tc.id
    ORDER BY tc.created_at DESC
");
while ($r = $res->fetch_assoc()) $courses[] = $r;

// Distinct categories
$categories = [];
$res = $conn->query("SELECT DISTINCT category FROM training_courses WHERE category IS NOT NULL ORDER BY category");
while ($r = $res->fetch_assoc()) $categories[] = $r['category'];

// Stats
$total_courses  = count($courses);
$total_lessons  = $conn->query("SELECT COUNT(*) AS c FROM training_lessons tl JOIN training_courses tc ON tc.id=tl.course_id")->fetch_assoc()['c'];
$total_enrolled = $conn->query("SELECT COUNT(DISTINCT user_id) AS c FROM training_progress")->fetch_assoc()['c'];
$my_courses     = $conn->query("SELECT COUNT(*) AS c FROM training_courses WHERE created_by=$extension_id")->fetch_assoc()['c'];

$category_colors = [
    'agronomy'    => ['bg'=>'#EAF3DE','text'=>'#3B6D11'],
    'pest'        => ['bg'=>'#FCEBEB','text'=>'#A32D2D'],
    'irrigation'  => ['bg'=>'#E6F1FB','text'=>'#185FA5'],
    'business'    => ['bg'=>'#FAEEDA','text'=>'#854F0B'],
    'soil'        => ['bg'=>'#FAEEDA','text'=>'#633806'],
    'default'     => ['bg'=>'#F1EFE8','text'=>'#5F5E5A'],
];
function cat_style(string $cat, array $map): string {
    $key = strtolower($cat);
    $c   = $map[$key] ?? $map['default'];
    return "background:{$c['bg']};color:{$c['text']}";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>Training Courses — FAIMS Extension</title>
<?php include '../_head.php'; ?>
<style>
.course-card{background:white;border:1px solid #f3f4f6;border-radius:14px;overflow:hidden;transition:border-color .15s,transform .15s}
.course-card:hover{border-color:#d1d5db;transform:translateY(-1px)}
.thumb{width:100%;height:120px;object-fit:cover;background:#f3f4f6;display:flex;align-items:center;justify-content:center}
.thumb-placeholder{width:100%;height:120px;display:flex;align-items:center;justify-content:center}
.progress-ring{transform:rotate(-90deg)}
</style>
</head>
<body class="bg-gray-50 text-gray-800">
<div class="flex h-screen overflow-hidden">
<?php include '_sidebar.php'; ?>

<main class="flex-1 flex flex-col overflow-hidden">
    <header class="bg-white border-b border-gray-100 px-6 py-4 flex items-center justify-between flex-shrink-0">
        <div>
            <h1 class="text-base text-gray-800" style="font-weight:500">Training courses</h1>
            <p class="text-xs text-gray-400 mt-0.5">Build and manage courses for farmers</p>
        </div>
        <a href="training_course_edit.php" class="btn-primary">
            <svg width="12" height="12" viewBox="0 0 12 12" fill="none" stroke="white" stroke-width="1.8"><path d="M6 1v10M1 6h10"/></svg>
            New course
        </a>
    </header>

    <div class="flex-1 overflow-y-auto scrollbar-hide px-6 py-5 fade-in">

        <!-- Stats row -->
        <div class="grid grid-cols-4 gap-4 mb-5">
            <div class="bg-white rounded-xl border border-gray-100 p-4">
                <p class="text-xs text-gray-400 mb-1">Total courses</p>
                <p class="mono text-2xl text-gray-800" style="font-weight:500"><?= $total_courses ?></p>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 p-4">
                <p class="text-xs text-gray-400 mb-1">Total lessons</p>
                <p class="mono text-2xl text-gray-800" style="font-weight:500"><?= $total_lessons ?></p>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 p-4">
                <p class="text-xs text-gray-400 mb-1">Farmers enrolled</p>
                <p class="mono text-2xl text-gray-800" style="font-weight:500"><?= $total_enrolled ?></p>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 p-4">
                <p class="text-xs text-gray-400 mb-1">My courses</p>
                <p class="mono text-2xl text-gray-800" style="font-weight:500"><?= $my_courses ?></p>
            </div>
        </div>

        <!-- Filters -->
        <form method="GET" class="flex items-center gap-3 mb-5">
            <div class="relative flex-1 max-w-xs">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" width="13" height="13" viewBox="0 0 13 13" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="5.5" cy="5.5" r="4"/><path d="M9 9l2.5 2.5"/></svg>
                <input type="text" name="search" value="<?= htmlspecialchars($_GET['search']??'') ?>" placeholder="Search courses…" style="padding-left:32px">
            </div>
            <select name="category" style="width:auto">
                <option value="">All categories</option>
                <?php foreach($categories as $cat): ?>
                <option value="<?= htmlspecialchars($cat) ?>" <?= ($filter===$cat)?'selected':'' ?>><?= ucfirst($cat) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-ghost">Filter</button>
            <?php if($search||$filter): ?><a href="training.php" class="text-xs text-gray-400 hover:text-gray-600">Clear</a><?php endif; ?>
        </form>

        <!-- Course cards -->
        <?php if(empty($courses)): ?>
        <div class="text-center py-16">
            <div class="w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-4" style="background:#E1F5EE">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#1D9E75" stroke-width="1.5"><rect x="3" y="4" width="18" height="14" rx="2"/><path d="M8 18v2M16 18v2M6 20h12"/></svg>
            </div>
            <p class="text-sm text-gray-500 mb-1" style="font-weight:500">No courses yet</p>
            <p class="text-xs text-gray-400 mb-4">Create your first course to start training farmers</p>
            <a href="training_course_edit.php" class="btn-primary">Create first course</a>
        </div>
        <?php else: ?>
        <div class="grid grid-cols-3 gap-4">
            <?php foreach($courses as $c):
                $is_mine     = (int)$c['created_by'] === $extension_id;
                $lessons     = (int)$c['lesson_count'];
                $enrolled    = (int)$c['progress_count'];
                $completed   = (int)$c['completed_count'];
                $pct         = ($enrolled > 0) ? min(100, round(($completed / $enrolled) * 100)) : 0;
                $thumb_url   = $c['thumbnail'] ? '../../uploads/thumbnails/'.htmlspecialchars($c['thumbnail']) : null;
                $cat         = $c['category'] ?? 'general';
                $cat_s       = cat_style($cat, $category_colors);
            ?>
            <div class="course-card">
                <!-- Thumbnail -->
                <?php if($thumb_url && file_exists($thumb_url)): ?>
                <img src="<?= $thumb_url ?>" alt="" style="width:100%;height:120px;object-fit:cover">
                <?php else: ?>
                <div class="thumb-placeholder" style="background:linear-gradient(135deg,#E1F5EE 0%,#9FE1CB 100%)">
                    <svg width="32" height="32" viewBox="0 0 32 32" fill="none" stroke="#1D9E75" stroke-width="1.2" opacity=".6"><rect x="4" y="6" width="24" height="18" rx="2"/><path d="M10 24v3M22 24v3M8 27h16"/><path d="M12 13l4 3 4-3"/></svg>
                </div>
                <?php endif; ?>

                <div class="p-4">
                    <div class="flex items-start justify-between gap-2 mb-2">
                        <span class="tag" style="<?= $cat_s ?>;font-size:10px"><?= ucfirst($cat) ?></span>
                        <?php if($is_mine): ?>
                        <span class="text-gray-400" style="font-size:10px;font-weight:500">Yours</span>
                        <?php endif; ?>
                    </div>

                    <h3 class="text-sm text-gray-800 leading-snug mb-1" style="font-weight:500"><?= htmlspecialchars($c['title']) ?></h3>
                    <p class="text-xs text-gray-400 leading-relaxed mb-3" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden">
                        <?= htmlspecialchars($c['description'] ?? '') ?>
                    </p>

                    <!-- Meta row -->
                    <div class="flex items-center gap-3 mb-3 text-xs text-gray-400">
                        <span class="flex items-center gap-1">
                            <svg width="11" height="11" viewBox="0 0 11 11" fill="none" stroke="currentColor" stroke-width="1.4"><rect x="1" y="1.5" width="9" height="7" rx="1"/><path d="M3.5 8.5v1M7.5 8.5v1M2 9.5h7"/></svg>
                            <?= $lessons ?> lesson<?= $lessons!=1?'s':'' ?>
                        </span>
                        <span class="flex items-center gap-1">
                            <svg width="11" height="11" viewBox="0 0 11 11" fill="none" stroke="currentColor" stroke-width="1.4"><circle cx="5.5" cy="3.5" r="2"/><path d="M1 9.5c0-2.2 2-4 4.5-4s4.5 1.8 4.5 4"/></svg>
                            <?= $enrolled ?> enrolled
                        </span>
                        <span class="mono"><?= date('d M Y', strtotime($c['created_at'])) ?></span>
                    </div>

                    <!-- Progress bar -->
                    <?php if($enrolled > 0): ?>
                    <div class="mb-3">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs text-gray-400">Completion</span>
                            <span class="mono text-xs text-gray-600"><?= $pct ?>%</span>
                        </div>
                        <div class="w-full h-1.5 rounded-full" style="background:#f3f4f6">
                            <div class="h-1.5 rounded-full" style="width:<?= $pct ?>%;background:#1D9E75;transition:width .4s"></div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Actions -->
                    <div class="flex items-center gap-2 pt-2" style="border-top:1px solid #f9fafb">
                        <a href="training_course_edit.php?id=<?= $c['id'] ?>" class="btn-ghost" style="flex:1;justify-content:center;font-size:11px;padding:5px 8px">
                            <svg width="12" height="12" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M2 9.5L4 2l6.5 6.5-7 .5L2 9.5z"/></svg>
                            Edit
                        </a>
                        <a href="training_lesson_edit.php?course_id=<?= $c['id'] ?>" class="btn-ghost" style="flex:1;justify-content:center;font-size:11px;padding:5px 8px">
                            <svg width="12" height="12" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M6 1v10M1 6h10"/></svg>
                            Add lesson
                        </a>
                        <a href="training_progress.php?course_id=<?= $c['id'] ?>" class="btn-ghost" style="flex:1;justify-content:center;font-size:11px;padding:5px 8px">
                            <svg width="12" height="12" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M1.5 9l3-3 2 2 4-5"/></svg>
                            Progress
                        </a>
                        <?php if($is_mine): ?>
                        <form method="POST" onsubmit="return confirm('Delete this course and all its lessons?')">
                            <input type="hidden" name="action" value="delete_course">
                            <input type="hidden" name="course_id" value="<?= $c['id'] ?>">
                            <button type="submit" class="btn-ghost" style="padding:5px 8px;color:#A32D2D;border-color:#fecaca">
                                <svg width="12" height="12" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M2 3h8M4 3V2h4v1"/><path d="M3 3l.5 7h5L9 3"/></svg>
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div>
</main>
</div>
</body>
</html>
