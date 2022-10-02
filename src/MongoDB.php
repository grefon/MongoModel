<?php

namespace MongoModel;

use MongoDB\Collection;
use MongoDB\Database;
use MongoDB\Client;
use Exception;
use stdClass;

/**
 * Class MongoDB
 * The class that stores the database connection client and traces requests
 *
 * @package MongoModel
 */
class MongoDB
{


	/**
	 * @var Client $client - MongoDB Client
	 */
	private static $client = null;


	/**
	 * @var string $base - base name
	 */
	private static $base = null;


	/**
	 * @var bool $debug - debug
	 */
	private static $debug = false;


	/**
	 * @var array $trace - tracing
	 */
	public static $trace = [];


	/**
	 * @param Client $client - MongoDB Client
	 * @param string $base   - base name
	 * @param bool   $debug  - debug
	 *
	 * @return void
	 */
	public static function init(Client $client, string $base, bool $debug = false)
	{

		self::$client = $client;
		self::$base = $base;
		self::$debug = $debug;

	}


	/**
	 * Return MongoDB client
	 *
	 * @return Client
	 * @throws Exception
	 */
	public static function client(): Client
	{

		if (empty(self::$client)) {

			throw new Exception('Client not initialized by \MongoModel\MongoDB::init()');

		}

		return self::$client;

	}


	/**
	 * Return MongoDB database
	 *
	 * @return Database
	 * @throws Exception
	 */
	public static function base(): Database
	{

		if (empty(self::$base)) {

			throw new Exception('Base not initialized by \MongoModel\MongoDB::init()');

		}

		return self::client()->{self::$base};

	}


	/**
	 * Return MongoDB Collection
	 *
	 * @param string $collection - collection name
	 *
	 * @return Collection
	 * @throws Exception
	 */
	public static function collection(string $collection): Collection
	{

		return self::base()->{$collection};

	}


	/**
	 * Return information about MongoDB collection
	 *
	 * @param string $collection - collection name
	 *
	 * @return stdClass
	 * @throws Exception
	 */
	public static function collectionInfo(string $collection): stdClass
	{

		$result = new stdClass();
		$result->count = 0;
		$result->storageSize = 0;
		$result->indexSize = 0;
		$result->indexCount = 0;
		$result->size = 0;
		$result->avgObjSize = 0;

		$collection = self::collection($collection);

		$cursor = $collection->aggregate([
			[
				'$collStats' => [
					'storageStats' =>
						['scale' => 1]
				]
			]
		]);

		foreach ($cursor as $item) {

			if (!empty($item->storageStats)) {

				$result->count = $item->storageStats->count;
				$result->storageSize = $item->storageStats->storageSize;
				$result->indexSize = $item->storageStats->totalIndexSize;
				$result->indexCount = $item->storageStats->nindexes;
				$result->size = $item->storageStats->size;
				$result->avgObjSize = $item->storageStats->avgObjSize;

			}

			break;

		}

		return $result;

	}


	/**
	 * Execution via traceback
	 *
	 * @param Collection $collection - collection
	 * @param string     $method     - method name
	 * @param array      $filter     - filter
	 * @param array      $options    - options
	 * @param array      $settings   - settings
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public static function execute(Collection $collection, string $method, array $filter = [], array $options = [], array $settings = [])
	{

		$report = new stdClass();
		$traceStart = microtime(true);
		$error = null;
		$result = null;

		$useOptions = !empty($options);
		$useSettings = !empty($settings);

		if (self::$debug) {

			$report->database = $collection->getDatabaseName();
			$report->collection = $collection->getCollectionName();
			$report->method = $method;

			$report->filter = $filter;
			$report->filterJSON = json_encode($filter, JSON_UNESCAPED_UNICODE);

			$report->options = $useOptions ? $options : null;
			$report->optionsJSON = $useOptions ? json_encode($options, JSON_UNESCAPED_UNICODE) : '{}';

			$report->settings = $useSettings ? $settings : null;
			$report->settingsJSON = $useSettings ? json_encode($settings, JSON_UNESCAPED_UNICODE) : '{}';

			$report->backtrace = debug_backtrace();

			$report->query = 'db.getCollection("' . $report->collection . '").' . $method . '(' . $report->filterJSON . ($useOptions ? (', ' . $report->optionsJSON . ($useSettings ? ', ' . $report->settingsJSON : '')) : '') . ');';

		}

		try {

			if ($useOptions) {

				if ($useSettings) {

					$result = $collection->{$method}($filter, $options, $settings);

				} else {

					$result = $collection->{$method}($filter, $options);

				}

			} else {

				$result = $collection->{$method}($filter);

			}

		} catch (Exception $exception) {

			$error = nl2br($exception->__toString());

		}

		if (self::$debug) {

			if (is_null($error)) {

				$report->status = 'success';

			} else {

				$report->status = 'error';
				$report->error = $error;

			}

			$report->timeGeneration = (microtime(true) - $traceStart);

			self::$trace[] = $report;

		}

		if (!is_null($error)) {

			throw new Exception($error);

		}

		return $result;

	}


}
