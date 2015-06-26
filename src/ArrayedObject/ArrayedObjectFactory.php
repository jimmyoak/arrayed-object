<?php

namespace JimmyOak\ArrayedObject;

class ArrayedObjectFactory
{
    private function __construct()
    {
    }

    public static function instance()
    {
        static $instance;

        if (null === $instance) {
            $instance = new static();
        }

        return $instance;
    }

    public function create($object)
    {
        return new ArrayedObject(get_class($object), $this->arrayObjectVars($object));
    }

    private function arrayObjectVars($object)
    {
        $arrayedObjectFactory = $this;

        $valueProcessor = function ($value) use (&$valueProcessor, $arrayedObjectFactory) {
            if (is_object($value)) {
                $value = $arrayedObjectFactory->create($value);
            } elseif (is_array($value)) {
                foreach ($value as &$innerValue) {
                    $innerValue = $valueProcessor($innerValue);
                }
            }

            return $value;
        };

        $getObjectVarsClosure = function () use ($valueProcessor) {
            return array_map($valueProcessor, get_object_vars($this));
        };

        $vars = [];
        $class = get_class($object);
        do {
            $bindedGetObjectVarsClosure = \Closure::bind($getObjectVarsClosure, $object, $class);
            $vars = array_merge($vars, $bindedGetObjectVarsClosure());
        } while ($class = get_parent_class($class));

        return $vars;
    }
}
