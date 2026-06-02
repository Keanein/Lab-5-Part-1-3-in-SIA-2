<?php 
    require_once 'includes/footer.php'; ?>
    logout.php
<?php

session_start();
$_SESSION = [];
session_destroy();
header('Location: index.php');
exit;