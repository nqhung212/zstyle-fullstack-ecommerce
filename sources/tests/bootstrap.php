<?php
/**
 * PHPUnit Bootstrap File
 * Loads all necessary files and configurations for testing
 */

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load global functions
require_once __DIR__ . '/../model/global.php';

// Define constants if not already defined
if (!defined('PATH_IMG')) {
    define('PATH_IMG', __DIR__ . '/../upload/');
}

if (!defined('PATH_IMG_ADMIN')) {
    define('PATH_IMG_ADMIN', __DIR__ . '/../upload/');
}

// Set error reporting for tests
error_reporting(E_ALL);
ini_set('display_errors', 1);
