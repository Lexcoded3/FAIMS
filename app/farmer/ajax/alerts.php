<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'farmer') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$farmer_id = (int)$_SESSION['id'];
$alerts = [];
$total  = 0;

// -----------------------------
// 1. Pending Orders
// -----------------------------
$sql = "
SELECT o.id, CONCAT('New order #', o.id, ' from ', u.name) AS message, o.created_at
FROM orders o
JOIN users u ON u.id = o.buyer_id
WHERE o.farmer_id = ? AND o.status = 'pending'
ORDER BY o.created_at DESC
LIMIT 5
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $farmer_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $alerts[] = [
        'id'        => $row['id'],
        'type'      => 'order',
        'title'     => 'New Order',
        'message'   => $row['message'],
        'time'      => date('h:i A', strtotime($row['created_at'])),
        'timestamp' => strtotime($row['created_at']),
        'is_read'   => 1,
        'link'      => "orders.php#order-" . $row['id'],
    ];
    $total++;
}
$stmt->close();

// -----------------------------
// 2. Out of Stock
// -----------------------------
$sql = "
SELECT id, name, created_at
FROM products
WHERE farmer_id = ? AND status = 'out'
ORDER BY created_at DESC
LIMIT 3
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $farmer_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $alerts[] = [
        'id'        => $row['id'],
        'type'      => 'stock',
        'title'     => 'Stock Alert',
        'message'   => $row['name'] . ' is out of stock',
        'time'      => date('h:i A', strtotime($row['created_at'])),
        'timestamp' => strtotime($row['created_at']),
        'is_read'   => 1,
        'link'      => "products.php#product-" . $row['id'],
    ];
    $total++;
}
$stmt->close();

// -----------------------------
// 3. Forum Activity
// forum_replies.topic_id and forum_topic_likes.topic_id both reference
// forum_topics.id — NOT posts.id. Join to forum_topics.
// -----------------------------
$sql = "
SELECT 'comment' AS type, c.id, CONCAT(u.name,' commented on your topic') AS message, c.created_at
FROM forum_replies c
JOIN forum_topics t ON t.id = c.topic_id
JOIN users u ON u.id = c.user_id
WHERE t.user_id = ?
UNION ALL
SELECT 'like' AS type, l.id, CONCAT(u.name,' liked your topic') AS message, l.created_at
FROM forum_topic_likes l
JOIN forum_topics t ON t.id = l.topic_id
JOIN users u ON u.id = l.user_id
WHERE t.user_id = ?
ORDER BY created_at DESC
LIMIT 10
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $farmer_id, $farmer_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $alerts[] = [
        'id'        => $row['id'],
        'type'      => $row['type'],
        'title'     => 'Community Activity',
        'message'   => $row['message'],
        'time'      => date('h:i A', strtotime($row['created_at'])),
        'timestamp' => strtotime($row['created_at']),
        'is_read'   => 1,
        'link'      => null,
    ];
    $total++;
}
$stmt->close();

// -----------------------------
// 4. Negotiation Notifications
// -----------------------------
$sql = "
SELECT id, title, message, created_at, is_read, reference_id
FROM notifications
WHERE user_id = ?
ORDER BY created_at DESC
LIMIT 3
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $farmer_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $alerts[] = [
        'id'        => $row['id'],
        'type'      => 'negotiation',
        'title'     => $row['title'],
        'message'   => $row['message'],
        'time'      => date('h:i A • d M', strtotime($row['created_at'])),
        'timestamp' => strtotime($row['created_at']),
        'is_read'   => (int)$row['is_read'],
        'link'      => "negotiations.php?id=" . $row['reference_id'],
    ];
    $total++;
}
$stmt->close();

// -----------------------------
// 5. Weather Alerts
// get_weather.php returns 'date' as "F j, Y" string (e.g. "March 27, 2026")
// and uses 'description' not 'weather_desc'. We check the right keys here.
// If the weather_data table is empty we fall back to calling the live API.
// -----------------------------
$weatherAlerts = [];

// Try DB first (fastest, no external call)
$w_res = $conn->query("SELECT location, temperature, humidity, weather_desc, created_at FROM weather_data ORDER BY created_at DESC LIMIT 1");
if ($w_res && $w_res->num_rows > 0) {
    $w = $w_res->fetch_assoc();
    $weatherAlerts[] = [
        'id'        => 'w_db_' . strtotime($w['created_at']),
        'type'      => 'weather',
        'title'     => 'Weather Update',
        'message'   => ucfirst($w['weather_desc'])
                       . ' · ' . round($w['temperature']) . '°C'
                       . ' · Humidity ' . $w['humidity'] . '%',
        'time'      => date('h:i A • d M', strtotime($w['created_at'])),
        'timestamp' => strtotime($w['created_at']),
        'is_read'   => 0,
        'link'      => null,
    ];
} else {
    // DB empty — call the live API file directly
    $weather_api = __DIR__ . '/../api/get_weather.php';
    $response    = @file_get_contents($weather_api);
    if ($response) {
        $data = json_decode($response, true);
        // API returns: description, temp, humidity, date (as "F j, Y")
        if ($data && !isset($data['error'])
            && isset($data['description'], $data['temp'], $data['humidity'])) {
            $ts = isset($data['date']) ? strtotime($data['date']) : time();
            $weatherAlerts[] = [
                'id'        => 'w_api_' . $ts,
                'type'      => 'weather',
                'title'     => 'Weather — ' . ($data['location'] ?? 'Uganda'),
                'message'   => ucfirst($data['description'])
                               . ' · ' . $data['temp'] . '°C'
                               . ' · Humidity ' . $data['humidity'] . '%',
                'time'      => date('h:i A • d M', $ts),
                'timestamp' => $ts,
                'is_read'   => 0,
                'link'      => null,
            ];
        }
    }
}

$alerts  = array_merge($alerts, $weatherAlerts);
$total  += count($weatherAlerts);

// -----------------------------
// 6. Extension Worker Bulletins & Reports
// -----------------------------

// 6a. Agri bulletins posted by extension workers
$sql = "
SELECT p.id, p.title, p.content, p.created_at, u.name AS author
FROM posts p
JOIN users u ON u.id = p.user_id
WHERE u.role = 'extension'
ORDER BY p.created_at DESC
LIMIT 5
";
$res = $conn->query($sql);
while ($row = $res->fetch_assoc()) {
    $text = strtolower($row['title'] . ' ' . $row['content']);
    if (preg_match('/disease|pest|blight|worm|virus|fungus|rust|armyworm|outbreak/', $text)) {
        $prefix = '⚠ Disease alert';
    } elseif (preg_match('/season|planting|harvest/', $text)) {
        $prefix = '🌱 Seasonal tip';
    } elseif (preg_match('/price|market|sell|buy/', $text)) {
        $prefix = '📈 Market info';
    } else {
        $prefix = '📋 Agri bulletin';
    }
    $alerts[] = [
        'id'        => 'ext_post_' . $row['id'],
        'type'      => 'extension',
        'subtype'   => 'bulletin',
        'title'     => $row['title'],
        'message'   => $prefix . ' from ' . $row['author'] . ': '
                       . mb_strimwidth(strip_tags($row['content']), 0, 90, '…'),
        'time'      => date('h:i A • d M', strtotime($row['created_at'])),
        'timestamp' => strtotime($row['created_at']),
        'is_read'   => 0,
        'link'      => null,
    ];
    $total++;
}

// 6b. Extension field reports
// Show ALL reports to all farmers — districts rarely match exactly.
// The district is shown in the title so farmers know the source area.
$sql = "
SELECT er.id, er.title, er.district, er.created_at, u.name AS officer
FROM extension_reports er
JOIN users u ON u.id = er.extension_id
ORDER BY er.created_at DESC
LIMIT 5
";
$res = $conn->query($sql);
while ($row = $res->fetch_assoc()) {
    $alerts[] = [
        'id'        => 'ext_report_' . $row['id'],
        'type'      => 'extension',
        'subtype'   => 'report',
        'title'     => 'Field Report — ' . $row['district'],
        'message'   => $row['officer'] . ' filed: ' . $row['title'],
        'time'      => date('h:i A • d M', strtotime($row['created_at'])),
        'timestamp' => strtotime($row['created_at']),
        'is_read'   => 0,
        'link'      => null,
    ];
    $total++;
}

// -----------------------------
// 7. Sort newest first
// -----------------------------
usort($alerts, fn($a, $b) => $b['timestamp'] - $a['timestamp']);

// -----------------------------
// 8. Output JSON
// -----------------------------
header('Content-Type: application/json');
echo json_encode([
    'total'  => $total,
    'alerts' => $alerts,
]);
exit;