<?php

use MongoDB\Client;
use MongoModel\MongoDB;

/**
 * Initialize connection to MongoDB
 */
MongoDB::init(new Client('mongodb://127.0.0.1/'), 'myBase', true);
