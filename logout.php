<?php

require_once __DIR__ . '/functions.php';

start_session_safe();
logout_user();
header('Location: login.php');
exit;