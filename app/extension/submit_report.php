<?php
session_start();
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'extension') { header("Location: ../auth"); exit; }
require_once '../config/db.php';

$extension_id   = (int)$_SESSION['id'];
$extension_name = $_SESSION['name'] ?? 'Extension Worker';
$active_page    = 'submit_report.php';

$edit_id   = (int)($_GET['edit'] ?? 0);
$edit_data = null;
$success   = false;
$errors    = [];

if ($edit_id > 0) {
    $res = $conn->query("SELECT * FROM extension_reports WHERE id=$edit_id AND extension_id=$extension_id");
    if (!$res || $res->num_rows === 0) { header('Location: reports.php'); exit; }
    $edit_data = $res->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $district = trim($_POST['district'] ?? '');
    $title    = trim($_POST['title']    ?? '');
    $report   = trim($_POST['report']   ?? '');

    if ($district === '') $errors[] = 'District is required.';
    if ($title === '')    $errors[] = 'Title is required.';
    if ($report === '')   $errors[] = 'Report content is required.';

    if (empty($errors)) {
        $sd = $conn->real_escape_string($district);
        $st = $conn->real_escape_string($title);
        $sr = $conn->real_escape_string($report);
        if ($edit_id > 0) {
            $conn->query("UPDATE extension_reports SET district='$sd',title='$st',report='$sr' WHERE id=$edit_id AND extension_id=$extension_id");
        } else {
            $conn->query("INSERT INTO extension_reports (extension_id,district,title,report) VALUES ($extension_id,'$sd','$st','$sr')");
        }
        $success  = true;
        $edit_data = null;
    }
}

$v_district = $edit_data['district'] ?? ($_POST['district'] ?? '');
$v_title    = $edit_data['title']    ?? ($_POST['title']    ?? '');
$v_report   = $edit_data['report']   ?? ($_POST['report']   ?? '');
if ($success && !$edit_id) { $v_district = $v_title = $v_report = ''; }
?>
<!DOCTYPE html><html lang="en"><head>
<title><?= $edit_id?'Edit':'Submit' ?> Report — FAIMS Extension</title>
<?php include '_head.php'; ?>
</head>
<body class="bg-gray-50 text-gray-800">
<div class="flex h-screen overflow-hidden">
<?php include '_sidebar.php'; ?>

<main class="flex-1 flex flex-col overflow-hidden">
    <header class="bg-white border-b border-gray-100 px-6 py-4 flex items-center justify-between flex-shrink-0">
        <div>
            <h1 class="text-base text-gray-800" style="font-weight:500"><?= $edit_id?'Edit report':'Submit field report' ?></h1>
            <p class="text-xs text-gray-400 mt-0.5"><?= $edit_id?'Update your filed report':'Document your field observations' ?></p>
        </div>
        <a href="reports.php" class="text-xs text-gray-400 hover:text-gray-600">← Back to reports</a>
    </header>

    <div class="flex-1 overflow-y-auto scrollbar-hide px-6 py-6 fade-in" x-data="{ reportLen: <?= strlen($v_report) ?> }">
        <div class="max-w-2xl mx-auto">

            <?php if($success): ?>
            <div class="flex items-center gap-3 px-4 py-3 rounded-xl mb-5 text-xs" style="background:#E1F5EE;color:#0F6E56">
                <svg width="15" height="15" viewBox="0 0 15 15" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="7.5" cy="7.5" r="6"/><path d="M4.5 7.5l2 2 4-4"/></svg>
                <?= $edit_id?'Report updated':'Report submitted' ?> successfully.
                <a href="reports.php" class="ml-auto underline" style="color:#0F6E56">View all reports</a>
            </div>
            <?php endif; ?>

            <?php if(!empty($errors)): ?>
            <div class="px-4 py-3 rounded-xl mb-5" style="background:#FCEBEB;color:#A32D2D">
                <?php foreach($errors as $e): ?><p class="text-xs"><?= htmlspecialchars($e) ?></p><?php endforeach; ?>
            </div>
            <?php endif; ?>

            <form method="POST" class="space-y-5">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="field-label" for="district">District</label>
                        <input type="text" id="district" name="district" value="<?= htmlspecialchars($v_district) ?>" placeholder="e.g. Wakiso" required>
                    </div>
                    <div>
                        <label class="field-label" for="title">Report title</label>
                        <input type="text" id="title" name="title" value="<?= htmlspecialchars($v_title) ?>" placeholder="e.g. Fall armyworm outbreak in Kakiri" required>
                    </div>
                </div>

                <div>
                    <label class="field-label" for="report">Report content</label>
                    <textarea id="report" name="report" @input="reportLen=$el.value.length" style="min-height:200px" placeholder="Describe your observations — affected areas, severity, crop types, recommendations…" required><?= htmlspecialchars($v_report) ?></textarea>
                    <p class="text-right mt-1" style="font-size:11px;color:#9ca3af"><span x-text="reportLen"></span> characters</p>
                </div>

                <!-- Tip -->
                <div class="flex gap-3 px-4 py-3 rounded-xl border" style="background:#FAEEDA22;border-color:#FAEEDA">
                    <svg width="15" height="15" viewBox="0 0 15 15" fill="none" stroke="#854F0B" stroke-width="1.4" class="flex-shrink-0 mt-0.5"><circle cx="7.5" cy="7.5" r="6"/><path d="M7.5 6v4M7.5 4.5v.5"/></svg>
                    <p class="text-xs text-gray-600 leading-relaxed">Include the sub-county, crop type, estimated % affected, and any action taken. For disease/pest alerts, name the pathogen if known.</p>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="btn-primary">
                        <svg width="13" height="13" viewBox="0 0 13 13" fill="none" stroke="white" stroke-width="1.6"><path d="M1.5 6.5l4 4 6-8"/></svg>
                        <?= $edit_id?'Update report':'Submit report' ?>
                    </button>
                    <a href="reports.php" class="text-xs text-gray-400 hover:text-gray-600">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</main>
</div>
</body></html>
