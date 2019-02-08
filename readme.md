# Schema

[![Build Status](https://travis-ci.org/northphp/schema.svg?branch=master)](https://travis-ci.org/northphp/schema)

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

## Types

* array
* bool/boolean
* callable
* closure
* function
* implements (implements:INTERFACE)
* int/integer
* iterable
* float
* string
* object
* resources
* type (type:CLASS)

Types can take arguments, for example for implements and type:

```php
[
    'person' => 'type:Person',
    'implements' => 'implements:Stringable',
]
```

## Custom types

```php
$schema->addType('custom_string', function($value) {
    return is_string($value);
});
```

When using extra arguments for types you just takes in more arguments.

```php
$schema->addType('type', function ($t, string $expected) {
    return get_class($t) === $expected;
});
```

More examples in the [`tests/SchemaTest.php`](tests/SchemaTest.php)

## License

MIT © [Fredrik Forsmo](https://github.com/frozzare)

