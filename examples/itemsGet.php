<?php

include_once '../vendor/autoload.php';
include_once 'connection.php';
include_once 'User.php';


echo 'Get associative array $userId => $name' . PHP_EOL;

foreach (User::itemsGet(['name' => 'Ben'], 'name') as $userId => $name) {

	echo $userId . ' => ' . $name . PHP_EOL;

}

echo PHP_EOL;
echo 'Get associative array $userId => {userId, name, surname}' . PHP_EOL;

foreach (User::itemsGet(['name' => 'Ben'], ['name', 'surname']) as $userId => $item) {

	echo $item->userId . ' => ' . $item->name . ' ' . $item->surname . PHP_EOL;

}


echo PHP_EOL;
echo 'Get objects User array' . PHP_EOL;

foreach (User::itemsGet(['name' => 'Ben']) as $userId => $item) {

	print_r($item->getArray('viewCard'));
	echo PHP_EOL;

}


echo PHP_EOL;
echo 'Get objects User array. Sort by rating desc. Pagination: skip 5, limit 5, return 6, 7, 8, 9, 10' . PHP_EOL;

foreach (User::itemsGet(['ban' => false, 'rating' => ['$gte' => 10]], null, ['rating', 'DESC'], [5, 5]) as $userId => $item) {

	print_r($item->getArray('viewCard'));
	echo PHP_EOL;

}


echo PHP_EOL;
echo 'Get objects User array where ID user_test or user1' . PHP_EOL;

foreach (User::itemsGet(['user_test', 'user1']) as $userId => $item) {

	echo $item->userId . ' => ' . $item->name . ' ' . $item->surname . PHP_EOL;
	echo PHP_EOL;

}


include 'trace.php';
