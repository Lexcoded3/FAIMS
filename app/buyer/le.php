<?php
session_start();
require_once '../config/db.php';

// Basic guards
if (!isset($_SESSION['user_id'])) {
    die('Unauthorized access');
}

if (!isset($_GET['id'])) {
    die('Lesson not specified');
}

$user_id   = (int) $_SESSION['user_id'];
$lesson_id = (int) $_GET['id'];

/*
|--------------------------------------------------------------------------
| 1. Fetch lesson
|--------------------------------------------------------------------------
*/
$lesson_q = $conn->prepare("
    SELECT tl.*, tc.title AS course_title
    FROM training_lessons tl
    JOIN training_courses tc ON tc.id = tl.course_id
    WHERE tl.id = ?
");
$lesson_q->bind_param("i", $lesson_id);
$lesson_q->execute();
$lesson_result = $lesson_q->get_result();

if ($lesson_result->num_rows === 0) {
    die('Lesson not found');
}

$lesson = $lesson_result->fetch_assoc();

/*
|--------------------------------------------------------------------------
| 2. Ensure progress row exists (STARTED)
|--------------------------------------------------------------------------
*/
$progress_stmt = $conn->prepare("
    SELECT * FROM training_progress
    WHERE user_id = ? AND lesson_id = ?
");
$progress_stmt->bind_param("ii", $user_id, $lesson_id);
$progress_stmt->execute();
$progress_result = $progress_stmt->get_result();

if ($progress_result->num_rows === 0) {
    $insert_stmt = $conn->prepare("
        INSERT INTO training_progress (user_id, lesson_id, status, started_at)
        VALUES (?, ?, 'started', NOW())
    ");
    $insert_stmt->bind_param("ii", $user_id, $lesson_id);
    $insert_stmt->execute();

    $status = 'started';
} else {
    $progress = $progress_result->fetch_assoc();
    $status   = $progress['status'];
}

/*
|--------------------------------------------------------------------------
| 3. Handle completion
|--------------------------------------------------------------------------
*/
if (isset($_POST['complete_lesson']) && $status !== 'completed') {
    $update_stmt = $conn->prepare("
        UPDATE training_progress
        SET status = 'completed',
            completed_at = NOW()
        WHERE user_id = ? AND lesson_id = ?
    ");
    $update_stmt->bind_param("ii", $user_id, $lesson_id);
    $update_stmt->execute();

    $status = 'completed';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($lesson['title']) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Your CSS stack here -->
    <link rel="stylesheet" href="../assets/css/app.css">
</head>
<body class="bg-slate-100 dark:bg-navy-900">

<div class="max-w-4xl mx-auto p-6">

    <!-- Course breadcrumb -->
    <p class="text-sm text-slate-400 mb-2">
        Course: <?= htmlspecialchars($lesson['course_title']) ?>
    </p>

    <!-- Lesson title -->
    <h1 class="text-2xl font-bold text-slate-800 dark:text-navy-100 mb-4">
        <?= htmlspecialchars($lesson['title']) ?>
    </h1>

    <!-- Status badge -->
    <div class="mb-6">
        <?php if ($status === 'completed'): ?>
            <span class="badge bg-success/15 text-success">Completed</span>
        <?php else: ?>
            <span class="badge bg-warning/15 text-warning">In Progress</span>
        <?php endif; ?>
    </div>

    <!-- Video -->
    <?php if (!empty($lesson['video'])): ?>
        <div class="mb-6">
            <video controls class="w-full rounded-lg">
                <source src="../uploads/videos/<?= htmlspecialchars($lesson['video']) ?>" type="video/mp4">
                Your browser does not support the video tag.
            </video>
        </div>
    <?php endif; ?>

    <!-- Content -->
    <div class="prose max-w-none dark:prose-invert mb-6">
        <?= nl2br(htmlspecialchars($lesson['content'])) ?>
    </div>

    <!-- PDF -->
    <?php if (!empty($lesson['pdf'])): ?>
        <div class="mb-6">
            <a href="../uploads/pdfs/<?= htmlspecialchars($lesson['pdf']) ?>"
               target="_blank"
               class="btn border border-primary text-primary">
                📄 Download Lesson PDF
            </a>
        </div>
    <?php endif; ?>

    <!-- Completion -->
    <?php if ($status !== 'completed'): ?>
        <form method="POST">
            <button name="complete_lesson"
                class="btn bg-primary text-white rounded-full px-6 py-2">
                Mark Lesson as Completed
            </button>
        </form>
    <?php else: ?>
        <div class="mt-4 text-success font-medium">
            ✔ You have completed this lesson
        </div>
    <?php endif; ?>

</div>

</body>
</html>
