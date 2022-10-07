# MongoModel

[english](README.md)

MongoModel позволяет удобно работать с документами MongoDB, как с объектами.

Реализованы:
- [x] типизация свойств
- [x] обязательные свойства
- [x] значения по умолчанию
- [x] история изменений
- [x] операции с объектами

MongoModel работает с [MongoDB PHP Library](https://github.com/mongodb/mongo-php-library)

----------------------------------------
<!-- TOC -->
* [С чего начать](#с-чего-начать)
  * [Composer](#composer)
  * [Инициализация подключения](#инициализация-подключения)
  * [Ваша первая модель](#ваша-первая-модель)
* [$fieldsModel](#fieldsmodel)
* [Создание нового объекта](#создание-нового-объекта)
* [Операции с объектом](#операции-с-объектом)
  * [Загрузка](#загрузка)
    * [По ID](#по-id)
    * [По свойствам](#по-свойствам)
  * [Метод save](#метод-save)
  * [Метод saveField](#метод-savefield)
  * [Метод saveFields](#метод-savefields)
  * [Метод delete](#метод-delete)
  * [Метод getArray](#метод-getarray)
  * [Загрузка с кешированием](#загрузка-с-кешированием)
* [Операции с объектами](#операции-с-объектами)
  * [Метод itemsGet](#метод-itemsget)
    * [Данные для поиска](#данные-для-поиска)
    * [Возвращаемые поля](#возвращаемые-поля)
    * [Сортировка](#сортировка)
    * [Пагинация](#пагинация)
    * [Настройки запроса](#настройки-запроса)
  * [Метод itemsHas](#метод-itemshas)
  * [Метод itemsCount](#метод-itemscount)
  * [Метод itemsDelete](#метод-itemsdelete)
  * [Метод itemsNew](#метод-itemsnew)
* [История изменений](#история-изменений)
  * [Атрибут historyPrepare](#атрибут-historyprepare)
  * [Атрибут historyValue](#атрибут-historyvalue)
  * [Метод checkRecordHistory](#метод-checkrecordhistory)
* [В помощь разработчику](#В-помощь-разработчику)
  * [Триггеры](#триггеры)
  * [Снимки](#снимки)
  * [Метод getCollection](#метод-getcollection)
  * [Метод collectionInfo](#метод-collectioninfo)
<!-- TOC -->

----------------------------------------

## С чего начать
### Composer

Установите этот пакет с помощью [Composer](https://getcomposer.org/).
Отредактируйте `require` в Вашем `composer.json`:
```JSON
{
    "require": {
        "grefon/mongo-model": "*"
    }
}
```
и запустите `composer update`

**или**

запустите эту команду в командной строке:

```BASH
composer require grefon/mongo-model
```

### Инициализация подключения
```PHP
// Загрузка composer
require __DIR__ . '/vendor/autoload.php';

use MongoDB\Client;
use MongoModel\MongoDB;

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
Каждый класс модели должен быть расширяющим `ModelMongoDB`. В нем должны быть заданы 4 основные статические переменные:
- `$collection` - в этой переменной будет храниться экземпляр \MongoDB\Collection
- `$collectionName` - имя коллекции в MongoDB
- `$primaryKey` - имя первичного ключа вместо `_id`
- `$fieldsModel` - массив полей модели (свойств объекта)

## $fieldsModel
В $fieldsModel описывается модель данных в виде ассоциативного массива. Ключ - это имя свойства объекта, значение - массив атрибутов свойства.

***Первым значением в массиве атрибутов всегда должен быть указан тип данных.***

| Тип                      | Описание                                                                                                    |
|--------------------------|-------------------------------------------------------------------------------------------------------------|
| **string**               | строка                                                                                                      |
| **boolean** или **bool** | булевный true или false                                                                                     |
| **int**                  | число                                                                                                       |
| **float**                | число с плавающей точкой                                                                                    |
| **datetime**             | данные в PHP будут в виде строки (2022-10-03 11:27:15), а в MongoDB будет храниться временная метка ISODate |
| **array**                | массив                                                                                                      |
| **object**               | объект; в PHP это stdClass                                                                                  |
| **history**              | массив с [историей изменений](#история-изменений)                                                           |

_При извлечении данных из MongoDB или сохранении будет произведена типизация._

Все последующие атрибуты в массиве являются свободными, но некоторые из них зарезервированы:

| Атрибут            | Описание                                                                                                                                                                                   |
|--------------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **required**       | Обязательное свойство объекта. Если значение свойства не указано, то при попытке сохранения данных сработает throw new Exception                                                           |
| **default**        | Значение по умолчанию. К примеру: `'rating' => ['int', 'default' => 100]`                                                                                                                  |
| **timeCreate**     | Можно применять только на типе `datetime`. Устанавливает текущее время, если сохраняемый объект новый.                                                                                     |
| **timeUpdate**     | Можно применять только на типе `datetime`. Устанавливает текущее время при каждом сохранении объекта методом [save()](#метод-save).                                                        |
| **onlyCreation**   | Создает переменную в документе MongoDB при первом сохранении и больше не обновляет в ней данные. Полезно, когда данные в этом свойстве обновляются в стороннем процессе прямыми запросами. |
| **hidden**         | Скрывать свойство на методе [getArray](#метод-getarray)                                                                                                                                    |
| **history**        | Записывать изменения этого свойства в [историю](#история-изменений).                                                                                                                       |
| **historyPrepare** | Указывает метод, которым необходимо производить проверку, изменилось ли свойство.                                                                                                          |
| **historyValue**   | Указывает метод, который возвращает стандартизированное значение свойства для истории.                                                                                                     |

## Создание нового объекта

Пример: [examples/new.php](examples/new.php)
```PHP
$user = new User(['name' => 'Will', 'surname' => 'Smith']);
$user->save();
```
или
```PHP
$user = new User;
$user->name = 'Will';
$user->surname = 'Smith';
$user->save();
```
или
```PHP
$user = new User;
$user->save(['name' => 'Will', 'surname' => 'Smith']);
```
или
```PHP
$user = User::new(['name' => 'Will', 'surname' => 'Smith'], false);
```
В каждом из этих примеров в MongoDB будет создан новый документ:
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
Так же возможно создавать документы с собственным ID:
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
_Если ID будет длинной 24 символа, то будет произведена попытка преобразовать строку в [ObjectId](https://www.mongodb.com/docs/manual/reference/method/ObjectId/)._

В статический метод `User::new(array $data, bool $returnID = true)` первым аргументом передается массив данных, вторым - нужно ли вернуть только ID созданного документа или весь экземпляр объекта; `$returnID = false` - вернуть весь объект.

----------------------------------------

## Операции с объектом
### Загрузка
`YourModelClass::get($data)`

Пример: [examples/get.php](examples/get.php)
#### По ID
```PHP
$user = User::get('63399434089c8c26344ff2df');
```
#### По свойствам
```PHP
$user = User::get(['name' => 'Bob', 'rating' => ['$gte' => 10]]);
```
При загрузке по свойствам происходит проверка на существования искомых полей `name` и `rating` в `$fieldsModel`. 
Возможен поиск по внутренним свойствам если в ключе есть точка:
```PHP
$user = User::get(['phones.number' => 123456789);
```
Так же можно искать по синтаксису MongoDB, если ключ начинается с $:
```PHP
// Будет возвращен первый найденный документ с рейтингом меньше нуля или забаненный
$user = User::get(['$or' => [['rating' => ['$lt' => 0]], ['ban' => true]]]);
```
Если документ не будет найден в MongoDB `User::get` вернет `null`.

### Метод save
`save(array $data = null)`

Пример: [examples/save.php](examples/save.php)

Метод сохраняет текущий экземпляр объекта в документ MongoDB. В базу данных отправляются только измененные свойства.
```PHP
if ($user = User::get('63399434089c8c26344ff2df')) {

    $user->rating = '20'; // строка будет преобразована в int
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
_Если объект обновляется, а не создается впервые, то свойства с атрибутом `onlyCreation` игнорируются, а с атрибутом `history` проверяются на изменение и записываются в историю._

### Метод saveField
`saveField(string $field, $value = '__EMPTY__')`

Пример: [examples/save.php](examples/save.php)

Сохранить в MongoDB только одно свойство текущего объекта.

_Метод облегченного сохранения. Не создается история изменений. Свойство timeUpdate не обновляется автоматически._

```PHP
$user->email = 'test@test.com';
$user->rating = 50;
$user->saveField('rating');
```
или
```PHP
$user->email = 'test@test.com';
$user->saveField('rating', 50);
```
Несмотря на то, что Вы изменили `email` его новое значение не будет записано в MongoDB. В базу данных сохранится только значение `rating`.

### Метод saveFields
`saveFields($fields = null)`

Пример: [examples/save.php](examples/save.php)

Сохранить в MongoDB только определенные свойства текущего объекта.

_Метод облегченного сохранения. Не создается история изменений. Свойство timeUpdate не обновляется автоматически._

```PHP
$user->surname = 'TEST';
$user->email = 'test@test.com';
$user->rating = 50;

// Сохраняет только rating
$user->saveFields('rating');

// Сохраняет только rating и email
$user->saveFields(['rating', 'email']);
```
В собственных методах класса `User` Вы можете пополнять массив protected changedFields:
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
    
        // Изменяем email
        $user->changeEmail($_POST['email']);
    
    }
    
    // Сбрасываем рейтинг и сохраняем в MongoDB изменившиеся свойства объекта
    $user->resetRating()
         ->saveFields();

}
```

### Метод delete
`delete()`

Пример: [examples/delete.php](examples/delete.php)

Удаляет документ из MongoDB.
```PHP
if ($user = User::get('63399434089c8c26344ff2df')) {

    $user->delete();

}
```

### Метод getArray
`getArray($includeField = null, bool $skipHidden = true)`

Пример: [examples/getArray.php](examples/getArray.php)
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
Метод `getArray` по умолчанию не заносит в результат свойства, у которых в модели `$fieldsModel` указано `hidden`. Отключить это можно передав $skipHidden = false.

**$includeField**

Вы можете передать один атрибут или массив атрибутов, которые указаны для свойств в `$fieldsModel`. Например:
```PHP
// Вернет только свойства, у которых указано required в атрибутах
$user->getArray('required');

// Вернет только свойства, у которых указано card и short в атрибутах
$user->getArray(['card', 'short']);
```

### Загрузка с кешированием
`YourModelClass::getFromCache($data)`

Пример: [examples/getFromCache.php](examples/getFromCache.php)

При загрузке с кешированием экземпляр объекта хранится в памяти и при повторной загрузке не происходит обращения к MongoDB. Как и в методе [get()](#загрузка) можно загружать по ID или свойствам.

Полезно при работе с объектом в разных участках кода.

```PHP
$userId = '63399434089c8c26344ff2df';
$email = $_POST['email'] ?? null;
$name = $_POST['name'] ?? null;
// ...................................

// В первом участке кода
if ($email) {

    $user = User::getFromCache($userId);
    $user->email = $email;

}

// ...................................

// Во втором участке кода
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

## Операции с объектами
### Метод itemsGet
`YourModelClass::itemsGet($data = null, $fields = null, $orderBy = null, $limited = null, array $settings = [])`

Пример: [examples/itemsGet.php](examples/itemsGet.php)

Найти документы в MongoDB и вернуть массив экземпляров объектов или их определенные свойства.

#### Данные для поиска
`$data`

- **null** - поиск без условий по всем документам
- **string** или **int** - поиск по ID
- **array** - массив ID или ассоциативный массив свойств
```PHP
// Вернет массив всех документов
User::itemsGet(); 

// Вернет массив с документами, у которых _id = 26
User::itemsGet(26); 

// Вернет массив с документами, у которых _id = user_12
User::itemsGet('user_12'); 

// Вернет массив с документами, у которых _id = ObjectId("63399434089c8c26344ff2df")
User::itemsGet('63399434089c8c26344ff2df'); 

// Вернет массив с документами, у которых _id = user_12 или ObjectId("63399434089c8c26344ff2df")
User::itemsGet(['user_12', '63399434089c8c26344ff2df']); 

// Вернет массив с документами, у которых name = Will и surname = Smith
User::itemsGet(['name' => 'Will', 'surname' => 'Smith']);
```
Поиск работает так же, как на [методе get()](#загрузка), но в результат всегда возвращается массив со всеми найденными документами.

#### Возвращаемые поля
`$fields`

Если **$fields = null**, то в результат `itemsGet` будет возвращен массив **экземпляров объектов**. С каждым экземпляром можно выполнять такие-же действия, как если бы его обрабатывали после [загрузки](#загрузка).
```PHP
foreach (User::itemsGet(['rating' => ['$lt' => 10]], null) as $user) {

    $user->rating += 5;
    $user->saveField('rating');

}
```
Если в **$fields** передать свойство строкой, то в результат `itemsGet` будет возвращен ассоциативный массив с указанным свойством.
```PHP
foreach (User::itemsGet(['name' => 'Will'], 'rating') as $userId => $rating) {

}
```

Если в **$fields** передать массив свойств, то в результат `itemsGet` будет возвращен ассоциативный массив, где ключ будет **_id**, а значение **stdClass** с указанными полями.
```PHP
foreach (User::itemsGet(['name' => 'Will'], ['name', 'surname', 'rating']) as $userId => $item) {

    echo $item->name . ' ' . $item->surname . ' has a rating ' . $item->rating;
    // Will Smith has a rating 10
    // Will Duk has a rating 7
    // ..........

}
```

#### Сортировка
`$orderBy`

Указать поле, по которому нужно сортировать документы при поиске.
```PHP
// По возрастанию
User::itemsGet(['name' => 'Will'], null, 'rating');

// По убыванию
User::itemsGet(['name' => 'Will'], null, ['rating', 'DESC']);
```

#### Пагинация
`$limited`

Указать количество искомых документов и отступ.
```PHP
// Вернет первых 10 найденных документов
User::itemsGet(null, null, null, 10);

// Вернет с 21 по 30 найденный документ
User::itemsGet(null, null, null, [20, 10]);
```

#### Настройки запроса
`$settings`

Настройки для поиска в MongoDB. Например, можно подсказать индекс:
```PHP
User::itemsGet(['name' => 'Will'], null, 'rating', 10, ['hint' => 'index_name_rating']);
```

Так же в настройках Вы можете передавать более сложные условия для сортировки по нескольким полям.

### Метод itemsHas
`YourModelClass::itemsHas($data = null)`

Пример: [examples/itemsHas.php](examples/itemsHas.php)

Проверить, существуют ли документы в коллекции по заданным условиям.

В **$data** передаются [данные для поиска](#данные-для-поиска), как в методе [itemsGet](#метод-itemsget).

Возвращается **true** или **false**.

```PHP
if (User::itemsHas(['email' => 'mail@test.com'])) {
    
    die('Email busy');

}
```

### Метод itemsCount
`YourModelClass::itemsCount($data = null, array $settings = [])`

Пример: [examples/itemsCount.php](examples/itemsCount.php)

Возвращает количество документов соответствующих запросу.

В **$data** передаются [данные для поиска](#данные-для-поиска), как в методе [itemsGet](#метод-itemsget).

В **$settings** - [настройки запроса](#настройки-запроса)

```PHP
// Сколько документов имеют 'rating' больше 100
echo User::itemsCount(['rating' => ['$gt' => 100]]);

// Подсказываем индекс и считаем максимум до 20
echo User::itemsCount(['rating' => ['$gt' => 100]], ['hint' => 'my_index', 'limit' => 20]);
```

### Метод itemsDelete
`YourModelClass::itemsDelete($data = null)`

Пример: [examples/itemsDelete.php](examples/itemsDelete.php)

Удаляет из коллекции все документы, соответствующие запросу.

В **$data** передаются [данные для поиска](#данные-для-поиска), как в методе [itemsGet](#метод-itemsget).

Возвращает количество удаленных документов.

```PHP
// Удалить все документы со свойством 'rating' меньше нуля
echo User::itemsDelete(['rating' => ['$lt' => 0]]);
```

### Метод itemsNew
`YourModelClass::itemsNew(array $items, bool $returnID = true)`

Пример: [examples/itemsNew.php](examples/itemsNew.php)

Создает несколько объектов и делает вставку `insertMany` в MongoDB.

Метод аналогичен методу `YourModelClass::new()` с той лишь разницей, что первым аргументом передается массив массивов данных.

Возвращает массив ID созданных объектов или массив объектов (если $returnID = false).

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

## История изменений

При сохранении объекта методом [save()](#метод-save) происходит создание истории изменений. История записывается в свойство объекта с типом `history`. Проверку на изменение проходят те свойства, у которых в атрибутах указано `history`.

```PHP
class User extends ModelMongoDB
{

    // ..............................

    static public $fieldsModel = [
        'userId'  => ['string'],
        'surname' => ['string'],                                          // НЕ отслеживаем изменения
        'name'    => ['string', 'required'],                              // НЕ отслеживаем изменения
        'email'   => ['string', 'history'],                               // Отслеживаем изменения
        'phones'  => ['array', 'history',                                 // Отслеживаем изменения
                      'historyPrepare' => 'historyPrepareArray:number', 
                      'historyValue' => 'historyValuePhones'
                     ],
        'history' => ['history']                                          // Массив истории
    ];

}
```

Если свойства объекта с атрибутом `history` изменились, то в массив историй будет добавлена запись с датой изменения и объектом `changes`, в котором будут перечислены все изменившиеся свойства. Кажодое свойство - это массив с двумя элементами array(было, стало).

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

### Атрибут historyPrepare
В атрибуте `historyPrepare` Вы можете указывать свой метод, которым будет производится сравнение `было` === `стало`.

Помимо сравнения простых типов данных (string, int, float, bool) существуют 2 метода:

- **historyPrepareArray** для сравнения массивов
- **historyPrepareObject** для сравнения объектов

В уже заготовленные `historyPrepareArray` и `historyPrepareObject` методы и в Ваш собственный метод из атрибута `historyPrepare` будет передаваться два значения:
1) значение, которое нужно как-то стандартизировать
2) необязательная переменная из значения атрибута после двоеточия. Например, будет передан number в `'historyPrepare' => 'historyPrepareArray:number'`

Давайте представим, что у пользователя есть свойство phones - это массив объектов stdClass с телефонами:

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

Из указанного примера `'historyPrepare' => 'historyPrepareArray:number'` массив телефонов будет сравниваться по полю `number`. При сохранении метод `historyPrepareArray` преобразует данные для сравнения в строку `[127774477,1555331188]`. Такое же преобразование выполнится с данными из [снимка](#снимки) после последнего сохранения. Далее сравнение и запись в историю, если есть изменения.


### Атрибут historyValue

Указывает на Ваш **собственный** метод, который обработает и вернет значение для записи в историю.

Смотрите пример: [examples/User.php](examples/User.php#L123)

### Метод checkRecordHistory
`checkRecordHistory(array $changes)`

По умолчанию возвращает **true**; Если вернет **false** - история изменений не добавится.

Вызывается во время сохранения, когда уже составлена история изменений.

В **$changes** передаются текущие изменения.

Метод полезен, если Вам нужно вести историю изменений, но при каких-то обстоятельствах иногда ее не записывать.

Например, Вы можете проверять размер текущей истории и не писать больше 100 записей:

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

## В помощь разработчику
### Триггеры
| Метод           | Описание                                                                                                             |
|-----------------|----------------------------------------------------------------------------------------------------------------------|
| **afterCreate** | Вызывается после инициализации класса в __construct.                                                                 |
| **afterLoad**   | Вызывается после загрузки данных из MongoDB.                                                                         |
| **preSave**     | Вызывается перед сохранением.                                                                                        |
| **afterSave**   | Вызывается после сохранения. В метод передается массив имен свойств, которые изменились и были отправлены в MongoDB. |
```PHP
class User extends ModelMongoDB
{

    // ..............................

    function afterLoad() {
        
        // Если рейтинг в MongoDB был меньше 500
        if (empty($this->rating) or $this->rating < 500) {
        
            $this->rating = 500;
        
        }
    
    }
    
    function preSave() {
        
        // Если после всех манипуляций рейтинг стал меньше -100
        if ($this->rating < -100) {
        
            $this->ban = true;
        
        }
    
    }

}
```

### Снимки
`getSnapshot($field)` и `getSnapshotUpdate($field)`

После загрузки данных из MongoDB создается снимок текущего состояния. В процессе Вам может понадобиться узнать, какие данные были в базе данных или после последнего сохранения. 
```PHP
if ($user = User::get('63399434089c8c26344ff2df')) {

    $user->name = 'Jack';
    echo $user->name; // Выведет Jack
    echo $user->getSnapshot('name'); // Выведет Will
    echo $user->getSnapshotUpdate('name'); // Выведет Will
    
    // Сохраняем имя Jack
    $user->saveFields('name');
    
    $user->name = 'Bob';
    echo $user->name; // Выведет Bob
    echo $user->getSnapshot('name'); // Выведет Will
    echo $user->getSnapshotUpdate('name'); // Выведет Jack
    
    // Сохраняем имя Dan
    $user->save(['name' => 'Dan']);
    
    $user->name = 'Test';
    echo $user->name; // Выведет Test
    echo $user->getSnapshot('name'); // Выведет Will
    echo $user->getSnapshotUpdate('name'); // Выведет Dan   
    
}
```

### Метод getCollection
`YourModelClass::getCollection()`

Возвращает коллекцию MongoDB. Можно использовать для прямых запросов.
```PHP
use MongoModel\MongoDB;

// Баним всех пользователей с рейтингом меньше -100
MongoDB::execute(User::getCollection(), 'updateMany', [
    'rating' => ['$lt' => -100]
], [
    '$set' => [
        'ban' => true
    ]
]);
```

### Метод collectionInfo
`MongoDB::collectionInfo(string $collection)`

Пример: [examples/collectionInfo.php](examples/collectionInfo.php)

Возвращает информацию о коллекции.
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
