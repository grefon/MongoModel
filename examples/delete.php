<?php

include_once '../vendor/autoload.php';
include_once 'connection.php';
include_once 'User.php';


if ($user = User::get('user_test')) {

	$user->delete();
	echo 'User with ID user_test removed from database' . PHP_EOL;

}


include 'trace.php';
