<?php
// index.php - GUARD FILE (prevents directory browsing)
header('Location: ../access_denied.php');
exit;
?>