<?php

use Steampixel\Route;

// Bootstrap: Load dependencies and initialize services
require_once __DIR__ . '/bootstrap.php';

// Routes: Define application routes
require_once __DIR__ . '/routes.php';

// Run the router
Route::run('/');
?>
