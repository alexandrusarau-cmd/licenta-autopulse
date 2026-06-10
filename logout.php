<?php
session_start();

//delete all var
$_SESSION = [];

session_destroy();

// redirect index
header("Location: index.php");
exit;