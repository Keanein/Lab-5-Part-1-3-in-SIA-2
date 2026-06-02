<?php
// logout.php - PHASE 3 PROFESSOR'S VERSION

session_start();
session_destroy();
header('Location: index.php');
exit;
?>