<?php
include 'db.php';

// Define the canonical list of courses/strands to keep order and include zeros
$course_list = [
    'BEED','BSED','BSIT','BSBA','BSCRIM','ICT','HUMSS','ABM','STEM','JUNIOR HIGH','SENIOR HIGH','FACULTY','STAFF','VISITOR'
];

// Initialize counts with zero
$counts = array_fill_keys($course_list, 0);

$where = "";

// Accept date filters via GET parameters:
// - range=week|month|year|all  (week = last 7 days, month = last 30 days, year = last 365 days)
// - start=YYYY-MM-DD & end=YYYY-MM-DD for custom range (inclusive)
$range = isset($_GET['range']) ? $_GET['range'] : 'all';
$start = isset($_GET['start']) ? $_GET['start'] : null;
$end = isset($_GET['end']) ? $_GET['end'] : null;

// Validate custom dates
function valid_date($d) { return preg_match('/^\d{4}-\d{2}-\d{2}$/', $d); }

if ($start && $end && valid_date($start) && valid_date($end)) {
    $s = $conn->real_escape_string($start) . ' 00:00:00';
    $e = $conn->real_escape_string($end) . ' 23:59:59';
    $where = "WHERE a.log_time BETWEEN '$s' AND '$e'";
} else {
    switch (strtolower($range)) {
        case 'week':
            $where = "WHERE a.log_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            break;
        case 'month':
            $where = "WHERE a.log_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            break;
        case 'year':
            $where = "WHERE a.log_time >= DATE_SUB(NOW(), INTERVAL 365 DAY)";
            break;
        default:
            $where = ""; // all time
    }
}

// Count logins per course from attendance_logs joined with users
$query = "
    SELECT u.course, COUNT(*) AS total
    FROM attendance_logs a
    JOIN users u ON a.student_id = u.student_id
    $where
    GROUP BY u.course
";

$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $course = $row['course'];
        $total = (int)$row['total'];
        if (array_key_exists($course, $counts)) {
            $counts[$course] = $total;
        } else {
            // Include any unexpected course names at the end
            $counts[$course] = $total;
        }
    }
}

// Also compute top users by total logins (overall)
$top_users = [];
$top_q = "
    SELECT u.first_name, u.middle_name, u.last_name, u.course, u.student_id, COUNT(*) AS logins
    FROM attendance_logs a
    JOIN users u ON a.student_id = u.student_id
    $where
    GROUP BY a.student_id
    ORDER BY logins DESC
    LIMIT 10
";
$top_res = $conn->query($top_q);
if ($top_res) {
    while ($r = $top_res->fetch_assoc()) {
        $top_users[] = [
            'name' => trim($r['first_name'] . ' ' . $r['middle_name'] . ' ' . $r['last_name']),
            'course' => $r['course'],
            'student_id' => $r['student_id'],
            'logins' => (int)$r['logins']
        ];
    }
}

// Prepare response arrays
$courses = array_keys($counts);
$totals = array_values($counts);

echo json_encode([
    'courses' => $courses,
    'totals' => $totals,
    'top_users' => $top_users,
    'total_users' => (int)($conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'] ?? 0),
    // total logs respecting the same filter
    'total_logs' => (int)($conn->query("SELECT COUNT(*) as c FROM attendance_logs a $where")->fetch_assoc()['c'] ?? 0),
    'range' => $range,
    'start' => $start,
    'end' => $end
]);

$conn->close();
?>
