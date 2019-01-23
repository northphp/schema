<?php

namespace North\Schema;

use Closure;
use Exception;

class Schema
{
    /**
     * The schema.
     *
     * @var array
     */
    protected $schema = [];

    /**
     * Default data.
     *
     * @var array
     */
    protected $defaults = [];

    /**
     * Schema types.
     *
     * @var array
     */
    protected $types = [];

    /**
     * Create a new schema.
     *
     * @param  mixed $schema
     * @param  array $defaults
     *
     * @throws Exception if schema is invalid.
     */
    public function __construct($schema = [], array $defaults = [])
    {
        if (is_array($schema)) {
            $this->schema = $schema;
            $this->defaults = $defaults;
        } else {
            $this->readSchema($schema);
        }

        $this->types = [
            'array' => 'is_array',
            'bool' => 'is_bool',
            'boolean' => 'is_bool',
            'callable' => 'is_callable',
            'closure' => function ($t) {
                return is_object($t) && ($t instanceof Closure);
            },
            'function' => 'is_callable',
            'implements' => function ($t, string $interface) {
                return in_array($interface, class_implements($t), true);
            },
            'int' => 'is_int',
            'integer' => 'is_int',
            'iterable' => 'is_iterable',
            'float' => 'is_float',
            'string' => 'is_string',
            'object' => 'is_object',
            'resource' => 'is_resource',
            'type' => function ($t, string $expected) {
                return get_class($t) === $expected;
            },
        ];
    }

    /**
     * Add extra schema.
     *
     * @param  mixed $schema
     *
     * @throws Exception if schema is invalid.
     */
    public function addSchema($schema)
    {
        $this->readSchema($schema);
    }

    /**
     * Add type validation function.
     *
     * @param  string   $type
     * @param  callback $validate
     */
    public function addType(string $type, $validate)
    {
        if (is_callable($validate)) {
            $this->types[$type] = $validate;
        }
    }

    /**
     * Determine if given array is an associative array.
     *
     * @param  array $arr
     *
     * @return bool
     */
    protected function isAssocArray(array $arr)
    {
        return count(array_filter(array_keys($arr), 'is_string')) > 0;
    }

    /**
     * Read schema and default data from array or json file.
     *
     * @param  mixed $schema
     *
     * @throws Exception if schema is invalid.
     */
    protected function readSchema($schema)
    {
        if (is_string($schema) && file_exists($schema)) {
            $content = file_get_contents($schema);
            $schema = json_decode($content, true);
        }

        if (!is_array($schema)) {
            throw new Exception('Invalid file content, expected array got ' . gettype($file));
        }

        if (isset($schema['schema'])) {
            $this->schema = array_replace_recursive($this->schema, $schema['schema']);

            if (isset($schema['default'])) {
                $this->defaults = array_replace_recursive($this->defaults, $schema['default']);
            }
        } else {
            $this->schema = array_replace_recursive($this->schema, $schema);
        }
    }

    /**
     * Resolve input data and merge with default data.
     *
     * @param  array|object  $arr
     *
     * @throws Exception if missing schema type, type function or array value.
     * @throws Exception if input value is not an array.
     *
     * @return array
     */
    public function resolve($arr)
    {
        if (is_object($arr)) {
            $arr = (array) $arr;
        }

        if (!is_array($arr)) {
            throw new Exception('Input value is not an array, got ' . gettype($arr));
        }

        try {
            if ($this->valid($arr)) {
                return array_replace_recursive($this->defaults, $arr);
            }
        } catch (Exception $e) {
            throw $e;
        }

        throw new Exception('Schema and input value don\'t match');
    }

    /**
     * Determine if given array is valid.
     *
     * @param  array|object $arr
     *
     * @throws Exception if missing schema type, type function or array value.
     * @throws Exception if input value is not an array.
     *
     * @return bool
     */
    public function valid($arr)
    {
        if (is_object($arr)) {
            $arr = (array) $arr;
        }

        if (!is_array($arr)) {
            throw new Exception('Input value is not an array, got ' . gettype($arr));
        }

        return $this->validate($this->schema, $this->defaults, $arr);
    }

    /**
     * Validate schema against array object.
     *
     * @param  array  $schema
     * @param  array  $defaults
     * @param  array  $obj
     * @param  string $parentKey
     *
     * @throws Exception if missing schema type, type function or array value.
     *
     * @return bool
     */
    protected function validate(array $schema, array $defaults, array $obj, string $parentKey = '')
    {
        $keys = array_merge(array_keys($schema), array_keys($obj));

        foreach ($keys as $key) {
            $childKey = (empty($parentKey) ? '' : $parentKey . '.' ) . $key;

            if (!isset($schema[$key])) {
                throw new Exception('Missing schema type for ' . $childKey);
            }

            $optional = false;
            $type = $schema[$key];
            if (is_string($type)) {
                $optional = $type[strlen($type) -1] === '?';
                $type = str_replace('?', '', $type);
            }

            if (empty($obj[$key]) && isset($defaults[$key])) {
                $obj[$key] = $defaults[$key];
            }

            if (!isset($obj[$key])) {
                if ($optional) {
                    continue;
                }

                throw new Exception('Missing array value for ' . $childKey);
            }

            $value = $obj[$key];

            if (is_array($value)) {
                $def = isset($defaults[$key]) ? $defaults[$key] : [];

                if ($this->isAssocArray($value)) {
                    if (!$this->validate($schema[$key], $def, $value, $childKey)) {
                        return false;
                    }
                } else {
                    foreach ($value as $i => $v) {
                        $d = isset($def[$i]) ? $def[$i] : [];
                        if (!$this->validate($type, $d, [$v], $childKey)) {
                            return false;
                        }
                    }
                }

                continue;
            }

            $args = [$value];
            $func = null;
            if (is_callable($type)) {
                $func = $type;
            } else {
                $t = explode(':', $type);

                $type = $t[0];
                $vals = array_map(function ($val) {
                    return explode(',', $val);
                }, array_splice($t, 1));
                foreach ($vals as $val) {
                    $args = array_merge($args, $val);
                }

                if (!isset($this->types[$type])) {
                    throw new Exception('Missing type validation function for ' . $type);
                }

                $func = $this->types[$type];
            }

            if (!call_user_func_array($func, $args)) {
                return false;
            }
        }

        return true;
    }
}
