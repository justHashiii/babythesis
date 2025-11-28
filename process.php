<?php
include 'db.php';

$log_message = "";
$logs = [];

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['student_id'])) {
    $student_id = $conn->real_escape_string($_POST['student_id']);

    $user_result = $conn->query("SELECT * FROM users WHERE student_id = '$student_id'");
    if ($user_result->num_rows > 0) {
        $user = $user_result->fetch_assoc();
        /* $full_name = $user['first_name'] . ' ' . $user['middle_name'] . ' ' . $user['last_name']; */
        $first_name = $user['first_name'];

        $conn->query("INSERT INTO attendance_logs (student_id) VALUES ('$student_id')");
        /* $log_message = "Welcome, $full_name!"; basta mao ni sya ang mogawas ubos sa SIGNUP */
        $log_message = "Welcome, $first_name!"; /* basta mao ni sya ang mogawas ubos sa SIGNUP */
    } else {
        $log_message = "Student ID not found.";
    }
}

$logs_result = $conn->query("
    SELECT u.first_name, u.middle_name, u.last_name, u.course, a.log_time
    FROM attendance_logs a
    JOIN users u ON a.student_id = u.student_id
    ORDER BY a.log_time DESC
");

while ($row = $logs_result->fetch_assoc()) {
    $logs[] = [
        'name' => $row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name'],
        'course' => $row['course'],
        'log_time' => date("m/d/Y h:i A", strtotime($row['log_time']))
    ];
}

$conn->close();
?>