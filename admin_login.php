<?php
session_start();
include "db.php";  

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $password = $_POST['password'];

    // Query admin
    $query = "SELECT * FROM admin_account WHERE username = 'admin' LIMIT 1";
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        $admin = $result->fetch_assoc();

        // NO HASH — plain password checking
        if ($password === $admin['password']) {

            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_name'] = $admin['username'];

            header("Location: admin_dashboard.php");
            exit();
        } else {
            $error = "Incorrect password!";
        }
    } else {
        $error = "Admin account not found.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Login</title>
</head>
<body>

<?php if ($error != ""): ?>
    <p style="color:red;"><?php echo $error; ?></p>
<?php endif; ?>

<a href="../html/admin_login.html">← Back to Login</a>

</body>
</html>
