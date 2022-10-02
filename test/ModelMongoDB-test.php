<?php

include_once '../vendor/autoload.php';

use MongoModel\MongoDB;
use MongoModel\ModelMongoDB;

/**
 * User Class
 *
 * @property string  userId
 * @property string  surname
 * @property string  name
 * @property string  patronymic
 * @property array   phones
 * @property string  email
 * @property string  companyName
 * @property string  companyRole
 * @property array   notes
 * @property object  options
 * @property int     rating
 * @property float   balance
 * @property string  timeCreate
 * @property string  timeUpdate
 * @property boolean ban
 * @property array   history
 *
 */
class User extends ModelMongoDB
{


	static protected $collection;

	static public    $collectionName = 'users';

	static public    $primaryKey     = 'userId';

	static public    $fieldsModel    = [
		'userId'      => ['string'],
		'surname'     => ['string'],
		'name'        => ['string', 'required'],
		'patronymic'  => ['string'],
		'phones'      => ['array', 'history', 'historyPrepare' => 'historyPrepareArray:number'],
		'email'       => ['string', 'history'],
		'companyName' => ['string', 'history'],
		'companyRole' => ['string', 'history', 'default' => 'director'],
		'notes'       => ['array', 'onlyCreation'],
		'options'     => ['object'],
		'rating'      => ['int', 'default' => 0],
		'balance'     => ['float', 'default' => 10],
		'timeCreate'  => ['datetime', 'timeCreate'],
		'timeUpdate'  => ['datetime', 'timeUpdate'],
		'ban'         => ['boolean', 'default' => false],
		'history'     => ['history']
	];


	protected function afterCreate()
	{

		if (!isset($this->options->test)) {

			$this->options->test = true;

		}

	}


}


/////////////////////////////////////////////////////////////////////////////////////////////

/**
 * Initialize connection to MongoDB
 */

MongoDB::init(new \MongoDB\Client('mongodb://127.0.0.1/'), 'base', true);


/**
 * Creating a new user John Doe
 */
$userId = User::new([
	'surname' => 'Doe',
	'name'    => 'John'
]);


/**
 * Loading user John Doe from the database and change his rating
 */
if ($userJohn = User::get($userId)) {

	$userJohn->saveField('rating', 20);

}

/**
 * Creating a new user Will Smith
 */
$userWill = new User(['surname' => 'Smith']);
$userWill->name = 'Will';
$userWill->save(['balance' => 100]);


/**
 * Creating a new user Will Smith
 */
foreach (User::itemsGet([
	'rating' => ['$gte' => 5],
	'ban' => false
]) as $user) {

	echo $user->name . ' ' . $user->surname . '<br>';

}

/**
 * Show requests trace
 */
echo '<hr>';
echo '<pre>';
print_r(MongoDB::$trace);
echo '</pre>';
