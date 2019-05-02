<?php

namespace MKCG\DBAL\Filters;

use MKCG\DBAL\Filters\Traits\{
    GetterTrait
};

abstract class CommonFilter
{
    use GetterTrait;

    public function toArray()
    {
        $data = [
            '_type' => static::getFilterType()
        ];

        $reflection = new \ReflectionClass($this);

        while (true) {
            $properties = $reflection
                ->getProperties(\ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_PROTECTED | \ReflectionProperty::IS_PRIVATE);

            foreach ($properties as $property) {
                $isPublic = $property->isPublic();
                !$isPublic and  $property->setAccessible(true);

                $name = $property->getName();
                $value = $property->getValue($this);

                $data[$name] = is_object($value)
                    ? $value->toArray()
                    : is_array($value)
                        ? array_map(function ($element) {
                            return is_object($element)
                                ? $element->toArray()
                                : $element;
                        }, $value)
                        : $value
                    ;

                !$isPublic and $property->setAccessible(false);
            }

            $parent = $reflection->getParentClass();

            if ($parent === false) {
                break;
            }

            $reflection = $parent;
        }

        return $data;
    }

    public static function getFilterType()
    {
        $type = explode('\\', get_called_class());
        $type = array_pop($type);
        $type = str_replace('Filter', '', $type);

        $type = str_replace(
            range('A', 'Z'),
            array_map(function($char) {
                return '_' . $char;
            }, range('a', 'z')),
            $type
        );

        $type = strtolower($type);
        strpos($type, '_') === 0 and $type = substr($type, 1);

        return $type;
    }
}
