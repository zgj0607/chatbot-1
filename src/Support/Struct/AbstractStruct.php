<?php

/**
 * This file is part of CommuneChatbot.
 *
 * @link     https://github.com/thirdgerb/chatbot
 * @document https://github.com/thirdgerb/chatbot/blob/master/README.md
 * @contact  <thirdgerb@gmail.com>
 * @license  https://github.com/thirdgerb/chatbot/blob/master/LICENSE
 */

namespace Commune\Support\Struct;

use Commune\Support\Arr\ArrayAbleToJson;

/**
 * @author thirdgerb <thirdgerb@gmail.com>
 */
abstract class AbstractStruct implements Struct, \Serializable
{
    use ArrayAbleToJson;

    protected static $validators = [];

    /**
     * @var array
     */
    protected $_data;

    public function __construct(array $data = [])
    {

        $stub = static::stub();
        $data = $data + $stub;

        $error = static::validate($data);
        if (isset($error)) {
            throw new InvalidStructException("struct validate data fail: $error");
        }

        $this->_data = $this->recursiveConstruct($data);
    }

    /**
     * @param array $data
     * @return static
     */
    public static function create(array $data = []): Struct
    {
        return new static($data);
    }

    public function __get($name)
    {
        return $this->_data[$name] ?? null;
    }

    public function __set($name, $value)
    {
        $data = $this->_data;
        $data[$name] = $value;
        $this->_data = $this->recursiveConstruct($data);
    }

    public function __isset($name)
    {
        return isset($this->_data[$name]);
    }

    private function recursiveConstruct(array $data) : array
    {
        $relations = static::relations();
        if (empty($relations)) {
            return $data;
        }

        foreach ($relations as $field => $structType) {
            $isArray = $this->isArrayFieldName($field);
            $field = $isArray ? $this->fieldWithOutArrMark($field) : $field;

            // 不能不存在
            if (!array_key_exists($field, $data)) {
                throw new InvalidStructException("relation field $field is missing");
            }

            if (!$isArray) {
                $data[$field] = $this->buildRelatedStruct(
                    $structType,
                    $data[$field]
                );
            } else {
                foreach ($data[$field] as $key => $value) {
                    $data[$field][$key] = $this->buildRelatedStruct(
                        $structType,
                        $value
                    );
                }
            }
        }

        return $data;
    }

    public static function isRelation(string $fieldName): bool
    {
        $relations = static::relations();
        if (empty($relations)) {
            return false;
        }

        return array_key_exists($fieldName, $relations)
            || array_key_exists($fieldName . '[]', $relations);
    }

    public static function isListRelation(string $fieldName): bool
    {
        $relations = static::relations();
        if (empty($relations)) {
            return false;
        }
        return array_key_exists($fieldName . '[]', $relations);
    }


    public static function getRelationNames() : array
    {
        $names = [];
        foreach(static::relations() as $relation) {
            $names[] = self::isArrayFieldName($relation)
                ? self::fieldWithOutArrMark($relation)
                : $relation;
        }
        return $names;
    }

    public static function getRelationClass(string $fieldName): ? string
    {
        $relations = static::relations();

        return $relations[$fieldName] ?? $relations[$fieldName . '[]'] ?? null;
    }


    private static function fieldWithOutArrMark(string $field) : string
    {
        return substr($field, 0, -2);
    }

    private static function isArrayFieldName($field) : bool
    {
        return substr($field, -2, 2) === '[]';
    }


    private function buildRelatedStruct(string $type, $data) : Struct
    {
        if (is_object($data) && is_a($data, $type, TRUE)) {
            return $data;
        }

        if (!is_array($data)) {
            throw new InvalidStructException("relation value for type $type must be array");
        }
        return call_user_func(
            [$type, 'create'],
            $data
        );
    }

    public function toArray(): array
    {
        $data = $this->_data;

        $relations = static::relations();
        if (empty($relations)) {
            return $data;
        }

        foreach ($relations as $field => $structType) {
            $isArray = $this->isArrayFieldName($field);
            $field = $isArray ? $this->fieldWithOutArrMark($field) : $field;

            if (!isset($data[$field])) {
                continue;
            }

            if ($isArray) {
                /**
                 * @var Struct $value
                 */
                foreach($data[$field] as $key => $value) {
                    $data[$field][$key] = $value->toArray();
                }

            } else {
                $data[$field] = $data[$field]->toArray();
            }
        }
        return $data;
    }


    public function __destruct()
    {
        // 防止不回收垃圾.
        $this->_data = [];
    }

    public function serialize()
    {
        return $this->toJson();
    }

    public function unserialize($serialized)
    {
        return static::create(json_decode($serialized, true));
    }


}