<?php
session_start();
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'extension') { header("Location: ../auth"); exit; }
require_once '../config/db.php';

$extension_id   = (int)$_SESSION['id'];
$extension_name = $_SESSION['name'] ?? 'Extension Worker';
$active_page    = 'training.php';

$edit_id   = (int)($_GET['id'] ?? 0);
$course    = null;
$success   = false;
$errors    = [];

// Toast messages
$toast = null;
if (isset($_GET['created'])) $toast = ['type' => 'success', 'msg' => 'Course created successfully!'];
if (isset($_GET['deleted'])) $toast = ['type' => 'warning', 'msg' => 'Lesson removed.'];

if ($edit_id > 0) {
    $res = $conn->query("SELECT * FROM training_courses WHERE id=$edit_id");
    if (!$res || $res->num_rows === 0) { header('Location: training.php'); exit; }
    $course = $res->fetch_assoc();
}

$lessons = [];
if ($edit_id > 0) {
    $res = $conn->query("SELECT * FROM training_lessons WHERE course_id=$edit_id ORDER BY id ASC");
    while ($r = $res->fetch_assoc()) $lessons[] = $r;
}

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

    $thumb_filename = $course['thumbnail'] ?? null;
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
        $file    = $_FILES['thumbnail'];
        $allowed = ['image/jpeg','image/png','image/webp'];
        if (!in_array($file['type'], $allowed)) $errors[] = 'Thumbnail must be JPG, PNG or WebP.';
        else {
            $ext  = pathinfo($file['name'], PATHINFO_EXTENSION);
            $name = 'thumb_' . time() . '_' . $extension_id . '.' . $ext;
            $dir  = '../uploads/thumbnails/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            if (move_uploaded_file($file['tmp_name'], $dir . $name)) $thumb_filename = $name;
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
$thumb_url     = ($course['thumbnail'] ?? '') ? '../uploads/thumbnails/'.htmlspecialchars($course['thumbnail']) : null;
$course_categories = ['agronomy','pest management','irrigation','soil health','business','post-harvest','nutrition','climate'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAIMS - <?= $edit_id ? 'Edit' : 'New' ?> Course</title>
    <link rel="stylesheet" href="../css/app.css">
    <script src="../js/app.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
        localStorage.getItem("_x_darkMode_on") === "true" && document.documentElement.classList.add("dark");
    </script>
    <style>
        .lesson-row { @apply flex items-center justify-between p-3 rounded-lg border border-slate-150 dark:border-navy-500 hover:bg-slate-50 dark:hover:bg-navy-600 transition-all; }
        .lesson-num { @apply flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-100 text-[10px] font-bold text-slate-500 dark:bg-navy-500 dark:text-navy-200 mr-3; }
    </style>
</head>

<body x-data="{ thumbPreview: null, dragging: false }" 
      x-init="<?php if($toast): ?> $notification({text:'<?= $toast['msg'] ?>',variant:'<?= $toast['type'] ?>',position:'right-top'}); <?php endif; ?>
              <?php if($success): ?> $notification({text:'Changes saved successfully!',variant:'success',position:'right-top'}); <?php endif; ?>"
      class="is-header-blur is-sidebar-open" x-bind="$store.global.documentBody">

    <div id="root" class="min-h-100vh flex grow bg-slate-50 dark:bg-navy-900" x-cloak>
        <div class="sidebar print:hidden">
            <div class="main-sidebar">
                <div class="flex h-full w-full flex-col items-center border-r border-slate-150 bg-white dark:border-navy-700 dark:bg-navy-800">
                    <div class="flex pt-4">
                        <a href="index.php"><img class="size-11" src="../images/app-logo.png" alt="logo"></a>
                    </div>
                    <?php include 'sidenav.php';?>
                </div>
            </div>
            <?php include 'trainingsider.php';?>
        </div>

        <?php include 'toprightsidenav.php';?>

        <main class="main-content w-full px-[var(--margin-x)] pb-8">
            <div class="flex flex-col items-center justify-between space-y-4 py-5 sm:flex-row sm:space-y-0 lg:py-6">
                <div class="flex items-center space-x-2">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary dark:bg-accent-light/10 dark:text-accent-light">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-semibold text-slate-700 dark:text-navy-50">
                            <?= $edit_id ? 'Edit Course' : 'Create New Course' ?>
                        </h2>
                        <p class="text-xs text-slate-400 dark:text-navy-300">Set up your curriculum and materials</p>
                    </div>
                </div>
                <div class="flex space-x-2">
                    <a href="training.php" class="btn border border-slate-300 font-medium text-slate-700 hover:bg-slate-150 dark:border-navy-450 dark:text-navy-100 dark:hover:bg-navy-500">
                        Back to List
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-12 gap-4 sm:gap-5 lg:gap-6">
                <div class="col-span-12 lg:col-span-8">
                    <div class="card p-4 sm:p-5">
                        <form method="POST" enctype="multipart/form-data" class="space-y-5">
                            <input type="hidden" name="action" value="save_course">
                            
                            <?php if(!empty($errors)): ?>
                            <div class="rounded-lg bg-error/10 p-4 text-error">
                                <ul class="list-inside list-disc text-sm">
                                    <?php foreach($errors as $e): ?><li><?= $e ?></li><?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endif; ?>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <label class="block">
                                    <span class="font-medium text-slate-600 dark:text-navy-100">Course Title</span>
                                    <input class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 focus:border-primary dark:border-navy-450 dark:focus:border-accent" 
                                           type="text" name="title" value="<?= htmlspecialchars($v_title) ?>" placeholder="e.g. Sustainable Soil Management" required>
                                </label>
                                <label class="block">
                                    <span class="font-medium text-slate-600 dark:text-navy-100">Category</span>
                                    <select name="category" class="mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 dark:border-navy-450">
                                        <option value="">Select category</option>
                                        <?php foreach($course_categories as $cat): ?>
                                            <option value="<?= $cat ?>" <?= ($v_category===$cat)?'selected':'' ?>><?= ucfirst($cat) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                            </div>

                            <label class="block">
                                <span class="font-medium text-slate-600 dark:text-navy-100">Course Description</span>
                                <textarea rows="4" name="description" class="form-textarea mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent p-2.5 focus:border-primary dark:border-navy-450 dark:focus:border-accent" 
                                          placeholder="Provide an overview of the learning objectives..."><?= htmlspecialchars($v_description) ?></textarea>
                            </label>

                            <div>
                                <span class="font-medium text-slate-600 dark:text-navy-100">Cover Image</span>
                                <div class="mt-1.5 flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-slate-300 py-6 dark:border-navy-450"
                                     :class="dragging ? 'border-primary bg-primary/5' : ''"
                                     @dragover.prevent="dragging = true" @dragleave="dragging = false"
                                     @drop.prevent="dragging = false; const f=$event.dataTransfer.files[0]; if(f){ $refs.thumbInput.files=$event.dataTransfer.files; const r=new FileReader(); r.onload=e=>thumbPreview=e.target.result; r.readAsDataURL(f); }">
                                    
                                    <template x-if="thumbPreview || '<?= $thumb_url ?>'">
                                        <div class="relative">
                                            <img :src="thumbPreview || '<?= $thumb_url ?>'" class="max-h-40 rounded-lg object-cover">
                                            <button @click.prevent="thumbPreview=null; $refs.thumbInput.value=''" type="button" class="absolute -right-2 -top-2 flex h-6 w-6 items-center justify-center rounded-full bg-error text-white shadow-sm">
                                                <i class="fa-solid fa-xmark text-[10px]"></i>
                                            </button>
                                        </div>
                                    </template>

                                    <template x-if="!thumbPreview && !'<?= $thumb_url ?>'">
                                        <div class="text-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto size-10 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            <div class="mt-2 flex text-sm text-slate-500">
                                                <label class="relative cursor-pointer rounded-md font-medium text-primary hover:text-primary-focus">
                                                    <span>Upload a file</span>
                                                    <input type="file" name="thumbnail" x-ref="thumbInput" class="sr-only" accept="image/*" 
                                                           @change="const f=$el.files[0]; if(f){ const r=new FileReader(); r.onload=e=>thumbPreview=e.target.result; r.readAsDataURL(f); }">
                                                </label>
                                                <p class="pl-1">or drag and drop</p>
                                            </div>
                                            <p class="text-xs text-slate-400">PNG, JPG, WebP up to 3MB</p>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <div class="flex justify-end space-x-3 pt-4 border-t border-slate-150 dark:border-navy-600">
                                <button type="submit" class="btn bg-primary font-medium text-white hover:bg-primary-focus dark:bg-accent dark:hover:bg-accent-focus">
                                    <?= $edit_id ? 'Update Course Details' : 'Create & Continue' ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="col-span-12 lg:col-span-4 space-y-4">
                    <div class="card p-4 sm:p-5">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-semibold text-slate-700 dark:text-navy-100">Course Content</h3>
                            <?php if($edit_id): ?>
                            <a href="training_lesson_edit.php?course_id=<?= $edit_id ?>" class="text-xs font-bold text-primary dark:text-accent-light">+ ADD LESSON</a>
                            <?php endif; ?>
                        </div>

                        <?php if(!$edit_id): ?>
                        <div class="py-8 text-center rounded-lg border-2 border-dashed border-slate-200 dark:border-navy-500">
                            <p class="text-sm text-slate-400">Save the course to start adding lessons.</p>
                        </div>
                        <?php elseif(empty($lessons)): ?>
                        <div class="py-8 text-center rounded-lg border-2 border-dashed border-slate-200 dark:border-navy-500">
                            <p class="text-sm text-slate-400 mb-4">No lessons added yet.</p>
                            <a href="training_lesson_edit.php?course_id=<?= $edit_id ?>" class="btn btn-sm bg-primary/10 text-primary dark:bg-accent-light/10 dark:text-accent-light">
                                Add First Lesson
                            </a>
                        </div>
                        <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach($lessons as $i => $l): ?>
                            <div class="lesson-row">
                                <div class="flex items-center min-w-0">
                                    <div class="lesson-num"><?= $i+1 ?></div>
                                    <div class="truncate">
                                        <p class="text-sm font-medium text-slate-700 dark:text-navy-100 truncate"><?= htmlspecialchars($l['title']) ?></p>
                                        <div class="flex space-x-2 mt-0.5">
                                            <?php if($l['video']): ?><span class="text-[10px] text-slate-400"><i class="fa-solid fa-play mr-1"></i>Video</span><?php endif; ?>
                                            <?php if($l['pdf']): ?><span class="text-[10px] text-slate-400"><i class="fa-solid fa-file-pdf mr-1"></i>PDF</span><?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-1 ml-2">
                                    <a href="training_lesson_edit.php?id=<?= $l['id'] ?>&course_id=<?= $edit_id ?>" class="btn h-7 w-7 p-0 hover:bg-slate-200 dark:hover:bg-navy-450">
                                        <i class="fa-solid fa-pen text-[10px]"></i>
                                    </a>
                                    <form method="POST" onsubmit="return confirm('Remove this lesson?')">
                                        <input type="hidden" name="action" value="delete_lesson">
                                        <input type="hidden" name="lesson_id" value="<?= $l['id'] ?>">
                                        <button type="submit" class="btn h-7 w-7 p-0 text-error hover:bg-error/10">
                                            <i class="fa-solid fa-trash text-[10px]"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <?php if($edit_id): ?>
                        <div class="mt-6 pt-4 border-t border-slate-150 dark:border-navy-600">
                            <a href="training_progress.php?course_id=<?= $edit_id ?>" class="flex items-center justify-between group">
                                <span class="text-sm text-slate-500 group-hover:text-primary transition-colors">View Student Progress</span>
                                <i class="fa-solid fa-arrow-right text-slate-300 group-hover:text-primary transition-colors"></i>
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <div id="x-teleport-target"></div>
    <script>
        window.addEventListener("DOMContentLoaded", () => Alpine.start());
    </script>
</body>
</html>