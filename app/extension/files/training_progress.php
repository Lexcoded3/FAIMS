<?php
session_start();
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'extension') { header('Location: /login.php'); exit; }
require_once '../../config/db.php';

$extension_id   = (int)$_SESSION['id'];
$extension_name = $_SESSION['name'] ?? 'Extension Worker';
$active_page    = 'training_progress.php';

$filter_course = (int)($_GET['course_id'] ?? 0);
$filter_search = $conn->real_escape_string(trim($_GET['search'] ?? ''));

// All courses (for filter dropdown)
$courses = [];
$res = $conn->query("SELECT id,title FROM training_courses ORDER BY title");
while ($r = $res->fetch_assoc()) $courses[] = $r;

// Course filter label
$course_title = '';
if ($filter_course > 0) {
    $cr = $conn->query("SELECT title FROM training_courses WHERE id=$filter_course");
    if ($cr && $cr->num_rows > 0) $course_title = $cr->fetch_assoc()['title'];
}

// ── Summary stats ─────────────────────────────────────────────────────────
$total_enrolled  = $conn->query("SELECT COUNT(DISTINCT user_id) AS c FROM training_progress")->fetch_assoc()['c'];
$total_completed = $conn->query("SELECT COUNT(*) AS c FROM training_progress WHERE status='completed'")->fetch_assoc()['c'];
$total_started   = $conn->query("SELECT COUNT(*) AS c FROM training_progress WHERE status='started'")->fetch_assoc()['c'];
$total_lessons   = $conn->query("SELECT COUNT(*) AS c FROM training_lessons")->fetch_assoc()['c'];

// ── Per-farmer progress ────────────────────────────────────────────────────
$course_cond = $filter_course > 0 ? "AND tl.course_id=$filter_course" : '';
$search_cond = $filter_search !== '' ? "AND u.name LIKE '%$filter_search%'" : '';

$farmers = [];
$res = $conn->query("
    SELECT
        u.id, u.name, u.location, u.phone,
        COUNT(DISTINCT tp.lesson_id)                              AS lessons_touched,
        SUM(tp.status='completed')                                AS lessons_completed,
        SUM(tp.status='started')                                  AS lessons_in_progress,
        MAX(tp.started_at)                                        AS last_activity,
        COUNT(DISTINCT tl.course_id)                              AS courses_active
    FROM training_progress tp
    JOIN training_lessons tl ON tl.id = tp.lesson_id $course_cond
    JOIN users u             ON u.id  = tp.user_id   $search_cond
    GROUP BY u.id
    ORDER BY last_activity DESC
");
while ($r = $res->fetch_assoc()) $farmers[] = $r;

// ── Course completion breakdown ────────────────────────────────────────────
$course_stats = [];
$res = $conn->query("
    SELECT
        tc.id, tc.title, tc.category,
        COUNT(DISTINCT tl.id)                           AS total_lessons,
        COUNT(DISTINCT tp.user_id)                      AS enrolled,
        SUM(tp.status='completed')                      AS completions,
        COUNT(DISTINCT CASE WHEN tp.status='completed'
              AND (SELECT COUNT(*) FROM training_lessons x WHERE x.course_id=tc.id)
              = (SELECT COUNT(*) FROM training_progress y
                 JOIN training_lessons z ON z.id=y.lesson_id
                 WHERE z.course_id=tc.id AND y.user_id=tp.user_id AND y.status='completed')
              THEN tp.user_id END)                      AS fully_completed
    FROM training_courses tc
    LEFT JOIN training_lessons tl ON tl.course_id = tc.id
    LEFT JOIN training_progress tp ON tp.lesson_id = tl.id
    GROUP BY tc.id
    ORDER BY enrolled DESC
");
while ($r = $res->fetch_assoc()) $course_stats[] = $r;

// ── Per-lesson heatmap data ────────────────────────────────────────────────
$lesson_stats = [];
$cond = $filter_course > 0 ? "WHERE tl.course_id=$filter_course" : '';
$res = $conn->query("
    SELECT
        tl.id, tl.title, tc.title AS course_title,
        COUNT(tp.id)              AS starts,
        SUM(tp.status='completed') AS completions
    FROM training_lessons tl
    JOIN training_courses tc ON tc.id = tl.course_id
    LEFT JOIN training_progress tp ON tp.lesson_id = tl.id
    $cond
    GROUP BY tl.id
    ORDER BY starts DESC
    LIMIT 10
");
while ($r = $res->fetch_assoc()) $lesson_stats[] = $r;

$avatar_colors = ['#1D9E75','#378ADD','#D85A30','#BA7517','#993556','#0F6E56','#185FA5'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>Farmer Progress — FAIMS Extension</title>
<?php include '../_head.php'; ?>
<style>
.heat-bar{height:6px;border-radius:3px;transition:width .5s ease}
.progress-pill{display:inline-flex;align-items:center;gap:4px;font-size:11px;padding:2px 8px;border-radius:20px;font-weight:500}
.farmer-row:hover{background:#f9fafb}
.ring-wrap{position:relative;display:inline-flex;align-items:center;justify-content:center}
</style>
</head>
<body class="bg-gray-50 text-gray-800" x-data="{ detailFarmer: null, detailModal: false }">
<div class="flex h-screen overflow-hidden">
<?php include '_sidebar.php'; ?>

<main class="flex-1 flex flex-col overflow-hidden">
    <header class="bg-white border-b border-gray-100 px-6 py-4 flex items-center justify-between flex-shrink-0">
        <div>
            <h1 class="text-base text-gray-800" style="font-weight:500">Farmer progress</h1>
            <p class="text-xs text-gray-400 mt-0.5">
                <?= $course_title ? 'Filtered: '.htmlspecialchars($course_title) : 'All courses' ?>
            </p>
        </div>
        <a href="training.php" class="btn-ghost">← Courses</a>
    </header>

    <div class="flex-1 overflow-y-auto scrollbar-hide px-6 py-5 fade-in">

        <!-- Summary stats -->
        <div class="grid grid-cols-4 gap-4 mb-5">
            <div class="bg-navy-500 rounded-xl border border-gray-100 p-4">
                <p class="text-xs text-gray-400 mb-1">Farmers enrolled</p>
                <p class="mono text-2xl text-gray-800" style="font-weight:500"><?= $total_enrolled ?></p>
            </div>
            <div class="bg-navy-500 rounded-xl border border-gray-100 p-4">
                <p class="text-xs text-gray-400 mb-1">Lessons completed</p>
                <p class="mono text-2xl text-gray-800" style="font-weight:500"><?= $total_completed ?></p>
            </div>
            <div class="bg-navy-500 rounded-xl border border-gray-100 p-4">
                <p class="text-xs text-gray-400 mb-1">In progress</p>
                <p class="mono text-2xl text-gray-800" style="font-weight:500"><?= $total_started ?></p>
            </div>
            <div class="bg-navy-500 rounded-xl border border-gray-100 p-4">
                <p class="text-xs text-gray-400 mb-1">Total lessons</p>
                <p class="mono text-2xl text-gray-800" style="font-weight:500"><?= $total_lessons ?></p>
            </div>
        </div>

        <div class="grid grid-cols-3 gap-5">

            <!-- Left: farmer table (2 cols) -->
            <div class="col-span-2 space-y-4">

                <!-- Filters -->
                <form method="GET" class="flex items-center gap-3">
                    <div class="relative flex-1 max-w-xs">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" width="13" height="13" viewBox="0 0 13 13" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="5.5" cy="5.5" r="4"/><path d="M9 9l2.5 2.5"/></svg>
                        <input type="text" name="search" value="<?= htmlspecialchars($_GET['search']??'') ?>" placeholder="Search farmer…" style="padding-left:32px">
                    </div>
                    <select name="course_id" style="width:auto">
                        <option value="0">All courses</option>
                        <?php foreach($courses as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($filter_course===(int)$c['id'])?'selected':'' ?>><?= htmlspecialchars(mb_strimwidth($c['title'],0,35,'…')) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn-ghost">Filter</button>
                    <?php if($filter_search||$filter_course): ?><a href="training_progress.php" class="text-xs text-gray-400 hover:text-gray-600">Clear</a><?php endif; ?>
                </form>

                <!-- Farmer table -->
                <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
                    <table class="w-full">
                        <thead><tr class="border-b border-gray-100">
                            <th class="text-left px-4 py-3 text-xs text-gray-400" style="font-weight:500">Farmer</th>
                            <th class="text-left px-4 py-3 text-xs text-gray-400" style="font-weight:500">Courses</th>
                            <th class="text-left px-4 py-3 text-xs text-gray-400" style="font-weight:500">Completed</th>
                            <th class="text-left px-4 py-3 text-xs text-gray-400" style="font-weight:500">Progress</th>
                            <th class="text-left px-4 py-3 text-xs text-gray-400" style="font-weight:500">Last active</th>
                        </tr></thead>
                        <tbody class="divide-y divide-gray-50">
                        <?php if(empty($farmers)): ?>
                            <tr><td colspan="5" class="px-4 py-10 text-center text-xs text-gray-400">
                                No farmer progress data yet<?= $filter_course?' for this course':'' ?>
                            </td></tr>
                        <?php else: foreach($farmers as $i=>$f):
                            $touched   = max(1, (int)$f['lessons_touched']);
                            $completed = (int)$f['lessons_completed'];
                            $pct       = min(100, round(($completed / $touched) * 100));
                            $color     = $avatar_colors[$i % count($avatar_colors)];
                        ?>
                            <tr class="farmer-row cursor-pointer transition-colors"
                                @click="detailFarmer=<?= json_encode([
                                    'name'       => $f['name'],
                                    'location'   => $f['location']??'',
                                    'phone'      => $f['phone']??'',
                                    'touched'    => $f['lessons_touched'],
                                    'completed'  => $f['lessons_completed'],
                                    'inprogress' => $f['lessons_in_progress'],
                                    'courses'    => $f['courses_active'],
                                    'last'       => $f['last_activity'] ? date('d M Y', strtotime($f['last_activity'])) : '—',
                                    'initials'   => strtoupper(substr($f['name'],0,2)),
                                    'color'      => $color,
                                ]) ?>; detailModal=true">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs text-white flex-shrink-0" style="background:<?= $color ?>;font-weight:500"><?= strtoupper(substr($f['name'],0,1)) ?></div>
                                        <div>
                                            <p class="text-xs text-gray-700" style="font-weight:500"><?= htmlspecialchars($f['name']) ?></p>
                                            <p class="text-xs text-gray-400"><?= htmlspecialchars($f['location']??'') ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="progress-pill" style="background:#E6F1FB;color:#185FA5"><?= $f['courses_active'] ?></span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="progress-pill" style="background:#EAF3DE;color:#3B6D11"><?= $completed ?> / <?= $touched ?></span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1 h-1.5 rounded-full" style="background:#f3f4f6;min-width:60px">
                                            <div class="h-1.5 rounded-full" style="width:<?= $pct ?>%;background:<?= $pct>=80?'#1D9E75':($pct>=40?'#EF9F27':'#E24B4A') ?>;transition:width .4s"></div>
                                        </div>
                                        <span class="mono text-xs text-gray-500"><?= $pct ?>%</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 mono text-xs text-gray-400">
                                    <?= $f['last_activity'] ? date('d M', strtotime($f['last_activity'])) : '—' ?>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Right: Course breakdown + lesson heatmap -->
            <div class="col-span-1 space-y-4">

                <!-- Course breakdown -->
                <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
                    <div class="px-4 py-3 border-b border-gray-100">
                        <p class="text-xs text-gray-700" style="font-weight:500">Courses</p>
                    </div>
                    <div class="p-3 space-y-3">
                        <?php if(empty($course_stats)): ?>
                        <p class="text-xs text-gray-400 text-center py-4">No data yet</p>
                        <?php else: foreach($course_stats as $cs):
                            $enr = max(1, (int)$cs['enrolled']);
                            $pct = min(100, round(((int)$cs['completions'] / ($enr * max(1,(int)$cs['total_lessons']))) * 100));
                        ?>
                        <div>
                            <div class="flex items-start justify-between mb-1">
                                <p class="text-xs text-gray-700 truncate flex-1 pr-2" style="font-weight:500"><?= htmlspecialchars(mb_strimwidth($cs['title'],0,30,'…')) ?></p>
                                <span class="mono text-xs text-gray-400 flex-shrink-0"><?= $cs['enrolled'] ?> enrolled</span>
                            </div>
                            <div class="w-full rounded-full" style="height:5px;background:#f3f4f6">
                                <div class="rounded-full" style="height:5px;width:<?= $pct ?>%;background:#1D9E75;transition:width .4s"></div>
                            </div>
                            <p class="text-xs text-gray-400 mt-0.5"><?= $pct ?>% completion · <?= $cs['total_lessons'] ?> lessons</p>
                        </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>

                <!-- Lesson engagement -->
                <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
                    <div class="px-4 py-3 border-b border-gray-100">
                        <p class="text-xs text-gray-700" style="font-weight:500">Top lessons by engagement</p>
                    </div>
                    <div class="p-3 space-y-2.5">
                        <?php if(empty($lesson_stats)): ?>
                        <p class="text-xs text-gray-400 text-center py-4">No lesson activity yet</p>
                        <?php else:
                            $max_starts = max(1, max(array_column($lesson_stats, 'starts')));
                            foreach($lesson_stats as $ls):
                                $bar_pct = round(((int)$ls['starts'] / $max_starts) * 100);
                                $done_pct = $ls['starts'] > 0 ? round(((int)$ls['completions'] / (int)$ls['starts']) * 100) : 0;
                        ?>
                        <div>
                            <div class="flex items-start justify-between mb-1">
                                <p class="text-xs text-gray-700 truncate flex-1 pr-2"><?= htmlspecialchars(mb_strimwidth($ls['title'],0,28,'…')) ?></p>
                                <span class="mono text-xs text-gray-400 flex-shrink-0"><?= $ls['starts'] ?></span>
                            </div>
                            <div class="flex gap-1 items-center">
                                <div class="flex-1 rounded-full" style="height:4px;background:#f3f4f6">
                                    <div class="heat-bar" style="width:<?= $bar_pct ?>%;background:#1D9E75;height:4px"></div>
                                </div>
                                <span class="text-gray-400" style="font-size:10px;min-width:30px;text-align:right"><?= $done_pct ?>% done</span>
                            </div>
                        </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>
</main>
</div>

<!-- Farmer detail modal -->
<div x-show="detailModal"
     x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
     :style="detailModal?'display:flex':'display:none'" @keydown.escape.window="detailModal=false"
     style="position:fixed;inset:0;z-index:50;background:rgba(0,0,0,.35);align-items:center;justify-content:center">
    <div class="bg-white rounded-2xl w-full max-w-sm mx-4 overflow-hidden" @click.stop x-show="detailFarmer">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm text-white" :style="'background:'+detailFarmer?.color" style="font-weight:500" x-text="detailFarmer?.initials"></div>
                <div>
                    <p class="text-sm text-gray-800" style="font-weight:500" x-text="detailFarmer?.name"></p>
                    <p class="text-xs text-gray-400" x-text="detailFarmer?.location"></p>
                </div>
            </div>
            <button @click="detailModal=false" class="text-gray-300 hover:text-gray-500"><svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 3l10 10M13 3L3 13"/></svg></button>
        </div>
        <div class="px-5 py-4">
            <div class="grid grid-cols-2 gap-3 mb-4">
                <div class="rounded-lg p-3 text-center" style="background:#EAF3DE">
                    <p class="mono text-xl" style="font-weight:500;color:#3B6D11" x-text="detailFarmer?.completed"></p>
                    <p class="text-xs mt-0.5" style="color:#3B6D11">Completed</p>
                </div>
                <div class="rounded-lg p-3 text-center" style="background:#E6F1FB">
                    <p class="mono text-xl" style="font-weight:500;color:#185FA5" x-text="detailFarmer?.inprogress"></p>
                    <p class="text-xs mt-0.5" style="color:#185FA5">In progress</p>
                </div>
            </div>
            <div class="space-y-1">
                <div class="flex justify-between py-1.5" style="border-bottom:1px solid #f9fafb">
                    <span class="text-xs text-gray-400">Lessons touched</span>
                    <span class="mono text-xs text-gray-700" style="font-weight:500" x-text="detailFarmer?.touched"></span>
                </div>
                <div class="flex justify-between py-1.5" style="border-bottom:1px solid #f9fafb">
                    <span class="text-xs text-gray-400">Active courses</span>
                    <span class="mono text-xs text-gray-700" style="font-weight:500" x-text="detailFarmer?.courses"></span>
                </div>
                <div class="flex justify-between py-1.5" style="border-bottom:1px solid #f9fafb">
                    <span class="text-xs text-gray-400">Phone</span>
                    <span class="mono text-xs text-gray-700" style="font-weight:500" x-text="detailFarmer?.phone||'—'"></span>
                </div>
                <div class="flex justify-between py-1.5">
                    <span class="text-xs text-gray-400">Last active</span>
                    <span class="mono text-xs text-gray-700" style="font-weight:500" x-text="detailFarmer?.last"></span>
                </div>
            </div>
        </div>
        <div class="px-5 py-3 border-t border-gray-100 flex justify-end">
            <button @click="detailModal=false" class="btn-ghost">Close</button>
        </div>
    </div>
</div>

</body>
</html>
