<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Ahc\Env\Loader;
use Ahc\Env\Retriever;

// Load environment variables
(new Loader())->load(__DIR__ . '/../.env');

// Utility function to encode and send JSON responses
function jsonResponse($data, $statusCode = 200) {
	header('Content-Type: application/json');
	http_response_code($statusCode);
	return json_encode($data);
}

// Utility function to validate request methods
function validateRequestMethod($method) {
	header('Content-Type: application/json');
	if ($_SERVER['REQUEST_METHOD'] !== $method) {
		echo jsonResponse(['error' => 'Method Not Allowed'], 405);
		return false;
	}
	return true;
}

// Redis initialization
$redis = new Redis();
if (!$redis->connect(Retriever::getEnv('REDIS_HOST'), Retriever::getEnv('REDIS_PORT'))) {
	error_log('Redis connection failed');
	echo jsonResponse(['error' => 'Service temporarily unavailable'], 503);
	die();
}
if (Retriever::getEnv('REDIS_PASSWORD')) {
	if (!$redis->auth(Retriever::getEnv('REDIS_PASSWORD'))) {
		error_log('Redis authentication failed');
		echo jsonResponse(['error' => 'Service temporarily unavailable'], 503);
		die();
	}
}
if (!$redis->select(Retriever::getEnv('REDIS_DB', 0))) {
	error_log('Redis database selection failed');
	echo jsonResponse(['error' => 'Service temporarily unavailable'], 503);
	die();
}

// Database initialization with MysqliDb
try {
	$db = new MysqliDb([
		'host' => Retriever::getEnv('DB_HOST', 'localhost'),
		'username' => Retriever::getEnv('DB_USER'),
		'password' => Retriever::getEnv('DB_PASSWORD'),
		'db' => Retriever::getEnv('DB_DATABASE'),
		'port' => Retriever::getEnv('DB_PORT', 3306),
		'charset' => Retriever::getEnv('DB_CHARSET', 'utf8mb4'),
	]);

	// Test database connection
	$db->ping();
} catch (Exception $e) {
	// Log the actual error for debugging
	error_log('Database connection failed: ' . $e->getMessage());
	// Return generic error to client
	echo jsonResponse(['error' => 'Service temporarily unavailable'], 503);
	die();
}
