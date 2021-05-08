<?php

declare(strict_types=1);

namespace chaser\http\message;

use InvalidArgumentException;

/**
 * 参数
 *
 * @package chaser\http\message
 */
class Argument
{
    /**
     * null
     */
    public const NULL = 0b1;

    /**
     * 布尔类型
     */
    public const BOOL = 0b10;

    /**
     * 整型
     */
    public const INT = 0b100;

    /**
     * 浮点型
     */
    public const FLOAT = 0b1000;

    /**
     * 字符串型
     */
    public const STRING = 0b10000;

    /**
     * 数组类型
     */
    public const ARRAY = 0b100000;

    /**
     * 对象类型
     */
    public const OBJECT = 0b1000000;

    /**
     * 可调用类型
     */
    public const CALLABLE = 0b10000000;

    /**
     * 资源类型
     */
    public const RESOURCE = 0b100000000;

    /**
     * 类型说明
     *
     * @var string[]
     */
    private static array $declarations = [
        self::NULL => 'null',
        self::BOOL => 'a boolean',
        self::INT => 'an integer',
        self::FLOAT => 'a decimal',
        self::STRING => 'a string',
        self::ARRAY => 'an array',
        self::OBJECT => 'an object',
        self::CALLABLE => 'a callable',
        self::RESOURCE => 'a resource',
    ];

    /**
     * 参数类型无效性验证
     *
     * @param mixed $value
     * @param int $type
     * @param array $declarations
     * @return bool
     */
    public static function invalid($value, int $type, array &$declarations = []): bool
    {
        if ($type & self::NULL) {
            if (is_null($value)) {
                return false;
            }
            $declarations[] = self::$declarations[self::NULL];
        }

        if ($type & self::BOOL) {
            if (is_bool($value)) {
                return false;
            }
            $declarations[] = self::$declarations[self::NULL];
        }

        if ($type & self::INT) {
            if (is_int($value)) {
                return false;
            }
            $declarations[] = self::$declarations[self::INT];
        }

        if ($type & self::FLOAT) {
            if (is_float($value)) {
                return false;
            }
            $declarations[] = self::$declarations[self::FLOAT];
        }

        if ($type & self::STRING) {
            if (is_string($value)) {
                return false;
            }
            $declarations[] = self::$declarations[self::STRING];
        }

        if ($type & self::ARRAY) {
            if (is_array($value)) {
                return false;
            }
            $declarations[] = self::$declarations[self::ARRAY];
        }

        if ($type & self::OBJECT) {
            if (is_object($value)) {
                return false;
            }
            $declarations[] = self::$declarations[self::OBJECT];
        }

        if ($type & self::CALLABLE) {
            if (is_callable($value)) {
                return false;
            }
            $declarations[] = self::$declarations[self::CALLABLE];
        }

        if ($type & self::RESOURCE) {
            if (is_resource($value)) {
                return false;
            }
            $declarations[] = self::$declarations[self::RESOURCE];
        }

        return (bool)$declarations;
    }

    /**
     * 验证数值类型
     *
     * @param string $name
     * @param mixed $value
     * @param int $type
     * @throws InvalidArgumentException
     */
    public static function validate(string $name, $value, int $type)
    {
        if (self::invalid($value, $type, $declarations)) {
            throw self::exception($name, join(' or ', $declarations));
        }
    }

    /**
     * 抛出异常
     *
     * @param string $name
     * @param string $declaration
     * @return InvalidArgumentException
     */
    public static function exception(string $name, string $declaration): InvalidArgumentException
    {
        return new InvalidArgumentException(sprintf('%s must be %s.', $name, $declaration));
    }
}
