<?php

include_once '../vendor/autoload.php';
include_once 'connection.php';
include_once 'User.php';


echo 'Users with name Ben: ' . User::itemsCount(['name' => 'Ben'], ['limit' => 5]) . PHP_EOL; // itemsCount return max 5
echo 'Users with rating less than zero: ' . User::itemsCount(['rating' => ['$lt' => 0]]) . PHP_EOL;
echo 'Users with ID user_test: ' . User::itemsCount('user_test') . PHP_EOL;
echo 'Users with ID user1 or user2: ' . User::itemsCount(['user1', 'user2']) . PHP_EOL;


include 'trace.php';
