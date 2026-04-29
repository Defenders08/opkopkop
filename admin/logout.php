<?php
/**
 * ChubbyCMS - Logout
 */
require_once '../includes/config.php';
require_once '../core/Auth.php';

use Core\Auth;

Auth::initSession();
session_unset();
session_destroy();

header('Location: login.php');
exit;
