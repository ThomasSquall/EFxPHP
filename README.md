# Entity Framework for PHP/MySQL

This is an Entity Framework for PHP based on MySQL.


## Installation

Using composer is quite simple, just run the following command:
``` sh
$ composer require thomas-squall/efxphp
```

## Prerequisites

Before using this library you should make sure to have installed PHP7.1 or major.

## Usage

At first, you need to connect to the database.

The parameter to pass are:

- $host = The URL to the server where the database is running (localhost for local databases)
- $username = The username to login into MySQL
- $password = The password to login into MySQL
- $database = The database name to connect to  

For more information see the link: https://docs.mongodb.com/manual/reference/connection-string/

This is how you instantiate a new Adapter:

``` php
use EFxPHP\Adapter;

// Enstablish a connection.
$adapter = new Adapter();
$adapter->connect($host, $username, $password, $database);
```

Now you have to create a model

``` php
/**
 * @Model(name = "people")
 */
class person {
    /**
     * @Type(type = "varchar", length = 16)
     * @var string $name
     */
    public $name;

    /**
     * @Type(type = "int")
     * @var int $age
     */
    public $age;

    /**
     * @Type(type = "varchar", length = 10)
     * @Default(value = "italian")
     * @var string $nationality
     */
    public $nationality;
}
```

And register it

``` php
$db->registerModel(new person());
```

If you want to create/update your tables automatically based on your model, pass true as second parameter in the registerModel method

``` php
/*
 * Note that this will:
 * 1. Create the table if it does not exist
 * 2. Add new columns if you added new parameters to the model
 * 3. Remove unused columns if you deleted parameters from the model
 *
 * Specially for point 3, use it with care.
*/
$db->registerModel(new person(), true);
```

### Find

Once connected to the database we can simply query for the collection we want:

``` php
$items = $adapter->find('people');
```

You can also filter your query:

``` php
use MongDriver\Filter;

$filters =
[
    new Filter('age', 29, Filter::IS_EQUALS),
    new Filter('nationality', ['italian', 'spanish'], Filters::IS_IN_ARRAY)
];

$items = $adapter->find('people', $filters);
```

### Insert

If you want to insert an item you have simply to pass an instance of a registered model:

``` php
$item = new Person();
$item->name = 'Thomas';
$item->surname = 'Cocchiara');

$adapter->insert($item);
```

Hope you guys find this library useful.

Please share it and give me a feedback :)

Thomas