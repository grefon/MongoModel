<?php

include_once '../vendor/autoload.php';
include_once 'connection.php';
include_once 'User.php';


$user = User::getFromCache('user_test') or die('User ID `user_test` not found; run new-users.php');
echo 'Before change: ' . $user->surname . ' ' . $user->name . PHP_EOL;


$user1 = User::getFromCache('user_test') or die('User ID `user_test` not found; run new-users.php');
$user1->surname = 'testSurname';

$user2 = User::getFromCache('user_test') or die('User ID `user_test` not found; run new-users.php');
$user2->name = 'testName';


// $user = $user1 = $user2
echo 'User1: ' . $user1->surname . ' ' . $user1->name . PHP_EOL;
echo 'User2: ' . $user2->surname . ' ' . $user2->name . PHP_EOL;


// Save rating, email and balance
$user1 = User::getFromCache('user_test') or die('User ID `user_test` not found; run new-users.php');
$user1->resetRating();

$user2 = User::getFromCache('user_test') or die('User ID `user_test` not found; run new-users.php');
$user2->changeEmail('new.mail@gmail.com');

$user3 = User::getFromCache('user_test') or die('User ID `user_test` not found; run new-users.php');
$user3->balance = 50;
$user3->saveFields(['balance']);


include 'trace.php';
