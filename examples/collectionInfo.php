<?php

include_once '../vendor/autoload.php';
include_once 'connection.php';

use MongoModel\MongoDB;

print_r(MongoDB::collectionInfo('users'));
