<?php
session_start();
require_once __DIR__ . '/../../config/db.php'; // FIXED PATH

header('Content-Type: application/json');

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'extension') {
    echo json_encode(["total" => 0, "alerts" => []]);
    exit;
}

$extension_id = (int)$_SESSION['id'];
$alerts = [];

/**
 * Helper: time ago
 */
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;

    if ($diff < 60) return "just now";
    if ($diff < 3600) return floor($diff/60) . " mins ago";
    if ($diff < 86400) return floor($diff/3600) . " hrs ago";
    return date("M d", $time);
}

/**
 * 1. 🚨 YOUR DISTRICT - Disease detection (last 24h)
 */
$res = $conn->query("
    SELECT district, report 
    FROM extension_reports 
    WHERE extension_id = $extension_id
    AND created_at >= NOW() - INTERVAL 1 DAY
");

$disease = [];

while ($row = $res->fetch_assoc()) {
    $text = strtolower($row['report']);

    if (preg_match('/armyworm|pest|disease|blight|fungus|virus/', $text)) {
        $district = $row['district'];
        $disease[$district] = ($disease[$district] ?? 0) + 1;
    }
}

foreach ($disease as $district => $count) {
    if ($count >= 2) {
        $alerts[] = [
            "type" => "danger",
            "message" => "Outbreak suspected in $district ($count cases)",
            "time" => "Today",
            "link" => "reports.php?district=" . urlencode($district) . "&type=disease"
        ];
    }
}

/**
 * 2. 📊 YOUR NEW REPORTS (last 1 hour)
 */
$res = $conn->query("
    SELECT COUNT(*) as total 
    FROM extension_reports 
    WHERE extension_id = $extension_id
    AND created_at >= NOW() - INTERVAL 1 HOUR
");

$row = $res->fetch_assoc();

if ($row['total'] > 0) {
    $alerts[] = [
        "type" => "report",
        "message" => $row['total'] . " new reports submitted",
        "time" => "just now",
        "link" => "reports.php?filter=new"
    ];
}

/**
 * 3. 🌍 OTHER EXTENSION WORKERS - activity (last 3 hours)
 */
$res = $conn->query("
    SELECT district, COUNT(*) as total 
    FROM extension_reports 
    WHERE extension_id != $extension_id
    AND created_at >= NOW() - INTERVAL 3 HOUR
    GROUP BY district
    ORDER BY total DESC
    LIMIT 3
");

while ($row = $res->fetch_assoc()) {
    if ($row['total'] >= 2) {
        $alerts[] = [
            "type" => "insight",
            "message" => $row['total'] . " reports from other officers in " . $row['district'],
            "time" => "recent",
            "link" => "reports.php?district=" . urlencode($row['district']) . "&scope=external"
        ];
    }
}

/**
 * 4. 🚨 OTHER WORKERS - disease signals
 */
$res = $conn->query("
    SELECT district, report 
    FROM extension_reports 
    WHERE extension_id != $extension_id
    AND created_at >= NOW() - INTERVAL 1 DAY
");

$externalDisease = [];

while ($row = $res->fetch_assoc()) {
    $text = strtolower($row['report']);

    if (preg_match('/armyworm|pest|disease|blight|fungus|virus/', $text)) {
        $district = $row['district'];
        $externalDisease[$district] = ($externalDisease[$district] ?? 0) + 1;
    }
}

foreach ($externalDisease as $district => $count) {
    if ($count >= 2) {
        $alerts[] = [
            "type" => "danger",
            "message" => "Disease reported in $district (other officers)",
            "time" => "today",
            "link" => "reports.php?district=" . urlencode($district) . "&type=disease&scope=external"
        ];
    }
}

/**
 * 5. 📄 Latest individual reports (for freshness)
 */
$res = $conn->query("
    SELECT id, title, district, created_at 
    FROM extension_reports 
    WHERE created_at >= NOW() - INTERVAL 1 DAY
    ORDER BY created_at DESC
    LIMIT 3
");

while ($row = $res->fetch_assoc()) {
    $alerts[] = [
        "type" => "report",
        "message" => "New: " . $row['title'] . " (" . $row['district'] . ")",
        "time" => timeAgo($row['created_at']),
        "link" => "reports.php?view=" . $row['id']
    ];
}

/**
 * Limit alerts (avoid overload)
 */
$alerts = array_slice($alerts, 0, 8);

/**
 * Return JSON
 */
echo json_encode([
    "total" => count($alerts),
    "alerts" => $alerts
]);