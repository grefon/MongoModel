# MongoModel

[english](README.md)

MongoModel позволяет удобно работать с документами MongoDB, как с моделями данных.

Реализованы:
- типизация свойств
- обязательные параметры
- значения по умолчанию
- история изменений
- операции с документами

MongoModel работает с [MongoDB PHP Library](https://github.com/mongodb/mongo-php-library)

## С чего начать
### Composer

Установите этот пакет с помощью [Composer](https://getcomposer.org/).
Отредактируйте `require` в Вашем `composer.json`:
```json
{
    "require": {
        "grefon/mongo-model": "dev-main"
    }
}
```
и запустите `composer update`

**или**

запустите эту команду в командной строке:

```bash
composer require grefon/mongo-model
```

### Инициализация подключения
```php
// Загрузка composer
require __DIR__ . '/vendor/autoload.php';

use MongoDB\Client;
use MongoModel\MongoDB;
use MongoModel\ModelMongoDB;

// Инициализация подключения к MongoDB
/**
 * @param Client $client - MongoDB Client
 * @param string $base   - имя базы
 * @param bool   $debug  - дебаг
 */
MongoDB::init(new Client('mongodb://127.0.0.1/'), 'baseName', true);
```

Класс `MongoDB` с включенным дебагом будет записывать запросы и их статус в переменную `MongoDB::$trace`. 

### Ваша первая модель

Создайте коллекцию `users` в MongoDB.

Создайте новый PHP класс `User` расширяющий `ModelMongoDB`:

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
Каждый класс модели должен быть расширяющим `ModelMongoDB`. В нем должны быть заданы 4 основные статические переменные:
- `$collection` - в этой переменной будет храниться экземпляр \MongoDB\Collection
- `$collectionName` - имя коллекции в MongoDB
- `$primaryKey` - имя первичного ключа вместо `_id`
- `$fieldsModel` - массив полей модели

## $fieldsModel
В $fieldsModel описывается модель данных в виде ассоциативного массива. Ключ - это имя свойства класса, значение - массив с описанием параметров свойства.

***Первым значением в массиве параметров всегда должен быть указан тип данных.***

| Тип                      | Описание                                                                                                    |
|--------------------------|-------------------------------------------------------------------------------------------------------------|
| **string**               | строка                                                                                                      |
| **boolean** или **bool** | булевный true или false                                                                                     |
| **int**                  | число                                                                                                       |
| **float**                | число с плавающей точкой                                                                                    |
| **datetime**             | данные в PHP будут в виде строки (2022-10-03 11:27:15), а в MongoDB будет храниться временная метка ISODate |
| **array**                | массив                                                                                                      |
| **object**               | объект; в PHP это stdClass                                                                                  |
| **history**              | массив с историей изменений                                                                                 |

_При извлечении данных из MongoDB или сохранении будет произведена типизация данных._

Все последующие атрибуты в массиве параметров являются свободным, но некоторые из них зарезервированы:

| Значение           | Описание                                                                                                                                                                       |
|--------------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **required**       | Обязательное свойство модели. Если значение не указано, то при попытке сохранения данных сработает throw new Exception                                                         |
| **default**        | Значение по умолчанию. К примеру: `'rating' => ['int', 'default' => 100]`                                                                                                      |
| **timeCreate**     | Можно применять только на типе `datetime`. Устанавливает текущее время, если сохраняемый объект новый.                                                                         |
| **timeUpdate**     | Можно применять только на типе `datetime`. Устанавливает текущее время при сохранении объекта.                                                                                 |
| **onlyCreation**   | Создает переменную в документе при первом сохранении и больше не обновляет в ней данные. Полезно, когда данные в этом поле обновляются в стороннем процессе прямыми запросами. |
| **hidden**         | Скрывать поле на методе [getArray](#Метод getArray)                                                                                                                            |
| **history**        | Записывать изменения этого поля в историю. Подробнее про [историю](#History).                                                                                                  |
| **historyPrepare** | Указывает метод, которым необходимо производить проверку, изменилось ли поле.                                                                                                  |
| **historyValue**   | Указывает значение, которое необходимо писать в историю.                                                                                                                       |

## Создание нового объекта
```php
$user = new User(['name' => 'Will', 'surname' => 'Smith']);
$user->save();
```
или
```php
$user = new User();
$user->name = 'Will';
$user->surname = 'Smith';
$user->save();
```
или
```php
$user = new User();
$user->save(['name' => 'Will', 'surname' => 'Smith']);
```
или
```php
$user = User::new(['name' => 'Will', 'surname' => 'Smith'], false);
```
В каждом из этих примеров в MongoDB будет создан новый документ:
```json
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
Так же возможно создавать документы с собственным ID
```php
$userID = User::new(['userId' => 'user123', 'name' => 'Ban', 'rating' => 15]);
```
```json
{
    "_id" : "user123",
    "surname" : null,
    "name" : "Ban",
    "email" : null,
    "rating" : NumberInt(15),
    "ban" : false,
    "timeCreate" : ISODate("2022-10-02T13:38:51.000+0000"),
    "timeUpdate" : ISODate("2022-10-02T13:38:51.000+0000")
}
```
_Если ID будет длинной 24 символа, то будет произведена попытка преобразовать строку в ObjectId._

В статический метод `User::new(array $data, bool $returnID = true)` первым аргументом передается массив данных, вторым - нужно ли вернуть только ID созданного документа или весь экземпляр объекта; true = вернуть весь объект.

## Операции с объектом
### Загрузка
**По ID**
```php
$user = User::get('63399434089c8c26344ff2df');
```
**По свойствам**
```php
$user = User::get(['name' => 'Ban', 'rating' => ['$gte' => 10]]);
```
При загрузке по свойствам происходит проверка на существования искомых полей `name` и `rating` в `$fieldsModel`. 
Возможен поиск по внутренним свойствам если в ключе есть точка:
```php
$user = User::get(['phones.number' => 123456789);
```
Так же можно искать по синтаксису MongoDB, если ключ начинается с $:
```php
// Будет возвращен первый найденный документ с рейтингом меньше нуля или забаненный
$user = User::get(['$or' => [['rating' => ['$lt' => 0]], ['ban' => true]]);
```
Если документ не будет найден в MongoDB `User::get` вернет `null`.

### Метод getArray
`getArray($includeField = null, bool $skipHidden = true)`
```php
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
Метод `getArray` по умолчанию не заносит в результат поля, у которых в модели `$fieldsModel` указано `hidden`. Отключить это можно передав `$skipHidden` = `false`.

**$includeField**

Вы можете передать один атрибут или массив атрибутов, которые указаны для полей в `$fieldsModel`. Например:
```php
// Вернет только поля, у которых указано required в атрибутах
$user->getArray('required');

// Вернет только поля, у которых указано card и short в атрибутах
$user->getArray(['card', 'short']);
```
### Метод save
`save(array $data = null)`

Метод сохраняет текущий экземпляр объекта в документ MongoDB.
```php
if ($user = User::get('63399434089c8c26344ff2df')) {

    $user->rating = '20'; // строка будет преобразована в int
    $user->save(['email' => 'test@test.com']);
    
}
```
```json
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
_Если объект обновляется, а не создается впервые, то поля с атрибутом `onlyCreation` игнорируются._

### Метод save
`save(array $data = null)`

Метод сохраняет текущий экземпляр объекта в документ MongoDB.
```php
if ($user = User::get('63399434089c8c26344ff2df')) {

    $user->rating = '20'; // строка будет преобразована в int
    $user->save(['email' => 'test@test.com']);
    
}
```
```json
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
_Если объект обновляется, а не создается впервые, то поля с атрибутом `onlyCreation` игнорируются._

### Метод saveField
`saveField(string $field, $value = '__EMPTY__')`

Сохранить в MongoDB только одно свойство текущего объекта.

_Метод облегченного сохранения, он быстрее, так как не производится создание истории изменений._

```php
$user->email = 'test@test.com';
$user->rating = 50;
$user->saveField('rating');
```
или
```php
$user->email = 'test@test.com';
$user->saveField('rating', 50);
```
Несмотря на то, что Вы изменили `email` его новое значение не будет записано в MongoDB. В базу данных сохранится только значение `rating`.



### Метод saveFields
`saveFields($fields = null)`

Сохранить в MongoDB только определенные свойства текущего объекта.

_Метод облегченного сохранения, он быстрее, так как не производится создание истории изменений._

```php
$user->surname = 'TEST';
$user->email = 'test@test.com';
$user->rating = 50;

// Сохраняет только rating
$user->saveFields('rating');

// Сохраняет только rating и email
$user->saveFields(['rating', 'email']);
```
В собственных методах класса `User` Вы можете пополнять массив protected changedFields
```php

class User extends ModelMongoDB
{

    myMethod() {
        
        $this->rating = 100;
        $this->email = null

        $this->changedFields[] = 'rating';
        $this->changedFields[] = 'email';
        
        return $this;
    
    }

}

// Применяем myMethod и вызываем saveFields - будут сохранены rating и email
User::get('63399434089c8c26344ff2df')->myMethod()->saveFields();

```

