<?php

include_once '../vendor/autoload.php';
include_once 'connection.php';
include_once 'User.php';


// Get by ID
if ($user = User::get('user_test')) {

	echo 'Get user_test: ' . $user->userId . ' => ' . $user->name . ' ' . $user->surname . PHP_EOL;

}


// Get by request
if ($user = User::get(['ban' => false, 'name' => 'Ben', 'rating' => ['$ne' => 0]])) {

	echo 'Get Ben: ' . $user->userId . ' => ' . $user->name . ' ' . $user->surname . PHP_EOL;

}


// Get by $or
if ($user = User::get(['$or' => [['rating' => ['$lt' => 0]], ['ban' => true]]])) {

	echo 'Get by $or: ' . $user->userId . ' => ' . $user->name . ' ' . $user->surname . PHP_EOL;

}


include 'trace.php';
