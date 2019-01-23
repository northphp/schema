<?php

use North\Schema\Schema;
use PHPUnit\Framework\TestCase;

interface Stringable
{
    public function string();
}

class Person implements Stringable
{
    public $name = '';

    public function __construct($o)
    {
        foreach ($o as $k => $v) {
            $this->$k = $v;
        }
    }

    public function string()
    {
        return $this->name;
    }
}

class SchemaTest extends TestCase
{
    public function testSchema()
    {
        $schema = new Schema([
            'name' => 'string',
            'age' => 'integer',
            'item' => [
                'id' => 'int',
                'name' => 'is_string',
            ],
            'is_admin' => 'boolean?',
            'md5' => function ($value) {
                return is_string($value) && preg_match('/^[a-f0-9]{32}$/', $value);
            },
            'names' => ['string'],
            'objs' => [
                [
                    'name' => 'string',
                    'age' => 'int',
                ],
            ],
            'func' => 'function',
            'closure' => 'closure',
            'person' => 'type:Person',
            'implements' => 'implements:Stringable',
            'defaults' => [
                'name' => 'string',
                'age' => 'integer',
            ],
        ], [
            'defaults' => [
                'name' => 'default',
                'age' => 27,
            ],
        ]);

        $schema->addType('exact', function ($t, $v) {
            return $t === $v;
        });

        $schema->addSchema([
            'item' => [
                'exact' => 'exact:test'
            ],
        ]);

        $this->assertTrue($schema->valid([
            'name' => 'jimmy',
            'age' => 24,
            'item' => [
                'id' => 2,
                'name' => 'Test',
                'exact' => 'test',
            ],
            'md5' => '5d41402abc4b2a76b9719d911017c592',
            'names' => ['foo', 'bar', 'baz'],
            'objs' => [
                [
                    'name' => 'jimmy',
                    'age' => 24,
                ],
            ],
            'func' => 'is_string',
            'closure' => function ($x) {
            },
            'person' => new Person(['name' => 'Fredrik']),
            'implements' => new Person(['name' => 'Fredrik']),
        ]));
    }

    public function testDefaultSchema()
    {
        $expected = [
            'name' => 'jimmy',
            'age' => 24,
        ];

        $schema = new Schema([
            'name' => 'string',
            'age' => 'integer',
        ], $expected);

        $this->assertSame($expected, $schema->resolve([
            'name' => 'jimmy',
        ]));
    }

    public function testJsonSchema()
    {
        $schema = new Schema(__DIR__ . '/testdata/user.json');

        $this->assertTrue($schema->valid([
            'name' => 'jimmy',
            'age' => 24,
        ]));
    }

    public function testEmptySchema()
    {
        $schema = new Schema;

        $this->assertTrue($schema->valid([]));
    }

    public function testClassSchema()
    {
        $schema = new Schema([
            'name' => 'string',
        ]);

        $class = new Person(['name' => 'Fredrik']);

        $this->assertTrue($schema->valid($class));
    }
}
