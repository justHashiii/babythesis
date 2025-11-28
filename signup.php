<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $first_name   = $_POST['first_name'];
    $middle_name  = $_POST['middle_name'];
    $last_name    = $_POST['last_name'];
    $course       = $_POST['course'];
    $year_level   = $_POST['year_level'];
    $student_id   = $_POST['student_id'];

    // Check if student ID already exists
    $check = "SELECT * FROM users WHERE student_id = '$student_id'";
    $res = mysqli_query($conn, $check);

    if (mysqli_num_rows($res) > 0) {
        echo "This Student ID is already registered. ";
        echo "<a href='../php/index.php'>Go to login</a>";
    } else {
        $sql = "INSERT INTO users (first_name, middle_name, last_name, course, year_level, student_id) 
                VALUES ('$first_name', '$middle_name', '$last_name', '$course', '$year_level', '$student_id')";

        if (mysqli_query($conn, $sql)) {
            echo "Signup successful! <a href='../php/index.php'>Go to login</a>";
        } else {
            echo "Error: " . mysqli_error($conn);
        }
    }
}
?>