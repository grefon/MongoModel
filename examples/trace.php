<?php

use MongoModel\MongoDB;

echo PHP_EOL . '=================================' . PHP_EOL . 'MongoDB::$trace' . PHP_EOL . PHP_EOL;

foreach (MongoDB::$trace as $item) {

	echo $item->query . PHP_EOL;
	echo $item->status . ' => ' . $item->timeGeneration . PHP_EOL . PHP_EOL;

}
