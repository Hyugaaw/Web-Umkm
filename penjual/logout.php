<?php
session_start();
session_unset();
session_destroy();
header("Location: ../pembeli/auth/login.php");
exit();
