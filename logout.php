<?php
session_start();
session_destroy();
header("Location: ../html/admin_login.html");
exit();
?>
