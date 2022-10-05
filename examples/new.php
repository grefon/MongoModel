<?php

include_once '../vendor/autoload.php';
include_once 'connection.php';
include_once 'User.php';


// Add user Will Smith
$userWillSmith = new User(['name' => 'Will', 'surname' => 'Smith']);
$userWillSmith->save();

echo $userWillSmith->name . ' ' . $userWillSmith->surname . ' saved; ID: ' . $userWillSmith->userId . PHP_EOL;


// Add user Ben Affleck
$userBenAffleck = new User;
$userBenAffleck->name = 'Ben';
$userBenAffleck->surname = 'Affleck';
$userBenAffleck->rating = 100;
$userBenAffleck->save();

echo $userBenAffleck->name . ' ' . $userBenAffleck->surname . ' saved; ID: ' . $userBenAffleck->userId . PHP_EOL;


// Add user Ben Schwartz
$userBenSchwartz = new User;
$userBenSchwartz->save(['name' => 'Ben', 'surname' => 'Schwartz', 'email' => 'schwartz.ben@gmail.com']);

echo $userBenSchwartz->name . ' ' . $userBenSchwartz->surname . ' saved; ID: ' . $userBenSchwartz->userId . PHP_EOL;


// Add user TEST with userId
try {

	$userTest = User::new([
		'userId' => 'user_test',
		'name'   => 'TEST'
	], false);

	echo $userTest->name . ' ' . $userTest->surname . ' saved; ID: ' . $userTest->userId . PHP_EOL;

} catch (Exception $exception) {

	$message = $exception->getMessage();

	if (strpos($message, '_id_ dup key') !== false) {

		echo 'ID `user_test` already exists' . PHP_EOL;

	} else {

		echo $message;

	}

}


// Adding user Schwarzenegger will fail because a required property 'name' is not specified
try {

	$userTest = User::new([
		'surname' => 'Schwarzenegger'
	], false);

} catch (Exception $exception) {

	echo 'Error add Schwarzenegger: ' . $exception->getMessage();

}

include 'trace.php';
