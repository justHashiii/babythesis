<?php include '../php/process.php'; ?>

<!DOCTYPE html>
<html>
<head>
    <title>Library Attendance</title>
    <link rel="stylesheet" href="../css/indexstyle.css">
</head>

<body>
    <header>
        <h1>LIBRARY ATTENDANCE SYSTEM</h1>
    </header>


    <main>
        <div class="left-panel"> <!-- LEFT -->
            <div class="form-box">
                <img src="../img/holychildlogo.png" alt="Holy Child Logo">
                <h1>Enter your ID to login</h1>
                <form method="POST">
                    <input type="text" name="student_id" placeholder="Student ID" required>
                    <button type="submit">Log Attendance</button>

                    <div class="divider">OR</div>

                    <div class="signup">Don't have an account? <a href="../html/signup.html">Sign Up</a></div>
                    <div class="admin">Login as Admin <a href="../html/admin_login.html">Click Here</a></div>


                </form>
                <?php if ($log_message): ?>
                    <p class="message"><?= $log_message ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="right-panel"> <!-- RIGHT -->
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Strand/Course</th>
                        <th>Date & Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?= htmlspecialchars($log['name']) ?></td>
                            <td><?= htmlspecialchars($log['course']) ?></td>
                            <td><?= $log['log_time'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>


    <footer> HOLY CHILD COLLEGE OF DAVAO</footer>
</body>
</html>