# MongoModel
![GitHub Workflow Status](https://img.shields.io/github/workflow/status/grefon/MongoModel/PHP%20Composer)
![GitHub release (latest by date)](https://img.shields.io/github/v/release/grefon/MongoModel)
![GitHub](https://img.shields.io/github/license/grefon/MongoModel)

[на русском](README_ru.md)

MongoModel allows you to conveniently work with MongoDB documents, as with objects.

Implemented:
- [x] typification of properties
- [x] required properties
- [x] default values
- [x] history of changes
- [x] operations with objects

MongoModel work with [MongoDB PHP Library](https://github.com/mongodb/mongo-php-library)

----------------------------------------
<!-- TOC -->
* [Getting Started](#getting-started)
  * [Composer](#composer)
  * [Initialization of connection](#initialization-of-connection)
  * [Your first model](#your-first-model)
* [$fieldsModel](#fieldsmodel)
* [Creating a new object](#creating-a-new-object)
* [Operations with an object](#operations-with-an-object)
  * [Loading](#loading)
    * [By ID](#by-id)
    * [By properties](#by-properties)
  * [Method save](#method-save)
  * [Method saveField](#method-savefield)
  * [Method saveFields](#method-savefields)
  * [Method delete](#method-delete)
  * [Method getArray](#method-getarray)
  * [Loading with cache](#loading-with-cache)
* [Operations with objects](#operations-with-objects)
  * [Method itemsGet](#method-itemsget)
    * [Search data](#search-data)
    * [Return fields](#return-fields)
    * [Sorting](#sorting)
    * [Pagination](#pagination)
    * [Request settings](#request-settings)
  * [Method itemsHas](#method-itemshas)
  * [Method itemsCount](#method-itemscount)
  * [Method itemsDelete](#method-itemsdelete)
  * [Method itemsNew](#method-itemsnew)
* [History of changes](#history-of-changes)
  * [Attribute historyPrepare](#attribute-historyprepare)
  * [Attribute historyValue](#attribute-historyvalue)
  * [Method checkRecordHistory](#method-checkrecordhistory)
* [To help the developer](#to-help-the-developer)
  * [Triggers](#triggers)
  * [Snapshots](#snapshots)
  * [Method getCollection](#method-getcollection)
  * [Method collectionInfo](#method-collectioninfo)
<!-- TOC -->

----------------------------------------

## Getting Started
### Composer

Install this package through [Composer](https://getcomposer.org/).
Edit `require` in your `composer.json`:
```JSON
{
    "require": {
        "grefon/mongo-model": "*"
    }
}
```
and run `composer update`

**or**

run this command in your command line:

```BASH
composer require grefon/mongo-model
```

### Initialization of connection
```PHP
// Loading composer
require __DIR__ . '/vendor/autoload.php';

use MongoDB\Client;
use MongoModel\MongoDB;

// Initialization of connection to MongoDB
/**
 * @param Client $client - MongoDB Client
 * @param string $base   - name of base
 * @param bool   $debug  - debug
 */
MongoDB::init(new Client('mongodb://127.0.0.1/'), 'baseName', true);
```

The `MongoDB` class with debugging enabled will log requests and their status to the `MongoDB::$trace`.

### Your first model

Create collection `users` in MongoDB.

Create new PHP Class `User` extends `ModelMongoDB`:

```PHP
use MongoModel\ModelMongoDB;

/**
 * User Class
 *
 * @property string  userId
 * @property string  surname
 * @property string  name
 * @property string  email
 * @property int     rating
 * @property boolean ban
 * @property string  timeCreate
 * @property string  timeUpdate
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
        'email'       => ['string'],
        'rating'      => ['int', 'default' => 0],
        'ban'         => ['boolean', 'default' => false],
        'timeCreate'  => ['datetime', 'timeCreate'],
        'timeUpdate'  => ['datetime', 'timeUpdate']
    ];

}
```
Each model class must extend `ModelMongoDB`. It must contain 4 main static variables:
- `$collection` - this variable will store an instance \MongoDB\Collection
- `$collectionName` - collection name in MongoDB
- `$primaryKey` - primary key name instead of `_id`
- `$fieldsModel` - array of model fields (object properties)

## $fieldsModel
In $fieldsModel the data model is described as an associative array. The key is the name of the object's property, the value is an array of the property's attributes.

***The first value in an attribute array must always be the data type.***

| Type                    | Description                                                                                 |
|-------------------------|---------------------------------------------------------------------------------------------|
| **string**              | string                                                                                      |
| **boolean** or **bool** | boolean true or false                                                                       |
| **int**                 | number                                                                                      |
| **float**               | floating point number                                                                       |
| **datetime**            | in PHP will be a string (2022-10-03 11:27:15), and MongoDB will store the timestamp ISODate |
| **array**               | array                                                                                       |
| **object**              | object; in PHP it is stdClass                                                               |
| **history**             | array with [history of changes](#history-of-changes)                                        |

_When data is loaded from MongoDB or saved, object properties will be typification._

All subsequent attributes in the array are free, but some of them are reserved:

| Attribute          | Description                                                                                                                                                                                  |
|--------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **required**       | Required object property. If the property value is not specified, then when trying to save the data throw new Exception                                                                      |
| **default**        | Default value. For example: `'rating' => ['int', 'default' => 100]`                                                                                                                          |
| **timeCreate**     | Can only be used on the `datetime` type. Sets the current time if is new object.                                                                                                             |
| **timeUpdate**     | Can only be used on the `datetime` type. Sets the current time each time an object is saved by a method [save()](#method-save).                                                              |
| **onlyCreation**   | Creates a variable in the MongoDB document on first save and no longer updates the data in it. Useful when the data in this property is updated in a third party process by direct requests. |
| **hidden**         | Hide a property on a method [getArray](#method-getarray)                                                                                                                                     |
| **history**        | Write changes to this property to [history](#history-of-changes).                                                                                                                            |
| **historyPrepare** | Specifies the method to check if the property has changed.                                                                                                                                   |
| **historyValue**   | Specifies a method that returns the standardized property value for history.                                                                                                                 |

## Creating a new object

Example: [examples/new.php](examples/new.php)
```PHP
$user = new User(['name' => 'Will', 'surname' => 'Smith']);
$user->save();
```
or
```PHP
$user = new User;
$user->name = 'Will';
$user->surname = 'Smith';
$user->save();
```
or
```PHP
$user = new User;
$user->save(['name' => 'Will', 'surname' => 'Smith']);
```
or
```PHP
$user = User::new(['name' => 'Will', 'surname' => 'Smith'], false);
```
Each of these examples will create a new document in MongoDB:
```JSONiq
{
    "_id" : ObjectId("63399434089c8c26344ff2df"),
    "surname" : "Smith",
    "name" : "Will",
    "email" : null,
    "rating" : NumberInt(0),
    "ban" : false,
    "timeCreate" : ISODate("2022-10-02T13:37:56.000+0000"),
    "timeUpdate" : ISODate("2022-10-02T13:37:56.000+0000")
}
```
It is also possible to create documents with your own ID:
```PHP
$userID = User::new(['userId' => 'user123', 'name' => 'Bob', 'rating' => 15]);
```
```JSONiq
{
    "_id" : "user123",
    "surname" : null,
    "name" : "Bob",
    "email" : null,
    "rating" : NumberInt(15),
    "ban" : false,
    "timeCreate" : ISODate("2022-10-02T13:38:51.000+0000"),
    "timeUpdate" : ISODate("2022-10-02T13:38:51.000+0000")
}
```
_If the ID is 24 characters long, then an attempt will be made to convert the string to [ObjectId](https://www.mongodb.com/docs/manual/reference/method/ObjectId/)._

In the static method `User::new(array $data, bool $returnID = true)` the data array is passed as the first argument, the second - whether only the ID of the created document or the entire instance of the object should be returned; `$returnID = false` - return the entire object.

----------------------------------------

## Operations with an object
### Loading
`YourModelClass::get($data)`

Example: [examples/get.php](examples/get.php)
#### By ID
```PHP
$user = User::get('63399434089c8c26344ff2df');
```
#### By properties
```PHP
$user = User::get(['name' => 'Bob', 'rating' => ['$gte' => 10]]);
```
When loading by properties, a check is made for the existence of the required fields `name` and `rating` in `$fieldsModel`.
It is possible to search by internal properties if there is a dot in the key:
```PHP
$user = User::get(['phones.number' => 123456789);
```
You can also search MongoDB syntax if the key starts with $:
```PHP
// The first found document with a rating less than zero or banned will be returned
$user = User::get(['$or' => [['rating' => ['$lt' => 0]], ['ban' => true]]]);
```
If the document is not found in MongoDB `User::get` will return `null`.

### Method save
`save(array $data = null)`

Example: [examples/save.php](examples/save.php)

The method saves the current object instance to a MongoDB document. Only changed properties are sent to the database.
```PHP
if ($user = User::get('63399434089c8c26344ff2df')) {

    $user->rating = '20'; // string will be converted to int
    $user->save(['email' => 'test@test.com']);
    
}
```
```JSONiq
{
    "_id" : ObjectId("63399434089c8c26344ff2df"),
    "surname" : "Smith",
    "name" : "Will",
    "email" : "test@test.com",
    "rating" : NumberInt(20),
    "ban" : false,
    "timeCreate" : ISODate("2022-10-02T13:37:56.000+0000"),
    "timeUpdate" : ISODate("2022-10-02T14:12:02.000+0000")
}
```
_If the object is updated and not created for the first time, then properties with the `onlyCreation` attribute are ignored, while those with the `history` attribute are checked for changes and written to the history._

### Method saveField
`saveField(string $field, $value = '__EMPTY__')`

Example: [examples/save.php](examples/save.php)

Store in MongoDB only one property of the current object.

_Light saving method. History of changes is not created. The timeUpdate property is not updated automatically._

```PHP
$user->email = 'test@test.com';
$user->rating = 50;
$user->saveField('rating');
```
or
```PHP
$user->email = 'test@test.com';
$user->saveField('rating', 50);
```
Even though you have changed `email` its new value will not be written to MongoDB. Only the `rating` value will be saved to the database.

### Method saveFields
`saveFields($fields = null)`

Example: [examples/save.php](examples/save.php)

Save only specific properties of the current object to MongoDB.

_Light saving method. History of changes is not created. The timeUpdate property is not updated automatically._

```PHP
$user->surname = 'TEST';
$user->email = 'test@test.com';
$user->rating = 50;

// Saves only rating
$user->saveFields('rating');

// Saves only rating and email
$user->saveFields(['rating', 'email']);
```
In your own methods of the `User` class, you can append to the protected changedFields array:
```PHP
class User extends ModelMongoDB
{

    // ..............................

    function changeEmail(string $email) {
        
        $this->email = $email;
        $this->changedFields[] = 'email';
        return $this;
    
    }
    
    function resetRating() {
        
        $this->rating = 0;
        $this->changedFields[] = 'rating';
        return $this;
    
    }

}
```
```PHP
if ($user = User::get('63399434089c8c26344ff2df')) {

    if (!empty($_POST['email'])) {
    
        // Change email
        $user->changeEmail($_POST['email']);
    
    }
    
    // Reset the rating and save the changed object properties in MongoDB
    $user->resetRating()
         ->saveFields();

}
```

### Method delete
`delete()`

Example: [examples/delete.php](examples/delete.php)

Delete a document from MongoDB.
```PHP
if ($user = User::get('63399434089c8c26344ff2df')) {

    $user->delete();

}
```

### Method getArray
`getArray($includeField = null, bool $skipHidden = true)`

Example: [examples/getArray.php](examples/getArray.php)
```PHP
if ($user = User::get('63399434089c8c26344ff2df')) {

    print_r($user->getArray());
    
}
```
```
[
    'userId'      => '63399434089c8c26344ff2df',
    'surname'     => 'Smith',
    'name'        => 'Will',
    'email'       => null,
    'rating'      => 0,
    'ban'         => false,
    'timeCreate'  => '2022-10-02 13:37:56',
    'timeUpdate'  => '2022-10-02 13:37:56'
]
```
The `getArray` method by default does not return properties that have `hidden` in the `$fieldsModel`. You can disable this by passing $skipHidden = false.

**$includeField**

You can pass a single attribute or an array of attributes that are specified for properties in `$fieldsModel`. For example:
```PHP
// Will return only properties that have required in their attributes
$user->getArray('required');

// Will return only properties that have card and short in their attributes
$user->getArray(['card', 'short']);
```

### Loading with cache
`YourModelClass::getFromCache($data)`

Example: [examples/getFromCache.php](examples/getFromCache.php)

When loading with caching, the object instance is stored in memory and when reloading, there is no call to MongoDB. As in the [get()](#loading) method, you can load by ID or properties.

Useful when working with an object in different parts of the code.

```PHP
$userId = '63399434089c8c26344ff2df';
$email = $_POST['email'] ?? null;
$name = $_POST['name'] ?? null;
// ...................................

// In the first section of code
if ($email) {

    $user = User::getFromCache($userId);
    $user->email = $email;

}

// ...................................

// In the second section of code
if ($name) {

    $user = User::getFromCache($userId);
    $user->name = $name;

}

// ...................................
if ($email or $name) {

    User::getFromCache($userId)->save();

}
```

----------------------------------------

## Operations with objects
### Method itemsGet
`YourModelClass::itemsGet($data = null, $fields = null, $orderBy = null, $limited = null, array $settings = [])`

Example: [examples/itemsGet.php](examples/itemsGet.php)

Find documents in MongoDB and return an array of object instances or their defined properties.

#### Search data
`$data`

- **null** - search without conditions in all documents
- **string** or **int** - search by ID
- **array** - an array of IDs or an associative array of properties
```PHP
// Returns an array of all documents
User::itemsGet(); 

// Returns an array with documents that have _id = 26
User::itemsGet(26); 

// Returns an array with documents that have _id = user_12
User::itemsGet('user_12'); 

// Returns an array with documents that have _id = ObjectId("63399434089c8c26344ff2df")
User::itemsGet('63399434089c8c26344ff2df'); 

// Returns an array with documents that have _id = user_12 or ObjectId("63399434089c8c26344ff2df")
User::itemsGet(['user_12', '63399434089c8c26344ff2df']); 

// Returns an array with documents that have name = Will and surname = Smith
User::itemsGet(['name' => 'Will', 'surname' => 'Smith']);
```
The search works in the same way as on the [get()](#loading) method, but the result is always an array with all found documents.

#### Return fields
`$fields`

If **$fields = null** then `itemsGet` will return an array of **object instances**. With each instance, you can perform the same actions as if it were processed after [loading](#loading).
```PHP
foreach (User::itemsGet(['rating' => ['$lt' => 10]], null) as $user) {

    $user->rating += 5;
    $user->saveField('rating');

}
```
If a property is passed to **$fields** as a string, then an associative array with the specified property will be returned as a result of `itemsGet`.
```PHP
foreach (User::itemsGet(['name' => 'Will'], 'rating') as $userId => $rating) {

}
```
If you pass an array of properties to **$fields**, then an associative array will be returned to the `itemsGet` result, where the key will be **_id**, and the value **stdClass** with the specified fields.
```PHP
foreach (User::itemsGet(['name' => 'Will'], ['name', 'surname', 'rating']) as $userId => $item) {

    echo $item->name . ' ' . $item->surname . ' has a rating ' . $item->rating;
    // Will Smith has a rating 10
    // Will Duk has a rating 7
    // ..........

}
```

#### Sorting
`$orderBy`

Specify the field by which you want to sort documents when searching.
```PHP
// Ascending
User::itemsGet(['name' => 'Will'], null, 'rating');

// Descending
User::itemsGet(['name' => 'Will'], null, ['rating', 'DESC']);
```

#### Pagination
`$limited`

Specify the number of documents to be searched and the indent (skip and limit).
```PHP
// Returns the first 10 documents found
User::itemsGet(null, null, null, 10);

// Returns the found document from 21 to 30
User::itemsGet(null, null, null, [20, 10]);
```

#### Request settings
`$settings`

Settings for searching in MongoDB. For example, you can suggest an index:
```PHP
User::itemsGet(['name' => 'Will'], null, 'rating', 10, ['hint' => 'index_name_rating']);
```

Also in the settings you can pass more complex conditions for sorting by several fields.

### Method itemsHas
`YourModelClass::itemsHas($data = null)`

Example: [examples/itemsHas.php](examples/itemsHas.php)

Check if documents exist in the collection according to given conditions.

In **$data** is passed [search data](#search-data), as in the [itemsGet](#method-itemsget) method.

Returns **true** or **false**.

```PHP
if (User::itemsHas(['email' => 'mail@test.com'])) {
    
    die('Email busy');

}
```

### Method itemsCount
`YourModelClass::itemsCount($data = null, array $settings = [])`

Example: [examples/itemsCount.php](examples/itemsCount.php)

Returns the number of documents matching the query.

In **$data** is passed [search data](#search-data), as in the [itemsGet](#method-itemsget) method.

In **$settings** - [request-settings](#request-settings)

```PHP
// How many documents have a 'rating' greater than 100
echo User::itemsCount(['rating' => ['$gt' => 100]]);

// We suggest the index and count up to a maximum of 20
echo User::itemsCount(['rating' => ['$gt' => 100]], ['hint' => 'my_index', 'limit' => 20]);
```

### Method itemsDelete
`YourModelClass::itemsDelete($data = null)`

Example: [examples/itemsDelete.php](examples/itemsDelete.php)

Removes all documents matching the query from the collection.

In **$data** is passed [search data](#search-data), as in the [itemsGet](#method-itemsget) method.

Returns the number of deleted documents.

```PHP
// Delete all documents with 'rating' property less than zero
echo User::itemsDelete(['rating' => ['$lt' => 0]]);
```

### Method itemsNew
`YourModelClass::itemsNew(array $items, bool $returnID = true)`

Example: [examples/itemsNew.php](examples/itemsNew.php)

Creates several objects and inserts `insertMany` into MongoDB.

The method is similar `YourModelClass::new()`, with the only difference that the first argument is an array of data arrays.

Returns array of IDs of created objects, or array of objects (if $returnID = false).

```PHP
$newUsersID = User::itemsNew(
    [
        [
            'name'   => 'Ben',
            'rating' => 77
        ],
        [
            'name'    => 'Robin',
            'surname' => 'Collins'
        ]
    ],
    true
);
```

----------------------------------------

## History of changes

When an object is saved using the [save()](#method-save) method, a history of changes is created. The history is written to an object property of type `history`. Properties that have `history` in their attributes are checked for change.

```PHP
class User extends ModelMongoDB
{

    // ..............................

    static public $fieldsModel = [
        'userId'  => ['string'],
        'surname' => ['string'],                                          // DO NOT track changes
        'name'    => ['string', 'required'],                              // DO NOT track changes
        'email'   => ['string', 'history'],                               // Tracking changes
        'phones'  => ['array', 'history',                                 // Tracking changes
                      'historyPrepare' => 'historyPrepareArray:number', 
                      'historyValue' => 'historyValuePhones'
                     ],
        'history' => ['history']                                          // History array
    ];

}
```

If the properties of an object with the `history` attribute have changed, then an entry will be added to the history array with the date of the change and a `changes` object listing all the changed properties. Each property is an array with two elements array(old value, new value).

```json
{
    "datetime" : "2022-10-05 17:48:41",
    "changes" : {
        "phones" : [
            [],
            ["(+1) 555331188"]
        ],
        "email" : [
            "test@gmail.com",
            "email@gmail.com"
        ]
    }
}
```

### Attribute historyPrepare
In the `historyPrepare` attribute, you can specify your method, which will be used to compare `old value` === `new value`.

Besides comparing simple data types (string, int, float, bool) there are 2 methods:

- **historyPrepareArray** to compare arrays
- **historyPrepareObject** to compare objects

Your own method from the `historyPrepare` attribute and to the `historyPrepareArray` and `historyPrepareObject` methods  will be passed two values:
1) value to be standardized somehow
2) an optional variable from the attribute value after the colon. For example, number will be passed to `'historyPrepare' => 'historyPrepareArray:number'`

Let's imagine that the user has a **phones** property - it's an array of stdClass objects with phones:

```json
[
    {
        "code" : 1,
        "phone" : 555331188,
        "number" : 1555331188,
        "formatted" : "(+1) 555331188",
        "timeAdd" : "2022-10-05 17:48:41"
    },
    {
        "code" : 12,
        "phone" : 7774477,
        "number" : 127774477,
        "formatted" : "(+1) 7774477",
        "timeAdd" : "2022-10-05 19:31:02"
    }
]
```

From the given example `'historyPrepare' => 'historyPrepareArray:number'` the phone array will be compared by the `number` field. When saved, the `historyPrepareArray` method converts the comparison data to the string `[127774477,1555331188]`. The same conversion will be performed on data from [snapshot](#snapshots) after the last save. Further comparison and record in history if there are changes.

### Attribute historyValue

Your **custom** method that will process and return the value to write to the history.

See example: [examples/User.php](examples/User.php#L123)

### Method checkRecordHistory
`checkRecordHistory(array $changes)`

Returns **true** by default; If it returns **false** - the history of changes will not be added.

Called during save when the history of changes has already been compiled.

In **$changes** is passed current changes.

The method is useful if you need to keep a history of changes, but under some circumstances sometimes do not record it.

For example, you can check the size of the current history and not write more than 100 entries:

```PHP
class User extends ModelMongoDB
{

    // ..............................

    protected function checkRecordHistory($changes) {

        return count($this->history) < 100;    
    
    }

}
```

----------------------------------------

## To help the developer
### Triggers
| Method          | Description                                                                                                           |
|-----------------|-----------------------------------------------------------------------------------------------------------------------|
| **afterCreate** | Called after class initialization in __construct.                                                                     |
| **afterLoad**   | Called after loading data from MongoDB.                                                                               |
| **preSave**     | Called before saving.                                                                                                 |
| **afterSave**   | Called after saving. The method is passed an array of property names that have changed and been submitted to MongoDB. |
```PHP
class User extends ModelMongoDB
{

    // ..............................

    function afterLoad() {
        
        // If the rating in MongoDB was less than 500
        if (empty($this->rating) or $this->rating < 500) {
        
            $this->rating = 500;
        
        }
    
    }
    
    function preSave() {
        
        // If after all the manipulations the rating has become less than -100
        if ($this->rating < -100) {
        
            $this->ban = true;
        
        }
    
    }

}
```

### Snapshots
`getSnapshot($field)` and `getSnapshotUpdate($field)`

After loading data from MongoDB, created a snapshot of current state. In the process, you may need to find out what data was in the database or since the last save.
```PHP
if ($user = User::get('63399434089c8c26344ff2df')) {

    $user->name = 'Jack';
    echo $user->name; // Jack
    echo $user->getSnapshot('name'); // Will
    echo $user->getSnapshotUpdate('name'); // Will
    
    // Save name Jack
    $user->saveFields('name');
    
    $user->name = 'Bob';
    echo $user->name; // Bob
    echo $user->getSnapshot('name'); // Will
    echo $user->getSnapshotUpdate('name'); // Jack
    
    // Save name Dan
    $user->save(['name' => 'Dan']);
    
    $user->name = 'Test';
    echo $user->name; // Test
    echo $user->getSnapshot('name'); // Will
    echo $user->getSnapshotUpdate('name'); // Dan   
    
}
```

### Method getCollection
`YourModelClass::getCollection()`

Returns a MongoDB collection. Can be used for direct queries.
```PHP
use MongoModel\MongoDB;

// Ban all users with a rating less than -100
MongoDB::execute(User::getCollection(), 'updateMany', [
    'rating' => ['$lt' => -100]
], [
    '$set' => [
        'ban' => true
    ]
]);
```

### Method collectionInfo
`MongoDB::collectionInfo(string $collection)`

Example: [examples/collectionInfo.php](examples/collectionInfo.php)

Returns information about the collection.
```PHP
use MongoModel\MongoDB;

print_r(MongoDB::collectionInfo('users'));
```
```
stdClass Object
(
    [count] => 27
    [storageSize] => 36864
    [indexSize] => 36864
    [indexCount] => 1
    [size] => 6189
    [avgObjSize] => 229
)
```

