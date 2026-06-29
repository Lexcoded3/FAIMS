<?php
session_start();
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'extension') { header('Location: /login.php'); exit; }
require_once '../../config/db.php';

$extension_id   = (int)$_SESSION['id'];
$extension_name = $_SESSION['name'] ?? 'Extension Worker';
$active_page    = 'training.php';

$edit_id   = (int)($_GET['id'] ?? 0);
$course    = null;
$success   = false;
$errors    = [];

if ($edit_id > 0) {
    $res = $conn->query("SELECT * FROM training_courses WHERE id=$edit_id");
    if (!$res || $res->num_rows === 0) { header('Location: training.php'); exit; }
    $course = $res->fetch_assoc();
}

// Fetch lessons for this course (shown in edit mode)
$lessons = [];
if ($edit_id > 0) {
    $res = $conn->query("SELECT * FROM training_lessons WHERE course_id=$edit_id ORDER BY id ASC");
    while ($r = $res->fetch_assoc()) $lessons[] = $r;
}

// Handle delete lesson
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_lesson') {
    $lid = (int)$_POST['lesson_id'];
    $conn->query("DELETE FROM training_progress WHERE lesson_id=$lid");
    $conn->query("DELETE FROM training_lessons WHERE id=$lid AND course_id=$edit_id");
    header("Location: training_course_edit.php?id=$edit_id&deleted=1"); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_course') {
    $title       = trim($_POST['title']       ?? '');
    $description = trim($_POST['description'] ?? '');
    $category    = trim($_POST['category']    ?? '');

    if ($title === '') $errors[] = 'Course title is required.';

    // Thumbnail upload
    $thumb_filename = $course['thumbnail'] ?? null;
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
        $file    = $_FILES['thumbnail'];
        $allowed = ['image/jpeg','image/png','image/webp'];
        $max     = 3 * 1024 * 1024;
        if (!in_array($file['type'], $allowed))   $errors[] = 'Thumbnail must be JPG, PNG or WebP.';
        elseif ($file['size'] > $max)              $errors[] = 'Thumbnail must be under 3MB.';
        else {
            $ext  = pathinfo($file['name'], PATHINFO_EXTENSION);
            $name = 'thumb_' . time() . '_' . $extension_id . '.' . $ext;
            $dir  = '../../uploads/thumbnails/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            if (move_uploaded_file($file['tmp_name'], $dir . $name)) $thumb_filename = $name;
            else $errors[] = 'Could not save thumbnail.';
        }
    }

    if (empty($errors)) {
        $st = $conn->real_escape_string($title);
        $sd = $conn->real_escape_string($description);
        $sc = $conn->real_escape_string($category);
        $sf = $conn->real_escape_string($thumb_filename ?? '');

        if ($edit_id > 0) {
            $conn->query("UPDATE training_courses SET title='$st',description='$sd',category='$sc',thumbnail='$sf' WHERE id=$edit_id");
            $success = true;
            $course  = $conn->query("SELECT * FROM training_courses WHERE id=$edit_id")->fetch_assoc();
        } else {
            $conn->query("INSERT INTO training_courses (title,description,category,thumbnail,created_by) VALUES ('$st','$sd','$sc','$sf',$extension_id)");
            $new_id  = $conn->insert_id;
            header("Location: training_course_edit.php?id=$new_id&created=1"); exit;
        }
    }
}

$v_title       = $course['title']       ?? ($_POST['title']       ?? '');
$v_description = $course['description'] ?? ($_POST['description'] ?? '');
$v_category    = $course['category']    ?? ($_POST['category']    ?? '');
$thumb_url     = ($course['thumbnail'] ?? '') ? '../../uploads/thumbnails/'.htmlspecialchars($course['thumbnail']) : null;

$course_categories = ['agronomy','pest management','irrigation','soil health','business','post-harvest','nutrition','climate'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title><?= $edit_id?'Edit':'New' ?> Course — FAIMS Extension</title>
<?php include '../_head.php'; ?>
<style>
.lesson-row{display:flex;align-items:center;gap:12px;padding:10px 14px;background:white;border:1px solid #f3f4f6;border-radius:10px;transition:border-color .15s}
.lesson-row:hover{border-color:#d1d5db}
.lesson-num{width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:500;flex-shrink:0;background:#E1F5EE;color:#0F6E56}
.drop-zone{border:2px dashed #e5e7eb;border-radius:10px;transition:border-color .2s,background .2s;cursor:pointer}
.drop-zone:hover,.drop-zone.dragging{border-color:#1D9E75;background:#f0fdf8}
</style>
</head>
<body class="bg-gray-50 text-gray-800"
      x-data="{ thumbPreview: null, dragging: false,
        previewThumb(e){ const f=e.target.files[0]; if(!f) return; const r=new FileReader(); r.onload=ev=>this.thumbPreview=ev.target.result; r.readAsDataURL(f); } }">
<div class="flex h-screen overflow-hidden">
<?php include '_sidebar.php'; ?>

<main class="flex-1 flex flex-col overflow-hidden">
    <header class="bg-white border-b border-gray-100 px-6 py-4 flex items-center justify-between flex-shrink-0">
        <div>
            <h1 class="text-base text-gray-800" style="font-weight:500"><?= $edit_id?'Edit course':'New course' ?></h1>
            <p class="text-xs text-gray-400 mt-0.5"><?= $edit_id?htmlspecialchars($course['title']??''):'Set up a new training course for farmers' ?></p>
        </div>
        <div class="flex items-center gap-3">
            <?php if($success): ?>
            <span class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs" style="background:#E1F5EE;color:#0F6E56">
                <svg width="13" height="13" viewBox="0 0 13 13" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="6.5" cy="6.5" r="5.5"/><path d="M4 6.5l2 2 3.5-3.5"/></svg>
                Saved
            </span>
            <?php endif; ?>
            <a href="training.php" class="btn-ghost">← All courses</a>
        </div>
    </header>

    <div class="flex-1 overflow-y-auto scrollbar-hide px-6 py-6 fade-in">
        <?php if(!empty($errors)): ?>
        <div class="px-4 py-3 rounded-xl mb-5" style="background:#FCEBEB;color:#A32D2D;font-size:12px">
            <?php foreach($errors as $e): ?><p><?= htmlspecialchars($e) ?></p><?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if(isset($_GET['created'])): ?>
        <div class="flex items-center gap-3 px-4 py-3 rounded-xl mb-5 text-xs" style="background:#E1F5EE;color:#0F6E56">
            <svg width="13" height="13" viewBox="0 0 13 13" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="6.5" cy="6.5" r="5.5"/><path d="M4 6.5l2 2 3.5-3.5"/></svg>
            Course created! Now add some lessons below.
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-3 gap-6">

            <!-- Left: Course form (2 cols) -->
            <div class="col-span-2">
                <form method="POST" enctype="multipart/form-data" class="space-y-5">
                    <input type="hidden" name="action" value="save_course">

                    <div class="bg-white rounded-xl border border-gray-100 p-5">
                        <p class="text-xs text-gray-400 mb-4" style="font-weight:500;text-transform:uppercase;letter-spacing:.05em">Course details</p>

                        <div class="mb-4">
                            <label class="field-label" for="title">Course title <span style="color:#A32D2D">*</span></label>
                            <input type="text" id="title" name="title" value="<?= htmlspecialchars($v_title) ?>"
                                   placeholder="e.g. Modern maize farming techniques" required>
                        </div>

                        <div class="mb-4">
                            <label class="field-label" for="description">Description</label>
                            <textarea id="description" name="description" style="min-height:100px"
                                      placeholder="What will farmers learn? Who is this course for?"><?= htmlspecialchars($v_description) ?></textarea>
                        </div>

                        <div>
                            <label class="field-label" for="category">Category</label>
                            <select id="category" name="category">
                                <option value="">— Select category —</option>
                                <?php foreach($course_categories as $cat): ?>
                                <option value="<?= $cat ?>" <?= ($v_category===$cat)?'selected':'' ?>><?= ucfirst($cat) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Thumbnail -->
                    <div class="bg-white rounded-xl border border-gray-100 p-5">
                        <p class="text-xs text-gray-400 mb-4" style="font-weight:500;text-transform:uppercase;letter-spacing:.05em">Course thumbnail</p>

                        <div class="drop-zone p-5 text-center"
                             :class="dragging?'dragging':''"
                             @dragover.prevent="dragging=true" @dragleave="dragging=false"
                             @drop.prevent="dragging=false; previewThumb({target:{files:$event.dataTransfer.files}})"
                             @click="$refs.thumbInput.click()">
                            <template x-if="thumbPreview">
                                <img :src="thumbPreview" class="mx-auto rounded-lg mb-2" style="max-height:140px;max-width:100%;object-fit:cover">
                            </template>
                            <template x-if="!thumbPreview">
                                <?php if($thumb_url && file_exists($thumb_url)): ?>
                                <img src="<?= $thumb_url ?>" class="mx-auto rounded-lg mb-2" style="max-height:140px;max-width:100%;object-fit:cover">
                                <?php else: ?>
                                <div class="py-6">
                                    <svg class="mx-auto mb-2" width="28" height="28" viewBox="0 0 28 28" fill="none" stroke="#9ca3af" stroke-width="1.3"><rect x="3" y="5" width="22" height="16" rx="2"/><circle cx="9" cy="11" r="2"/><path d="M3 18l6-5 4 4 3-3 6 5"/></svg>
                                    <p class="text-xs text-gray-400">Click or drag to upload thumbnail</p>
                                    <p class="text-xs text-gray-400 mt-0.5">JPG, PNG or WebP · max 3MB</p>
                                </div>
                                <?php endif; ?>
                            </template>
                            <input type="file" name="thumbnail" accept="image/jpeg,image/png,image/webp" class="hidden" x-ref="thumbInput" @change="previewThumb($event)">
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <button type="submit" class="btn-primary">
                            <svg width="13" height="13" viewBox="0 0 13 13" fill="none" stroke="white" stroke-width="1.6"><path d="M1.5 6.5l4 4 6-8"/></svg>
                            <?= $edit_id?'Save changes':'Create course' ?>
                        </button>
                        <?php if($edit_id): ?>
                        <a href="training_lesson_edit.php?course_id=<?= $edit_id ?>" class="btn-ghost">
                            <svg width="12" height="12" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M6 1v10M1 6h10"/></svg>
                            Add new lesson
                        </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Right: Lessons panel -->
            <div class="col-span-1">
                <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
                    <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                        <p class="text-xs text-gray-700" style="font-weight:500">
                            Lessons
                            <?php if(!empty($lessons)): ?>
                            <span class="mono ml-1 text-gray-400"><?= count($lessons) ?></span>
                            <?php endif; ?>
                        </p>
                        <?php if($edit_id): ?>
                        <a href="training_lesson_edit.php?course_id=<?= $edit_id ?>" class="text-xs" style="color:#1D9E75;font-weight:500">+ Add</a>
                        <?php endif; ?>
                    </div>

                    <?php if(!$edit_id): ?>
                    <div class="px-4 py-8 text-center">
                        <p class="text-xs text-gray-400">Save the course first,<br>then add lessons.</p>
                    </div>
                    <?php elseif(empty($lessons)): ?>
                    <div class="px-4 py-8 text-center">
                        <p class="text-xs text-gray-400 mb-3">No lessons yet</p>
                        <a href="training_lesson_edit.php?course_id=<?= $edit_id ?>" class="btn-primary" style="font-size:11px;padding:5px 12px">Add first lesson</a>
                    </div>
                    <?php else: ?>
                    <div class="p-3 space-y-2">
                        <?php foreach($lessons as $i=>$l): ?>
                        <div class="lesson-row">
                            <div class="lesson-num"><?= $i+1 ?></div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs text-gray-700 truncate" style="font-weight:500"><?= htmlspecialchars($l['title']) ?></p>
                                <div class="flex items-center gap-2 mt-0.5">
                                    <?php if($l['video']): ?><span class="text-gray-400" style="font-size:10px">▶ Video</span><?php endif; ?>
                                    <?php if($l['pdf']): ?>  <span class="text-gray-400" style="font-size:10px">📄 PDF</span><?php endif; ?>
                                </div>
                            </div>
                            <div class="flex items-center gap-1 flex-shrink-0">
                                <a href="training_lesson_edit.php?id=<?= $l['id'] ?>&course_id=<?= $edit_id ?>" class="text-gray-400 hover:text-gray-600 transition-colors">
                                    <svg width="13" height="13" viewBox="0 0 13 13" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M2 9.5L4 2l6.5 6.5-7 .5L2 9.5z"/></svg>
                                </a>
                                <form method="POST" onsubmit="return confirm('Delete this lesson?')">
                                    <input type="hidden" name="action"    value="delete_lesson">
                                    <input type="hidden" name="lesson_id" value="<?= $l['id'] ?>">
                                    <button type="submit" class="text-gray-300 hover:text-red-400 transition-colors" style="background:none;border:none;cursor:pointer;padding:2px">
                                        <svg width="13" height="13" viewBox="0 0 13 13" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M2 3.5h9M4 3.5V2.5h5v1"/><path d="M3 3.5l.5 7.5h6L10 3.5"/></svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if($edit_id): ?>
                <div class="mt-4">
                    <a href="training_progress.php?course_id=<?= $edit_id ?>"
                       class="flex items-center justify-center gap-2 w-full py-2.5 rounded-xl border border-gray-200 text-xs text-gray-600 hover:bg-gray-50 transition-colors">
                        <svg width="13" height="13" viewBox="0 0 13 13" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M1.5 9.5l3.5-4 2 2 4-5"/></svg>
                        View farmer progress
                    </a>
                </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</main>
</div>
</body>
</html>
