<?php

include_once '../vendor/autoload.php';
include_once 'connection.php';

use MongoModel\MongoDB;

MongoDB::base()->createCollection('users');
