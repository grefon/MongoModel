<?php

include_once '../vendor/autoload.php';
include_once 'connection.php';
include_once 'User.php';

$count = User::itemsDelete(['name' => 'Ben']);
echo 'Removed ' . $count . ' users with name Ben' . PHP_EOL;

$count = User::itemsDelete(['rating' => ['$lt' => 0]]);
echo 'Removed ' . $count . ' users with rating less than zero' . PHP_EOL;

include 'trace.php';
