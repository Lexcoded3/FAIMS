<?php
session_start();
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'extension') { header("Location: ../auth"); exit; }
require_once '../config/db.php';

$extension_id   = (int)$_SESSION['id'];
$extension_name = $_SESSION['name'] ?? 'Extension Worker';
$active_page    = 'training.php';

$lesson_id = (int)($_GET['id']        ?? 0);
$course_id = (int)($_GET['course_id'] ?? 0);
$lesson    = null;
$success   = false;
$errors    = [];

// Load course
if ($course_id <= 0) { header('Location: training.php'); exit; }
$course_res = $conn->query("SELECT * FROM training_courses WHERE id=$course_id");
if (!$course_res || $course_res->num_rows === 0) { header('Location: training.php'); exit; }
$course = $course_res->fetch_assoc();

// Load lesson for edit
if ($lesson_id > 0) {
    $res = $conn->query("SELECT * FROM training_lessons WHERE id=$lesson_id AND course_id=$course_id");
    if (!$res || $res->num_rows === 0) { header("Location: training_course_edit.php?id=$course_id"); exit; }
    $lesson = $res->fetch_assoc();
}

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title   = trim($_POST['title']   ?? '');
    $content = trim($_POST['content'] ?? '');
    $video   = trim($_POST['video']   ?? '');

    if ($title === '')   $errors[] = 'Lesson title is required.';
    if ($content === '') $errors[] = 'Lesson content is required.';

    // PDF upload
    $pdf_filename = $lesson['pdf'] ?? null;
    if (isset($_FILES['pdf']) && $_FILES['pdf']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['pdf'];
        if ($file['type'] !== 'application/pdf') {
            $errors[] = 'Only PDF files are accepted.';
        } elseif ($file['size'] > 10 * 1024 * 1024) {
            $errors[] = 'PDF must be under 10MB.';
        } else {
            $fname = 'lesson_' . $course_id . '_' . time() . '.pdf';
            $dir   = '../uploads/lessons/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            if (move_uploaded_file($file['tmp_name'], $dir . $fname)) $pdf_filename = $fname;
            else $errors[] = 'Could not save PDF. Check folder permissions.';
        }
    }

    // Clear PDF if requested
    if (isset($_POST['remove_pdf'])) $pdf_filename = null;

    if (empty($errors)) {
        $st  = $conn->real_escape_string($title);
        $sc  = $conn->real_escape_string($content);
        $sv  = $conn->real_escape_string($video);
        $sp  = $conn->real_escape_string($pdf_filename ?? '');

        if ($lesson_id > 0) {
            $conn->query("UPDATE training_lessons SET title='$st',content='$sc',video='$sv',pdf='$sp' WHERE id=$lesson_id AND course_id=$course_id");
            $success = true;
            $lesson  = $conn->query("SELECT * FROM training_lessons WHERE id=$lesson_id")->fetch_assoc();
        } else {
            $conn->query("INSERT INTO training_lessons (course_id,posted_by,title,content,video,pdf) VALUES ($course_id,$extension_id,'$st','$sc','$sv','$sp')");
            $new_id = $conn->insert_id;
            header("Location: training_lesson_edit.php?id=$new_id&course_id=$course_id&created=1"); exit;
        }
    }
}

$v_title   = $lesson['title']   ?? ($_POST['title']   ?? '');
$v_content = $lesson['content'] ?? ($_POST['content'] ?? '');
$v_video   = $lesson['video']   ?? ($_POST['video']   ?? '');
$v_pdf     = $lesson['pdf']     ?? '';

// Count lessons in course for numbering
$lesson_count_res = $conn->query("SELECT COUNT(*) AS c FROM training_lessons WHERE course_id=$course_id");
$lesson_num = ((int)$lesson_count_res->fetch_assoc()['c']) + ($lesson_id > 0 ? 0 : 1);

// Embed YouTube/Vimeo helper
function video_embed(string $url): ?string {
    if (!$url) return null;
    if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([A-Za-z0-9_-]{11})/', $url, $m))
        return 'https://www.youtube.com/embed/' . $m[1];
    if (preg_match('/vimeo\.com\/(\d+)/', $url, $m))
        return 'https://player.vimeo.com/video/' . $m[1];
    return null;
}
$embed_url = video_embed($v_video);
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <!-- Meta tags  -->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">

    <title>Agriconnect - Training Courses — Extension</title>
    <link rel="icon" type="image/png" href="../images/favicon.png">

    <!-- CSS Assets -->
    <link rel="stylesheet" href="../css/app.css">

    <!-- Javascript Assets -->
    <script src="../js/app.js" defer=""></script>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="">
    <link href="../css2?family=Inter:wght@400;500;600;700&family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet">
    <script>
      /**
       * THIS SCRIPT REQUIRED FOR PREVENT FLICKERING IN SOME BROWSERS
       */
      localStorage.getItem("_x_darkMode_on") === "true" &&
        document.documentElement.classList.add("dark");
    </script>
    <style>
.pdf-drop{border:2px dashed #e5e7eb;border-radius:10px;padding:24px;text-align:center;cursor:pointer;transition:all .2s}
.pdf-drop:hover,.pdf-drop.drag{border-color:#1D9E75;background:#f0fdf8}
.video-preview{border-radius:10px;overflow:hidden;background:#000;aspect-ratio:16/9}
</style>
  </head>

  <body class="is-header-blur is-sidebar-open" x-bind="$store.global.documentBody"
  x-data="{
        videoUrl: <?= json_encode($v_video) ?>,
        embedUrl: <?= json_encode($embed_url) ?>,
        pdfName: <?= json_encode($v_pdf ? pathinfo($v_pdf, PATHINFO_BASENAME) : '') ?>,
        dragging: false,
        removePdf: false,
        getEmbed(url) {
            const yt = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([A-Za-z0-9_-]{11})/);
            if (yt) return 'https://www.youtube.com/embed/' + yt[1];
            const vi = url.match(/vimeo\.com\/(\d+)/);
            if (vi) return 'https://player.vimeo.com/video/' + vi[1];
            return null;
        },
        updateVideo(val) {
            this.videoUrl = val;
            this.embedUrl = this.getEmbed(val);
        },
        handlePdfDrop(e) {
            this.dragging = false;
            const f = e.dataTransfer.files[0];
            if (f && f.type === 'application/pdf') {
                this.pdfName = f.name;
                const dt = new DataTransfer();
                dt.items.add(f);
                document.getElementById('pdf-input').files = dt.files;
            }
        }
      }">
    <!-- App preloader-->
    <div class="app-preloader fixed z-50 grid h-full w-full place-content-center bg-slate-50 dark:bg-navy-900">
      <div class="app-preloader-inner relative inline-block size-48"></div>
    </div>

    <!-- Page Wrapper -->
    <div id="root" class="min-h-100vh flex grow bg-slate-50 dark:bg-navy-900" x-cloak="">
      <!-- Sidebar -->
      <div class="sidebar print:hidden">
        <!-- Main Sidebar -->
        <div class="main-sidebar">
          <div class="flex h-full w-full flex-col items-center border-r border-slate-150 bg-white dark:border-navy-700 dark:bg-navy-800">
            <!-- Application Logo -->
            <div class="flex pt-4">
              <a href="index.htm.html">
                <img class="size-11 transition-transform duration-500 ease-in-out hover:rotate-[360deg]" src="../images/app-logo.png" alt="logo">
              </a>
            </div>

            <!-- Main Sections Links -->
            <?php include 'sidenav.php';?>            
          </div>
        </div>

        <!-- Sidebar Panel -->
        <?php include 'trainingsider.php';?>
      </div>

      <!-- App Header Wrapper-->
      <?php include 'toprightsidenav.php';?>

      <!-- Main Content Wrapper -->
      <main class="main-content w-full px-[var(--margin-x)] pb-8">
        <div class="flex flex-col items-center justify-between space-y-4 py-5 sm:flex-row sm:space-y-0 lg:py-6">
          <div class="flex items-center space-x-1">
            <svg xmlns="http://www.w3.org/2000/svg" class="size-6" fill="none" viewbox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <h2 class="text-xl font-medium text-slate-700 line-clamp-1 dark:text-navy-50">
              New lesson
            </h2>
          </div>
          <div class="flex justify-center space-x-2">
            <a href="training_course_edit.php?id=<?= $course_id ?>">
            <button class="btn min-w-[7rem] bg-primary font-medium text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90 dark:bg-accent dark:hover:bg-accent-focus dark:focus:bg-accent-focus dark:active:bg-accent/90">
              Back to Course
            </button>
            </a>
          </div>
        </div>
        <?php if($success): ?>
        <?php if($success): ?>
        <div x-data x-init="$notification({ text: 'Lesson created! Add another .', variant: 'success', position: 'left-top' })"></div>
        <?php endif; ?>
        <?php endif; ?>
        <div class="grid grid-cols-12 gap-4 sm:gap-5 lg:gap-6">
          <div class="col-span-12 lg:col-span-7">
            <div class="card">
              <div class="border-b border-slate-200 p-4 dark:border-navy-500 sm:px-5">
                <div class="flex items-center space-x-2">
                  <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-primary/10 p-1 text-primary dark:bg-accent-light/10 dark:text-accent-light">
                    <i class="fa-solid fa-layer-group"></i>
                  </div>
                  <h4 class="text-lg font-medium text-slate-700 dark:text-navy-100">
                    Add a new lesson
                  </h4>
                </div>
              </div>
              <div class="space-y-4 p-4 sm:p-5">
                <form method="POST" enctype="multipart/form-data">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-1">                  
                    <input type="hidden" name="action" value="save_course">
                <label class="block">
                  <span>Lesson title</span>

                  <input class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent" type="text" id="title" name="title" value="<?= htmlspecialchars($v_title) ?>"
                                   placeholder="e.g. How to prepare your seedbed" required>
                </label>
                </div>
                <label class="block">
                              <span>Description</span>
                              <textarea
                                rows="3"
                                class="form-textarea mt-1.5 w-full resize-none rounded-lg border border-slate-300 bg-transparent p-2.5 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent"
                              id="content" name="content"
                                      style="line-height:1.8"
                                      placeholder="Write the full lesson content here. Explain concepts clearly, include step-by-step instructions, tips and key takeaways for farmers…"
                                      required><?= htmlspecialchars($v_content) ?></textarea>
                            </label> 
                <div>

                  <!-- Video -->
                    <div class="bg-navy-450 rounded-xl border-navy-100 p-5">
                        <p class="text-xs text-gray-400 mb-4" style="font-weight:500;text-transform:uppercase;letter-spacing:.05em">Video (optional)</p>

                        <div class="mb-4">
                            <label class="field-label" for="video">YouTube or Vimeo URL</label>
                            <input class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent" type="text" id="video" name="video"
                                   :value="videoUrl"
                                   @input="updateVideo($event.target.value)"
                                   placeholder="https://www.youtube.com/watch?v=…">
                            <p class="text-xs text-gray-400 mt-1">Paste a YouTube or Vimeo link — farmers will watch it inline.</p>
                        </div>

                        <!-- Preview -->
                        <div x-show="embedUrl" class="video-preview">
                            <iframe :src="embedUrl" width="100%" height="100%" frameborder="0"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                    allowfullscreen style="width:100%;aspect-ratio:16/9;border:none;display:block"></iframe>
                        </div>
                        <div x-show="!embedUrl && videoUrl.length > 0" class="text-xs text-gray-400 mt-2">
                            Could not detect a YouTube or Vimeo link. Check the URL format.
                        </div>
                    </div>
                </div>
                
              </div>
            </div>
          </div>
          <div class="col-span-12 lg:col-span-5">
            <div class="card space-y-5 p-4 sm:p-5">
              <!-- PDF upload -->
                    <div class="bg-navy-500 rounded-xl border-navy-100 p-5">
                        <p class="text-xs text-gray-400 mb-4" style="font-weight:500;text-transform:uppercase;letter-spacing:.05em">PDF resource (optional)</p>

                        <!-- Existing PDF -->
                        <template x-if="pdfName && !removePdf">
                            <div class="flex items-center gap-3 px-3 py-2.5 rounded-lg border border-navy-100 mb-3">
                                <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0" style="background:#FCEBEB">
                                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="#A32D2D" stroke-width="1.4"><path d="M3 1h6l3 3v9H3V1z"/><path d="M9 1v3h3"/><path d="M5 8h4M5 10h3"/></svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs text-gray-700 truncate" style="font-weight:500" x-text="pdfName"></p>
                                    <p class="text-xs text-gray-400">Current PDF</p>
                                </div>
                                <button type="button" @click="removePdf=true; pdfName=''"
                                        class="text-gray-300 hover:text-red-400 transition-colors" style="background:none;border:none;cursor:pointer">
                                    <svg width="13" height="13" viewBox="0 0 13 13" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 3l7 7M10 3l-7 7"/></svg>
                                </button>
                            </div>
                        </template>
                        <input type="hidden" name="remove_pdf" x-bind:value="removePdf ? '1' : ''">

                        <!-- Drop zone -->
                        <div class="pdf-drop"
                             :class="dragging?'drag':''"
                             @dragover.prevent="dragging=true"
                             @dragleave="dragging=false"
                             @drop.prevent="handlePdfDrop($event)"
                             @click="$refs.pdfInput.click()">
                            <svg class="mx-auto mb-2" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="1.3"><path d="M4 4h10l4 4v12H4V4z"/><path d="M14 4v4h4"/><path d="M8 12h8M8 15h6"/></svg>
                            <p class="text-xs text-gray-400" x-text="pdfName && !removePdf ? pdfName : 'Click or drag to upload PDF'"></p>
                            <p class="text-xs text-gray-400 mt-0.5" x-show="!pdfName || removePdf">Max 10MB</p>
                            <input type="file" name="pdf" id="pdf-input" accept="application/pdf"
                                   class="hidden" x-ref="pdfInput"
                                   @change="pdfName=$event.target.files[0]?.name||''; removePdf=false">
                        </div>
                        <p class="text-xs text-gray-400 mt-2">Farmers can download this as a reference guide.</p>
                    </div>

                    <!-- Save -->
                    <div class="bg-navy-500 rounded-xl border-gray-100 p-5">
                        <p class="text-xs text-gray-400 mb-4" style="font-weight:500;text-transform:uppercase;letter-spacing:.05em">Publish</p>
                        <div class="flex justify-center space-x-2 pt-4">
                            <button
                            class="btn h-6 rounded bg-success px-3 text-xs font-medium text-white hover:bg-success-focus focus:bg-success-focus active:bg-success-focus/90"
                          >
                            <?= $lesson_id?'Save':'Publish' ?>
                          </button>
                          <?php if(!$lesson_id): ?>
                             <a href="training_lesson_edit.php?course_id=<?= $course_id ?>">
                          <button
                            class="btn h-6 rounded bg-primary px-3 text-xs font-medium text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90 dark:bg-accent dark:hover:bg-accent-focus dark:focus:bg-accent-focus dark:active:bg-accent/90"
                          >
                            Add lesson
                          </button>
                            </a>
                            <?php endif; ?>
                            <a href="training_course_edit.php?id=<?= $course_id ?>">
                          <button
                            class="btn h-6 rounded bg-slate-150 px-3 text-xs font-medium text-slate-800 hover:bg-slate-200 focus:bg-slate-200 active:bg-slate-200/80 dark:bg-navy-500 dark:text-navy-50 dark:hover:bg-navy-450 dark:focus:bg-navy-450 dark:active:bg-navy-450/90"
                          >
                            Back
                          </button>
                            </a>
                        </div>
                            </div>

                    <!-- Lesson info (edit mode) -->
                    <?php if($lesson): ?>
                    <div class="bg-navy-500 rounded-xl border-gray-100 p-4">
                        <div class="space-y-1">
                            <div class="flex items-center justify-between py-1.5" style="border-bottom:1px solid #f9fafb">
                                <span class="text-xs text-gray-400">Added</span>
                                <span class="mono text-xs text-gray-600"><?= date('d M Y', strtotime($lesson['created_at'])) ?></span>
                            </div>
                            <?php
                            $prog = $conn->query("SELECT COUNT(*) AS c, SUM(status='completed') AS done FROM training_progress WHERE lesson_id=$lesson_id")->fetch_assoc();
                            ?>
                            <div class="flex items-center justify-between py-1.5" style="border-bottom:1px solid #f9fafb">
                                <span class="text-xs text-gray-400">Started by</span>
                                <span class="mono text-xs text-gray-600"><?= (int)$prog['c'] ?> farmers</span>
                            </div>
                            <div class="flex items-center justify-between py-1.5">
                                <span class="text-xs text-gray-400">Completed</span>
                                <span class="mono text-xs text-gray-600"><?= (int)$prog['done'] ?> farmers</span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
              
            </div>
            </form>
          </div>
        </div>
      </main>
    </div>
    <!-- 
        This is a place for Alpine.js Teleport feature 
        @see https://alpinejs.dev/directives/teleport
      -->
    <div id="x-teleport-target"></div>
    <script>
      window.addEventListener("DOMContentLoaded", () => Alpine.start());
    </script>
  </body>
</html>
