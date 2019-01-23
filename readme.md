# Schema

[![Build Status](https://travis-ci.org/northphp/schema.svg?branch=master)](https://travis-ci.org/northphp/schema)

> Work in progress!

A simple schema validation package.

## Installation

```
composer require north/schema
```

## Examples

Basic validation of an array.
```php
use North\Schema\Schema;

$schema = new Schema([
    'name' => 'string',
    'age' => 'int',
]);

// validate input against schema.
// returns bool or throws exception.
$schema->valid([
    'name' => 'fredrik',
    'age' => 27,
]);
```

Class validation converts class to array and validates public properties.
```php
use North\Schema\Schema;

class User
{
    public $name = '';

    public function __construct($o)
    {
        foreach ($o as $k => $v) {
            $this->$k = $v;
        }
    }
}

$schema = new Schema([
    'name' => 'string',
]);

// validate input against schema.
// returns bool or throws exception.
$schema->valid(
    new User([
        'name' => 'fredrik',
    ]),
);
```

More examples in the [`tests/SchemaTest.php`](tests/SchemaTest.php)

## License

MIT © [Fredrik Forsmo](https://github.com/frozzare)

