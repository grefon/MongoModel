<?php

include_once '../vendor/autoload.php';
include_once 'connection.php';
include_once 'User.php';

$user = User::get('user_test') or die('User ID `user_test` not found; run new-users.php');


echo 'View card:`' . PHP_EOL;
print_r($user->getArray('viewCard'));
echo PHP_EOL . PHP_EOL;


echo 'View card and short:' . PHP_EOL;
print_r($user->getArray(['viewCard', 'viewShort']));
echo PHP_EOL . PHP_EOL;


echo 'View full:' . PHP_EOL;
print_r($user->getArray());
echo PHP_EOL . PHP_EOL;


echo 'View card and hidden:' . PHP_EOL;
print_r($user->getArray(['viewCard', 'hidden'], false));
echo PHP_EOL . PHP_EOL;


include 'trace.php';
