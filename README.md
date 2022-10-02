# MongoModel

## Getting Started



### Composer

Install this package through [Composer](https://getcomposer.org/).
Edit `require` in your project `composer.json`:
```json
{
    "require": {
        "grefon/mongo-model": "dev-main"
    }
}
```
and run `composer update`

**or**

run this command in your command line:

```bash
composer require grefon/mongo-model
```

### Initializing the connection
```php
// Load composer
require __DIR__ . '/vendor/autoload.php';

use MongoDB\Client;
use MongoModel\MongoDB;
use MongoModel\ModelMongoDB;

// Initialize connection to MongoDB
/**
 * @param Client $client - MongoDB Client
 * @param string $base   - base name
 * @param bool   $debug  - debug
 */
MongoDB::init(new Client('mongodb://127.0.0.1/'), 'baseName', true);
```

### Your first model

Create collection `users` in MongoDB.

Create new PHP Class `User` extends `ModelMongoDB`:

```php
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


