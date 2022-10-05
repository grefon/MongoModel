<?php

include_once '../vendor/autoload.php';
include_once 'connection.php';
include_once 'User.php';

$user = User::get('user_test') or die('User ID `user_test` not found; run new-users.php');


// Save field
$user->saveField('surname', 'Testovisch');


// Save fields
$user->surname = null; // Don`t save
$user->email = 'test@test.com';
$user->rating = '20'; // Convert to int
$user->saveFields(['email', 'rating']);


// Save fields
$user
	->resetRating()
	->changeEmail('test@gmail.com')
	->saveFields();


// ----------------------------------------------------------
// Up to this point, the history of changes was not recorded


$user
	->changeEmail('email@gmail.com')
	->addPhone(1, 555331188);

// Full save with history
$user->save(['surname' => 'Testsurname']);

// Nothing will happen as there are no properties changed after save
$user->saveFields();


include 'trace.php';
