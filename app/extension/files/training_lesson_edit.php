<?php
session_start();
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'extension') { header('Location: /login.php'); exit; }
require_once '../../config/db.php';

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
            $dir   = '../../uploads/lessons/';
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
<title><?= $lesson_id?'Edit':'New' ?> Lesson — FAIMS Extension</title>
<?php include '../_head.php'; ?>
<style>
.pdf-drop{border:2px dashed #e5e7eb;border-radius:10px;padding:24px;text-align:center;cursor:pointer;transition:all .2s}
.pdf-drop:hover,.pdf-drop.drag{border-color:#1D9E75;background:#f0fdf8}
.video-preview{border-radius:10px;overflow:hidden;background:#000;aspect-ratio:16/9}
</style>
</head>
<body class="bg-gray-50 text-gray-800"
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
<div class="flex h-screen overflow-hidden">
<?php include '_sidebar.php'; ?>

<main class="flex-1 flex flex-col overflow-hidden">
    <header class="bg-white border-b border-gray-100 px-6 py-4 flex items-center justify-between flex-shrink-0">
        <div>
            <div class="flex items-center gap-2 mb-0.5">
                <a href="training_course_edit.php?id=<?= $course_id ?>" class="text-xs text-gray-400 hover:text-gray-600 transition-colors">
                    <?= htmlspecialchars($course['title']) ?>
                </a>
                <svg width="10" height="10" viewBox="0 0 10 10" fill="none" stroke="#d1d5db" stroke-width="1.5"><path d="M3 2l4 3-4 3"/></svg>
                <span class="text-xs text-gray-600"><?= $lesson_id?'Edit lesson':'New lesson' ?></span>
            </div>
            <h1 class="text-base text-gray-800" style="font-weight:500">
                <?= $lesson_id ? htmlspecialchars($lesson['title']??'') : 'Add a new lesson' ?>
            </h1>
        </div>
        <div class="flex items-center gap-3">
            <?php if($success): ?>
            <span class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs" style="background:#E1F5EE;color:#0F6E56">
                <svg width="13" height="13" viewBox="0 0 13 13" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="6.5" cy="6.5" r="5.5"/><path d="M4 6.5l2 2 3.5-3.5"/></svg>
                Saved
            </span>
            <?php endif; ?>
            <a href="training_course_edit.php?id=<?= $course_id ?>" class="btn-ghost">← Back to course</a>
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
            Lesson created! Add another or go back to the course.
            <a href="training_lesson_edit.php?course_id=<?= $course_id ?>" class="ml-auto underline" style="color:#0F6E56">Add another</a>
        </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="grid grid-cols-3 gap-6">

                <!-- Main content (2 cols) -->
                <div class="col-span-2 space-y-5">
                    <div class="bg-white rounded-xl border border-gray-100 p-5">
                        <p class="text-xs text-gray-400 mb-4" style="font-weight:500;text-transform:uppercase;letter-spacing:.05em">Lesson content</p>

                        <div class="mb-4">
                            <label class="field-label" for="title">Lesson title <span style="color:#A32D2D">*</span></label>
                            <input type="text" id="title" name="title" value="<?= htmlspecialchars($v_title) ?>"
                                   placeholder="e.g. How to prepare your seedbed" required>
                        </div>

                        <div>
                            <label class="field-label" for="content">Content / notes <span style="color:#A32D2D">*</span></label>
                            <textarea id="content" name="content"
                                      style="min-height:220px;line-height:1.8"
                                      placeholder="Write the full lesson content here. Explain concepts clearly, include step-by-step instructions, tips and key takeaways for farmers…"
                                      required><?= htmlspecialchars($v_content) ?></textarea>
                            <p class="text-xs text-gray-400 mt-1">Write as if explaining to a farmer face-to-face. Use simple language.</p>
                        </div>
                    </div>

                    <!-- Video -->
                    <div class="bg-white rounded-xl border border-gray-100 p-5">
                        <p class="text-xs text-gray-400 mb-4" style="font-weight:500;text-transform:uppercase;letter-spacing:.05em">Video (optional)</p>

                        <div class="mb-4">
                            <label class="field-label" for="video">YouTube or Vimeo URL</label>
                            <input type="text" id="video" name="video"
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

                <!-- Right: PDF + actions -->
                <div class="col-span-1 space-y-5">

                    <!-- PDF upload -->
                    <div class="bg-white rounded-xl border border-gray-100 p-5">
                        <p class="text-xs text-gray-400 mb-4" style="font-weight:500;text-transform:uppercase;letter-spacing:.05em">PDF resource (optional)</p>

                        <!-- Existing PDF -->
                        <template x-if="pdfName && !removePdf">
                            <div class="flex items-center gap-3 px-3 py-2.5 rounded-lg border border-gray-100 mb-3">
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
                    <div class="bg-white rounded-xl border border-gray-100 p-5">
                        <p class="text-xs text-gray-400 mb-4" style="font-weight:500;text-transform:uppercase;letter-spacing:.05em">Publish</p>
                        <div class="space-y-2">
                            <button type="submit" class="btn-primary w-full justify-center">
                                <svg width="13" height="13" viewBox="0 0 13 13" fill="none" stroke="white" stroke-width="1.6"><path d="M1.5 6.5l4 4 6-8"/></svg>
                                <?= $lesson_id?'Save lesson':'Publish lesson' ?>
                            </button>
                            <?php if(!$lesson_id): ?>
                            <a href="training_lesson_edit.php?course_id=<?= $course_id ?>"
                               class="btn-ghost w-full justify-center" style="text-align:center">
                                + Add another lesson
                            </a>
                            <?php endif; ?>
                            <a href="training_course_edit.php?id=<?= $course_id ?>"
                               class="block text-center text-xs text-gray-400 hover:text-gray-600 transition-colors mt-2">
                                Back to course
                            </a>
                        </div>
                    </div>

                    <!-- Lesson info (edit mode) -->
                    <?php if($lesson): ?>
                    <div class="bg-white rounded-xl border border-gray-100 p-4">
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

            </div>
        </form>
    </div>
</main>
</div>
</body>
</html>
