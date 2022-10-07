<?php

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
		'userId'      => ['string', 'viewCard'],
		'surname'     => ['string', 'viewCard'],
		'name'        => ['string', 'required', 'viewCard'],
		'phones'      => ['array', 'history', 'historyPrepare' => 'historyPrepareArray:number', 'historyValue' => 'historyValuePhones', 'viewShort'],
		'email'       => ['string', 'history', 'viewShort'],
		'companyName' => ['string', 'history', 'viewShort'],
		'companyRole' => ['string', 'history', 'default' => 'director', 'viewShort'],
		'notes'       => ['array', 'onlyCreation'],
		'options'     => ['object', 'hidden'],
		'rating'      => ['int', 'default' => 0],
		'balance'     => ['float', 'default' => 10],
		'timeCreate'  => ['datetime', 'timeCreate'],
		'timeUpdate'  => ['datetime', 'timeUpdate'],
		'ban'         => ['boolean', 'default' => false],
		'history'     => ['history']
	];


	/**
	 * Change Email
	 *
	 * @param string $email - email
	 *
	 * @return $this
	 */
	public function changeEmail(string $email): self
	{

		$this->email = $email;
		$this->changedFields[] = 'email';

		return $this;

	}


	/**
	 * Reset Rating
	 *
	 * @return $this
	 */
	public function resetRating(): self
	{

		$this->rating = 0;
		$this->changedFields[] = 'rating';

		return $this;

	}


	/**
	 * Add phone
	 *
	 * @param int $code
	 * @param int $phone
	 *
	 * @return $this
	 */
	public function addPhone(int $code, int $phone): self
	{

		$item = new stdClass();

		$item->code = $code;
		$item->phone = $phone;
		$item->number = intval($code . $phone);
		$item->formatted = '(+' . $code . ') ' . $phone;
		$item->timeAdd = date('Y-m-d H:i:s');

		$this->phones[] = $item;
		$this->changedFields[] = 'phones';

		return $this;

	}


	/**
	 * Handling phone values before writing to history
	 *
	 * @param mixed $phones
	 *
	 * @return array
	 */
	protected function historyValuePhones($phones): array
	{

		$result = [];

		if (is_array($phones) and !empty($phones)) {

			foreach ($phones as $phone) {

				$result[] = $phone->formatted;

			}

		}

		return $result;

	}


}