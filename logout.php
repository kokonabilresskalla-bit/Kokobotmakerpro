<?php
error_reporting(0);
require_once __DIR__ . '/config.php';
clearAuthCookie();
redirect('login.php');
