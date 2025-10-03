<?php

use Steampixel\Route;
use Rakit\Validation\Validator;

// Route to add users
Route::add('/users/store', function() use ($db, $redis) {

	if (!validateRequestMethod('POST')) return;

	// Get input from input stream
	$input = json_decode(file_get_contents('php://input'), true);

	// Validate input
	$validator = new Validator;
	$validation = $validator->make($input, [
		'name' => 'required|min:3',
		'email' => 'required|email',
		'country' => 'required',
	]);
	$validation->validate();

	if ($validation->fails()) {
		return jsonResponse(['errors' => $validation->errors()->firstOfAll()], 422);
	}

	// Prepare user data
	$userData = [
		'name' => $input['name'],
		'email' => $input['email'],
		'country' => $input['country']
	];

	// Insert into MySQL
	$insertId = $db->insert('users', $userData);

	if (!$insertId) {
		return jsonResponse(['error' => 'Failed to store user'], 500);
	}

	// Cache in Redis (hash: user:{email})
	$redis->hMSet('user:' . $userData['email'], $userData);

	return jsonResponse([
		'success' => true,
		'data' => $userData
	], 201);

}, 'POST');


// Route to get user country by email
Route::add('/users/country', function() use ($db, $redis) {

	if (!validateRequestMethod('GET')) return;
	$email = $_GET['email'] ?? null;

	// use validator to validate email
	$validator = new Validator;
	$validation = $validator->make(['email' => $email], [
		'email' => 'required|email',
	]);
	$validation->validate();

	if ($validation->fails()) {
		return jsonResponse(['error' => 'Provide valid email in query parameter'], 422);
	}

	// Check Redis cache first
	$country = $redis->hGet("user:$email", 'country');
	if ($country) {
		return jsonResponse([
			'success' => true,
			'source' => 'cache',
			'country' => $country
		]);
	}

	// Not in cache, try getting from database
	$db->where('email', $email);
	$user = $db->getOne('users');

	if (!$user) {
		return jsonResponse(['error' => 'User not found'], 404);
	}

	// User in database, add to cache
	if (isset($user['country'])) {
		$redis->hMSet("user:$email", ['country' => $user['country']]);
	}

	return jsonResponse([
		'success' => true,
		'source' => 'database',
		'country' => $user['country']
	]);

}, 'get');


// Get user by email
// /users?email=user@somemail.com
Route::add('/users', function() use ($db, $redis) {

	if (!validateRequestMethod('GET')) return;
	$email = $_GET['email'] ?? null;

	// Validate email query parameter
	if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
		return jsonResponse(['error' => 'Provide valid email in query parameter'], 422);
	}

	// Check Redis cache first
	$cachedUser = $redis->hGetAll("user:$email");
	if ($cachedUser && isset($cachedUser['email'])) {
		return jsonResponse([
			'success' => true,
			'source' => 'cache',
			'data' => $cachedUser
		]);
	}

	// Not in cache, try getting from database
	$db->where('email', $email);
	$user = $db->getOne('users');

	if (!$user) {
		return jsonResponse(['error' => 'User not found'], 404);
	}

	// User in database, add to cache
	$redis->hMSet("user:$email", $user);

	return jsonResponse([
		'success' => true,
		'source' => 'database',
		'data' => $user
	]);

}, 'get');
