<?php

include_once '../vendor/autoload.php';
include_once 'connection.php';
include_once 'User.php';


// Return array objects
$users = User::itemsNew(
	[
		[
			'name'   => 'Ben',
			'rating' => 77
		],
		[
			'name'    => 'Robin',
			'surname' => 'Collins'
		],
		[
			'userId' => uniqid('user_'),
			'name'   => 'NewTestUser'
		]
	],
	false
);

foreach ($users as $user) {

	echo 'User success created: ' . $user->userId . ' => ' . $user->name . ' ' . $user->surname . ' (rating ' . $user->rating . ')' . PHP_EOL;

}

// Return only ID
$users = User::itemsNew(
	[
		[
			'name' => 'Jack'
		],
		[
			'name' => 'Kate'
		],
	]
);

foreach ($users as $userId) {

	echo 'New id: ' . $userId . PHP_EOL;

}


include 'trace.php';
