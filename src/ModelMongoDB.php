<?php

namespace MongoModel;

use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON\ObjectId;
use MongoDB\Collection;
use DateTimeZone;
use Exception;
use stdClass;

/**
 * Class ModelMongoDB
 *
 * @property boolean       isNew
 * @property null|stdClass snapshot
 * @property null|stdClass snapshotUpdate
 * @property array         changedFields
 * @property array         history
 * @property float         score
 *
 * @package MongoModel
 */
abstract class ModelMongoDB
{


	/**
	 * @var bool $isNew - instance not stored in database
	 */
	protected $isNew = true;

	/**
	 * @var stdClass $snapshot - snapshot on upload
	 */
	protected $snapshot;

	/**
	 * @var stdClass $snapshotUpdate - snapshot after update
	 */
	protected $snapshotUpdate;


	/**
	 * @var array $changedFields - array of changed fields
	 */
	protected $changedFields = [];


	/**
	 * @var Collection $collection - collection
	 */
	static protected $collection;


	/**
	 * @var string $collectionName - collection name
	 */
	static public $collectionName;


	/**
	 * @var string $primaryKey - primary key name
	 */
	static public $primaryKey;


	/**
	 * @var array $fieldsModel - array of fields with attributes
	 */
	static public $fieldsModel;


	/**
	 * @var array $cacheGet - cache of loaded objects
	 */
	static protected $cacheGet = [];


	/**
	 * Check if array is associative
	 *
	 * @param array $array - array
	 *
	 * @return bool
	 */
	static protected function isAssocArray(array $array): bool
	{

		if ([] === $array) return false;

		return array_keys($array) !== range(0, count($array) - 1);

	}


	/**
	 * Return an instance of a collection
	 *
	 * @return Collection
	 * @throws Exception
	 */
	static public function getCollection(): Collection
	{

		/**
		 * @var self $className
		 */
		$className = get_called_class();

		if (is_null($className::$collection)) {

			$className::$collection = MongoDB::collection($className::$collectionName);

		}

		return $className::$collection;

	}


	/**
	 * Convert value received from database
	 *
	 * @param mixed $value - value in database
	 * @param array $attrs - array of attributes
	 *
	 * @return array|bool|float|int|stdClass|null
	 */
	static protected function valueFromBase($value, array $attrs)
	{

		if ($value === 'unIsset') {

			if (isset($attrs['default'])) {

				if ($attrs['default'] === 'object') {

					$value = new stdClass();

				} elseif ($attrs['default'] === 'array') {

					$value = [];

				} else {

					$value = $attrs['default'];

				}

			} elseif ($attrs[0] === 'object') {

				$value = new stdClass();

			} elseif ($attrs[0] === 'array' or $attrs[0] === 'history') {

				$value = [];

			} else {

				$value = null;

			}

		}

		if (!is_null($value)) {

			if ($attrs[0] === 'int') {

				$value = @intval($value);

			} elseif ($attrs[0] === 'float') {

				$value = @floatval($value);

			} elseif ($attrs[0] === 'bool' or $attrs[0] === 'boolean') {

				$value = @boolval($value);

			} elseif ($attrs[0] === 'array' or $attrs[0] === 'history') {

				if (!empty($value)) {

					$json = @json_decode(@json_encode($value));
					$value = (!is_null($json) and $json !== false) ? $json : [];

					if (!is_array($value)) {

						$value = [];

					}

				}

			} elseif ($attrs[0] === 'datetime' and is_object($value)) {

				$value = $value->toDateTime()->setTimezone(new DateTimeZone(date_default_timezone_get()))->format('Y-m-d H:i:s');

			}

		}

		return $value;

	}


	/**
	 * Create and save a new object
	 *
	 * @param array $data     - data of the added object
	 * @param bool  $returnID - return only ID of new object
	 *
	 * @return self|int|string
	 * @throws Exception
	 */
	static public function new(array $data, bool $returnID = true)
	{

		/**
		 * @var self $object
		 */
		$className = get_called_class();
		$object = new $className($data);
		$object->save();

		if ($returnID) {

			return $object->{$object::$primaryKey};

		}

		return $object;

	}


	/**
	 * Loading object instance with data from database
	 *
	 * @param int|string|array $data - request body
	 *
	 * @return self|null
	 * @throws Exception
	 */
	static public function get($data): ?self
	{

		$hasData = false;

		/**
		 * @var self $className
		 */
		$className = get_called_class();
		$fieldsModel = $className::$fieldsModel;
		$request = [];

		$object = new $className;
		$object->isNew = false;

		if (is_array($data)) {

			foreach ($data as $key => $value) {

				if (isset($fieldsModel[$key]) or strpos($key, '$') === 0 or strpos($key, '.') > 0 or $key === '_id') {

					if ($key === '_id' and is_string($value) and mb_strlen($value, 'utf-8') === 24) {

						$request[$key] = new ObjectId($value);

					} else {

						$request[$key] = $value;

					}

					$hasData = true;

				}

			}

		} elseif (!empty($data)) {

			if (is_string($data) and mb_strlen($data, 'utf-8') === 24) {

				$request['_id'] = new ObjectId($data);

			} else {

				$request['_id'] = $data;

			}

			$hasData = true;

		}

		if (!$hasData) {

			throw new Exception('Invalid $data to load model object on "get"');

		}

		$data = MongoDB::execute($className::getCollection(), 'findOne', $request);

		if ($data and isset($data->_id) and $data->_id) {

			if (is_object($data->_id)) {

				$object->{$className::$primaryKey} = $data->_id->__toString();

			} else {

				$object->{$className::$primaryKey} = $data->_id;

			}

			foreach ($fieldsModel as $field => $attrs) {

				if ($field === $className::$primaryKey) {

					continue;

				}

				$object->{$field} = $className::valueFromBase($data[$field] ?? 'unIsset', $attrs);

			}

			$object->afterLoad();
			$object->setSnapshot();
			$object->setSnapshotUpdate();

			return $object;

		}

		return null;

	}


	/**
	 * Loading object instance using cache
	 *
	 * @param int|string|array $data - request body
	 *
	 * @return self|null
	 * @throws Exception
	 */
	static public function getFromCache($data): ?self
	{

		$cacheName = get_called_class() . '-';

		if (is_array($data)) {

			$cacheName .= json_encode($data);

		} elseif (!empty($data)) {

			$cacheName .= $data;

		} else {

			throw new Exception('Invalid $data to load model object on "getFromCache"');

		}

		if (!isset(self::$cacheGet[$cacheName])) {

			$result = self::get($data);

			if (is_null($result)) {

				return null;

			}

			self::$cacheGet[$cacheName] = $result;

		}

		return self::$cacheGet[$cacheName];

	}


	/**
	 * Building database query
	 *
	 * @param mixed $data - request body
	 *
	 * @return array
	 * @throws Exception
	 */
	static private function itemsBuildRequest($data): array
	{

		/**
		 * @var self $className
		 */
		$className = get_called_class();
		$fieldsModel = $className::$fieldsModel;
		$hasData = false;
		$request = [];

		if (is_null($data)) {

			$hasData = true;

		} elseif (is_array($data)) {

			if (self::isAssocArray($data)) {

				foreach ($data as $key => $value) {

					if (isset($fieldsModel[$key]) or strpos($key, '$') === 0 or strpos($key, '.') > 0 or $key === '_id') {

						$request[$key] = $value;

						$hasData = true;

					}

				}

			} else {

				$request['_id'] = [
					'$in' => array_map(function($id) {

						if (is_string($id) and mb_strlen($id, 'utf-8') === 24) {

							return new ObjectId($id);

						} else {

							return $id;

						}

					}, $data)
				];

				$hasData = true;

			}

		} elseif ($data) {

			if (is_string($data) and mb_strlen($data, 'utf-8') === 24) {

				$request['_id'] = new ObjectId($data);

			} else {

				$request['_id'] = $data;

			}

			$hasData = true;

		}

		if (!$hasData) {

			throw new Exception('Invalid $data on "itemsGet"');

		}

		return $request;

	}


	/**
	 * Check existence objects matching the query
	 *
	 * @param null|string|int|array $data - request body
	 *
	 * @return bool
	 * @throws Exception
	 */
	static public function itemsHas($data = null): bool
	{

		/**
		 * @var self $className
		 */
		$className = get_called_class();

		$data = MongoDB::execute($className::getCollection(), 'findOne', self::itemsBuildRequest($data), [
			'projection' => [
				'_id' => 1
			]
		]);

		if ($data and isset($data->_id) and $data->_id) {

			return true;

		}

		return false;

	}


	/**
	 * Return counts objects matching the query
	 *
	 * @param null|string|int|array $data     - request body
	 * @param null|array            $settings - overlay options parameters
	 *
	 * @return int
	 * @throws Exception
	 */
	static public function itemsCount($data = null, array $settings = []): int
	{

		/**
		 * @var self $className
		 */
		$className = get_called_class();

		return MongoDB::execute($className::getCollection(), 'countDocuments', self::itemsBuildRequest($data), $settings);

	}


	/**
	 * Loading object or array with data from database
	 *
	 * @param null|string|int|array $data     - request body
	 * @param null|string|array     $fields   - return fields
	 * @param null|string|array     $orderBy  - sort parameters
	 * @param null|int|array        $limited  - pagination parameters
	 * @param array                 $settings - overlay options parameters
	 *
	 * @return self[]|array[]|string[]
	 * @throws Exception
	 */
	static public function itemsGet($data = null, $fields = null, $orderBy = null, $limited = null, array $settings = []): array
	{

		/**
		 * @var self $className
		 */
		$className = get_called_class();
		$fieldsModel = $className::$fieldsModel;
		$collection = $className::getCollection();
		$request = self::itemsBuildRequest($data);
		$options = [
			'skip'  => 0,
			'limit' => 0
		];
		$items = [];

		if (is_string($orderBy)) {

			if (isset($fieldsModel[$orderBy])) {

				$options['sort'] = [$orderBy => 1];

			} else {

				throw new Exception('Invalid $orderBy on "itemsGet"');

			}

		} elseif (is_array($orderBy)) {

			if (!empty($orderBy[0]) and isset($fieldsModel[$orderBy[0]])) {

				$options['sort'] = [$orderBy[0] => (empty(!$orderBy[1]) and strtoupper($orderBy[1]) === 'DESC') ? -1 : 1];

			} else {

				throw new Exception('Invalid $orderBy on "itemsGet"');

			}

		}

		if (is_int($limited) and $limited > 0) {

			$options['limit'] = $limited;

		} elseif (is_array($limited)) {

			if (isset($limited[0]) and isset($limited[1]) and $limited[0] >= 0 and $limited[1] > 0) {

				$options['skip'] = $limited[0];
				$options['limit'] = $limited[1];

			} else {

				throw new Exception('Invalid $limited on "itemsGet"');

			}

		}

		foreach ($settings as $settingName => $settingValue) {

			$options[$settingName] = $settingValue;

		}

		if (is_string($fields)) {

			if (!isset($fieldsModel[$fields])) {

				throw new Exception('Invalid $fields on "itemsGet"');

			}

			if (!isset($options['projection'])) {

				$options['projection'] = [];

			}

			$options['projection']['_id'] = 1;
			$options['projection'][$fields] = 1;

			foreach (MongoDB::execute($collection, 'find', $request, $options) as $data) {

				if ($data and isset($data->_id) and $data->_id) {

					if (is_object($data->_id)) {

						$data->_id = $data->_id->__toString();

					}

					$items[$data->_id] = $className::valueFromBase($data->{$fields} ?? 'unIsset', $fieldsModel[$fields]);

				}

			}

		} elseif (is_array($fields)) {

			if (!isset($options['projection'])) {

				$options['projection'] = [];

			}

			$options['projection']['_id'] = 1;

			foreach ($fields as $field) {

				if (!isset($fieldsModel[$field])) {

					throw new Exception('Invalid $fields on "itemsGet"');

				}

				$options['projection'][$field] = 1;

			}

			foreach (MongoDB::execute($collection, 'find', $request, $options) as $data) {

				if ($data and isset($data->_id) and $data->_id) {

					if (is_object($data->_id)) {

						$data->{$className::$primaryKey} = $data->_id->__toString();

					} else {

						$data->{$className::$primaryKey} = $data->_id;

					}

					$items[$data->{$className::$primaryKey}] = new stdClass();
					$items[$data->{$className::$primaryKey}]->{$className::$primaryKey} = $data->{$className::$primaryKey};

					if (isset($data->score)) {

						$items[$data->{$className::$primaryKey}]->score = $data->score;

					}

					foreach ($fields as $field) {

						$items[$data->{$className::$primaryKey}]->{$field} = $className::valueFromBase($data->{$field} ?? 'unIsset', $fieldsModel[$field]);

					}

				}

			}

		} else {

			foreach (MongoDB::execute($collection, 'find', $request, $options) as $data) {

				if ($data and isset($data->_id) and $data->_id) {

					$object = new $className;
					$object->isNew = false;

					if (isset($data->score)) {

						$object->score = $data->score;

					}

					if (is_object($data->_id)) {

						$data->{$className::$primaryKey} = $data->_id->__toString();

					} else {

						$data->{$className::$primaryKey} = $data->_id;

					}

					foreach ($fieldsModel as $field => $attrs) {

						$object->{$field} = $className::valueFromBase($data->{$field} ?? 'unIsset', $attrs);

					}

					$object->afterLoad();
					$object->setSnapshot();
					$object->setSnapshotUpdate();

					$items[$object->{$className::$primaryKey}] = $object;

				}

			}

		}

		return $items;

	}


	/**
	 * Removing one or more objects from database
	 *
	 * @param mixed $data - request
	 *
	 * @return int
	 * @throws Exception
	 */
	static public function itemsDelete($data = null): int
	{

		$hasData = false;

		/**
		 * @var self $className
		 */
		$className = get_called_class();
		$fieldsModel = $className::$fieldsModel;
		$request = [];

		if (is_null($data)) {

			$hasData = true;

		} elseif (is_array($data)) {

			if (self::isAssocArray($data)) {

				foreach ($data as $key => $value) {

					if (isset($className::$fieldsModel[$key])) {

						$request[$key] = $value;

						$hasData = true;

					}

				}

			} else {

				$request['_id'] = [
					'$in' => array_map(function($id) {

						if (is_string($id) and mb_strlen($id, 'utf-8') === 24) {

							return new ObjectId($id);

						} else {

							return $id;

						}

					}, $data)
				];

				$hasData = true;

			}

		} elseif ($data) {

			if (is_string($data) and mb_strlen($data, 'utf-8') === 24) {

				$request['_id'] = new ObjectId($data);

			} else {

				$request['_id'] = $data;

			}

			$hasData = true;

		}

		if (!$hasData) {

			throw new Exception('Invalid $data on "itemsDelete"');

		}

		return MongoDB::execute($className::getCollection(), 'deleteMany', $request)->getDeletedCount();

	}


	/**
	 * Return request body with ID
	 *
	 * @return array
	 */
	protected function getRequestId(): array
	{

		$request = [];

		if (is_string($this->{$this::$primaryKey}) and mb_strlen($this->{$this::$primaryKey}, 'utf-8') === 24) {

			$request['_id'] = new ObjectId($this->{$this::$primaryKey});

		} else {

			$request['_id'] = $this->{$this::$primaryKey};

		}

		return $request;

	}


	/**
	 * Compare array for history
	 *
	 * @param mixed       $array
	 * @param null|string $var
	 *
	 * @return string
	 */
	protected function historyPrepareArray($array, string $var = null): string
	{

		$result = '';

		if (is_array($array)) {

			if (is_null($var)) {

				sort($array);

				$result = json_encode($array, JSON_UNESCAPED_UNICODE);

			} else {

				$arrayTemp = [];

				foreach ($array as $item) {

					$varTemp = '';

					if (is_object($item)) {

						$varTemp = $item->{$var} ?? null;

					} elseif (is_array($item)) {

						$varTemp = $item[$var] ?? null;

					}

					if (is_object($varTemp) or is_array($varTemp)) {

						$varTemp = json_encode($varTemp, JSON_UNESCAPED_UNICODE);

					} elseif ($varTemp === true) {

						$varTemp = '1';

					} elseif ($varTemp === false) {

						$varTemp = '0';

					} else {

						$varTemp = strval($varTemp);

					}

					$arrayTemp[] = $varTemp;

				}

				sort($arrayTemp);

				$result = json_encode($arrayTemp, JSON_UNESCAPED_UNICODE);

			}

		}

		return $result;

	}


	/**
	 * Compare objects for history
	 *
	 * @param mixed       $object - object to compare
	 * @param string|null $var    - variable name
	 *
	 * @return string
	 */
	protected function historyPrepareObject($object, string $var = null): string
	{

		$result = '';

		if (is_object($object)) {

			if (is_null($var)) {

				$result = json_encode($object, JSON_UNESCAPED_UNICODE);

			} elseif (isset($object->{$var})) {

				if (is_object($object->{$var}) or is_array($object->{$var})) {

					$result = json_encode($object->{$var}, JSON_UNESCAPED_UNICODE);

				} elseif ($object->{$var} === true) {

					$result = '1';

				} elseif ($object->{$var} === false) {

					$result = '0';

				} else {

					$result = strval($object->{$var});

				}

			}

		}

		return $result;

	}


	/**
	 * Post-processing after creation
	 */
	protected function afterCreate()
	{
	}


	/**
	 * Post-processing after loading
	 */
	protected function afterLoad()
	{
	}


	/**
	 * Preprocessing before saving
	 */
	protected function preSave()
	{
	}


	/**
	 * Trigger after save
	 *
	 * @param array $fields - fields
	 */
	protected function afterSave(array $fields)
	{
	}


	/**
	 * Checking if history is required
	 *
	 * @param array $changes - changes
	 *
	 * @return bool
	 */
	protected function checkRecordHistory(array $changes): bool
	{

		return true;

	}


	/**
	 * Send an array with updated data to the database
	 *
	 * @param array $data - array of validated data
	 *
	 * @throws Exception
	 */
	protected function sendUpdateData(array $data)
	{

		MongoDB::execute($this::getCollection(), 'updateOne', $this->getRequestId(), ['$set' => $data]);

		$this->afterSave(array_keys($data));

	}


	/**
	 * Saving object to database
	 *
	 * @param array|null $data - assignment data
	 *
	 * @return self
	 * @throws Exception
	 */
	function save(array $data = null): self
	{

		if (is_array($data) and count($data)) {

			foreach ($this::$fieldsModel as $field => $attrs) {

				if (isset($data[$field])) {

					$this->{$field} = self::valueFromBase($data[$field], $attrs);

				}

			}

		}

		$this->preSave();

		$data = [];
		$changes = [];

		foreach ($this::$fieldsModel as $field => $attrs) {

			if (in_array('onlyCreation', $attrs, true) and !$this->isNew) {

				continue;

			}

			if ($field === $this::$primaryKey) {

				if ($this->isNew and in_array('required', $attrs, true)) {

					if (isset($this->{$field})) {

						if (is_string($this->{$field}) and mb_strlen($this->{$field}, 'utf-8') === 24) {

							$data['_id'] = new ObjectId($this->{$field});

						} else {

							$data['_id'] = $this->{$field};

						}

					} else {

						throw new Exception('Model validate error: $field ' . $field . ' required');

					}

				} else {

					continue;

				}

			}

			if (isset($this->{$field})) {

				$data[$field] = $this->{$field};

				if ($attrs[0] === 'int') {

					$data[$field] = @intval($data[$field]);

				} elseif ($attrs[0] === 'float') {

					$data[$field] = @floatval($data[$field]);

				} elseif ($attrs[0] === 'bool' or $attrs[0] === 'boolean') {

					$data[$field] = @boolval($data[$field]);

				} elseif ($attrs[0] === 'datetime') {

					$data[$field] = new UTCDateTime(strtotime($data[$field]) * 1000);

				} elseif ($attrs[0] === 'string') {

					$data[$field] = strval($data[$field]);

				}

			} else {

				$data[$field] = null;

			}

			if (in_array('timeCreate', $attrs, true) and ($this->isNew or empty($this->{$field}))) {

				$now = time();
				$this->{$field} = date('Y-m-d H:i:s', $now);
				$data[$field] = new UTCDateTime($now * 1000);

			} elseif (in_array('timeUpdate', $attrs, true)) {

				$now = time();
				$this->{$field} = date('Y-m-d H:i:s', $now);
				$data[$field] = new UTCDateTime($now * 1000);

			}

			if (in_array('required', $attrs, true) and ($data[$field] === null or $data[$field] === '' or $data[$field] === false)) {

				throw new Exception('Model validate error: $field ' . $field . ' required');

			}

			if (!$this->isNew) {

				$valueOld = $this->snapshotUpdate->{$field};
				$valueNew = $this->{$field};

				if ($field === '$') {

					unset($data[$field]);
					continue;

				} elseif (in_array('timeUpdate', $attrs, true)) {

					continue;

				} elseif ($attrs[0] === 'array' and !isset($attrs['historyPrepare'])) {

					$valueOld = $this->historyPrepareArray($valueOld);
					$valueNew = $this->historyPrepareArray($valueNew);

				} elseif ($attrs[0] === 'object' and !isset($attrs['historyPrepare'])) {

					$valueOld = $this->historyPrepareObject($valueOld);
					$valueNew = $this->historyPrepareObject($valueNew);

				} elseif (isset($attrs['historyPrepare']) and !empty($attrs['historyPrepare'])) {

					$prepareMethodVar = explode(':', $attrs['historyPrepare']);

					$prepareMethod = $prepareMethodVar[0];
					$prepareVar = (isset($prepareMethodVar[1]) and !empty($prepareMethodVar[1])) ? $prepareMethodVar[1] : null;

					$valueOld = $this->$prepareMethod($valueOld, $prepareVar, $attrs);
					$valueNew = $this->$prepareMethod($valueNew, $prepareVar, $attrs);

				} else {

					if ($valueOld === true) {

						$valueOld = '1';

					} elseif ($valueOld === false) {

						$valueOld = '0';

					} else {

						$valueOld = strval($valueOld);

					}

					if ($valueNew === true) {

						$valueNew = '1';

					} elseif ($valueNew === false) {

						$valueNew = '0';

					} else {

						$valueNew = strval($valueNew);

					}

				}

				if ($valueOld === $valueNew) {

					unset($data[$field]);

				} elseif ($field !== 'history' and in_array('history', $attrs, true)) {

					$valueOld = $this->snapshotUpdate->{$field};
					$valueNew = $this->{$field};

					if (isset($attrs['historyValue']) and !empty($attrs['historyValue'])) {

						$valueOld = $this->{$attrs['historyValue']}($valueOld);
						$valueNew = $this->{$attrs['historyValue']}($valueNew);

					}

					$changes[$field] = [$valueOld, $valueNew];

				}

			}

		}

		if (isset($data[$this::$primaryKey])) {

			unset($data[$this::$primaryKey]);

		}

		if ($this->isNew) {

			$result = MongoDB::execute($this::getCollection(), 'insertOne', $data);

			if (!$id = $result->getInsertedId()) {

				throw new Exception('MongoDB insert error');

			}

			if (is_object($id)) {

				$id = $id->__toString();

			}

			$this->{$this::$primaryKey} = $id;
			$this->isNew = false;

			$this->afterSave(array_keys($data));
			$this->setSnapshot();
			$this->setSnapshotUpdate();

		} else {

			if ($this->checkRecordHistory($changes) and isset($this::$fieldsModel['history']) and count($changes)) {

				$this->history[] = (object)[
					'datetime' => date('Y-m-d H:i:s'),
					'changes'  => (object)$changes
				];

				$data['history'] = $this->history;

			}

			if (count($data)) {

				$this->sendUpdateData($data);
				$this->setSnapshotUpdate();

			}

		}

		return $this;

	}


	/**
	 * Send a definite field of object to the database
	 *
	 * @param string $field - field name
	 * @param mixed  $value - value
	 *
	 * @throws Exception
	 */
	public function saveField(string $field, $value = '__EMPTY__')
	{

		if ($value !== '__EMPTY__') {

			$this->{$field} = $value;

		}

		$changedFields = $this->changedFields;
		$this->changedFields = [];

		$this->saveFields($field);

		$this->changedFields = $changedFields;

	}


	/**
	 * Send a definite fields of object to the database
	 *
	 * @param null|array|string $fields - fields name
	 *
	 * @throws Exception
	 */
	public function saveFields($fields = null)
	{

		if ($this->isNew) {

			throw new Exception('Forbidden save field on new object');

		}

		if (!empty($fields)) {

			if (is_string($fields)) {

				$this->changedFields[] = $fields;

			} elseif (is_array($fields)) {

				$this->changedFields = array_merge($this->changedFields, $fields);

			}

		}

		$this->changedFields = array_values(array_diff(array_unique($this->changedFields), ['', null]));

		if (count($this->changedFields)) {

			$fieldsModel = $this::$fieldsModel;
			$data = [];

			foreach ($this->changedFields as $field) {

				if (!isset($fieldsModel[$field]) or $field === $this::$primaryKey) {

					throw new Exception('Invalid $field ' . $field . ' on "saveFields"');

				}

				if (in_array('onlyCreation', $fieldsModel[$field], true)) {

					throw new Exception('Model validate error: $field ' . $field . ' onlyCreation');

				}

				$data[$field] = $this->{$field};

				if (!is_null($data[$field])) {

					if ($fieldsModel[$field][0] === 'int') {

						$data[$field] = intval($data[$field]);

					} elseif ($fieldsModel[$field][0] === 'float') {

						$data[$field] = floatval($data[$field]);

					} elseif ($fieldsModel[$field][0] === 'bool' or $fieldsModel[$field][0] === 'boolean') {

						$data[$field] = boolval($data[$field]);

					} elseif ($fieldsModel[$field][0] === 'datetime') {

						$data[$field] = new UTCDateTime(strtotime($data[$field]) * 1000);

					} elseif ($fieldsModel[$field][0] === 'string') {

						$data[$field] = strval($data[$field]);

					}

				}

				if (in_array('required', $fieldsModel[$field], true) and ($data[$field] === null or $data[$field] === '' or $data[$field] === false)) {

					throw new Exception('Model validate error: $field ' . $field . ' required');

				}

			}

			$this->sendUpdateData($data);

			foreach ($data as $field => $value) {

				$this->snapshotUpdate->{$field} = $this->{$field} ?? null;

			}

			$this->changedFields = [];

		}

	}


	/**
	 * Delete the current object from the database
	 *
	 * @return bool
	 * @throws Exception
	 */
	function delete(): bool
	{

		if (!$this->isNew) {

			MongoDB::execute($this::getCollection(), 'deleteOne', $this->getRequestId());

			return true;

		}

		return false;

	}


	/**
	 * Creation snapshot on upload
	 *
	 * @return void
	 */
	protected function setSnapshot()
	{

		$this->snapshot = new stdClass();

		foreach ($this::$fieldsModel as $field => $attrs) {

			$this->snapshot->{$field} = $this->{$field} ?? null;

		}

		$this->snapshot = json_decode(json_encode($this->snapshot));

	}


	/**
	 * Creation snapshot on update
	 *
	 * @return void
	 */
	protected function setSnapshotUpdate()
	{

		$this->snapshotUpdate = new stdClass();

		foreach ($this::$fieldsModel as $field => $attrs) {

			$this->snapshotUpdate->{$field} = $this->{$field} ?? null;

		}

		$this->snapshotUpdate = json_decode(json_encode($this->snapshotUpdate));

	}


	/**
	 * Return field value from snapshot
	 *
	 * @param $field - field name
	 *
	 * @return mixed
	 */
	public function getSnapshot($field)
	{

		return (isset($this->snapshot->{$field})) ? $this->snapshot->{$field} : null;

	}


	/**
	 * Return field value from snapshotUpdate
	 *
	 * @param $field - field name
	 *
	 * @return mixed
	 */
	public function getSnapshotUpdate($field)
	{

		return (isset($this->snapshotUpdate->{$field})) ? $this->snapshotUpdate->{$field} : null;

	}


	/**
	 * Return an array of object instance data
	 *
	 * @param null|array|string $includeField - key by which to load fields
	 * @param bool              $skipHidden   - skip hidden fields
	 *
	 * @return array
	 */
	public function getArray($includeField = null, bool $skipHidden = true): array
	{

		$filters = is_array($includeField) ? $includeField : (is_string($includeField) ? [$includeField] : null);
		$data = [];

		foreach ($this::$fieldsModel as $field => $attrs) {

			if ($skipHidden and in_array('hidden', $attrs, true)) {

				continue;

			}

			if (!is_null($filters)) {

				$continue = true;

				foreach ($filters as $filter) {

					if (in_array($filter, $attrs, true)) {

						$continue = false;
						break;

					}

				}

				if ($continue) {

					continue;

				}

			}

			if (isset($this->{$field})) {

				if (isset($attrs['prepare'])) {

					$data[$field] = $this->{$attrs['prepare']}($field, $filters);

				} else {

					$data[$field] = $this->{$field};

				}

			} else {

				$data[$field] = null;

			}

		}

		if (isset($this->score) and !isset($this::$fieldsModel['score'])) {

			$data['score'] = $this->score;

		}

		return $data;

	}


	/**
	 * Model constructor.
	 *
	 * @param array $data - data to preload on object creation
	 */
	public function __construct(array $data = [])
	{


		$this->snapshot = new stdClass();
		$this->snapshotUpdate = new stdClass();

		if (count($data)) {

			foreach ($this::$fieldsModel as $field => $attrs) {

				$this->{$field} = self::valueFromBase($data[$field] ?? 'unIsset', $attrs);

			}

		} else {

			foreach ($this::$fieldsModel as $field => $attrs) {

				$this->{$field} = $this::valueFromBase('unIsset', $attrs);

			}

		}

		$this->afterCreate();

	}


	/**
	 * Converts object data to a JSON string.
	 *
	 * @return string Converted data
	 */
	public function __toString()
	{

		return json_encode($this->getArray(), JSON_UNESCAPED_UNICODE);

	}


}