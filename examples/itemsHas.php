<?php

include_once '../vendor/autoload.php';
include_once 'connection.php';
include_once 'User.php';


echo 'Users with name Ben: ' . (User::itemsHas(['name' => 'Ben']) ? 'yes' : 'no') . PHP_EOL;
echo 'Users with rating less than zero: ' . (User::itemsHas(['rating' => ['$lt' => 0]]) ? 'yes' : 'no') . PHP_EOL;
echo 'Users with ID user_test: ' . (User::itemsHas('user_test') ? 'yes' : 'no') . PHP_EOL;
echo 'Users with ID user1 or user2: ' . (User::itemsHas(['user1', 'user2']) ? 'yes' : 'no') . PHP_EOL;


include 'trace.php';
