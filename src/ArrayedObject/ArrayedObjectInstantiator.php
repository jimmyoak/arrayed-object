<?php

namespace JimmyOak\ArrayedObject;

class ArrayedObjectInstantiator
{
    private $specialCasesClasses = [
        \DateTimeInterface::class,
        \DateInterval::class,
    ];

    private $specialCaseClassFunctionMap = [
        \DateTimeInterface::class => 'createDateTimeInterfaceInstance',
        \DateInterval::class => 'createDateIntervalInstance',
    ];

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

    /**
     * @param ArrayedObject $arrayedObject
     *
     * @return mixed
     */
    public function instantiate(ArrayedObject $arrayedObject)
    {
        if ($this->isSpecialCase($arrayedObject)) {
            return $this->createSpecialCaseInstance($arrayedObject);
        }

        $instance = $this->createClassInstance($arrayedObject);

        $this->fillClassInstance($instance, $arrayedObject->getData());

        return $instance;
    }

    private function createClassInstance(ArrayedObject $arrayedObject)
    {
        $instance = (new \ReflectionClass($arrayedObject->getClass()))->newInstanceWithoutConstructor();

        return $instance;
    }

    private function fillClassInstance($instance, $data)
    {
        $instantiator = $this;
        $valueProcessor = function ($value) use (&$valueProcessor, $instantiator) {
            if ($value instanceof ArrayedObject) {
                $value = $instantiator->instantiate($value);
            }

            if (is_array($value)) {
                foreach ($value as &$innerValue) {
                    $innerValue = $valueProcessor($innerValue);
                }
            }

            return $value;
        };

        $setObjectVarsClosure = function ($data, $class, &$valueProcessor) {
            foreach ($data as $property => $value) {
                if (property_exists($class, $property)) {
                    $value = $valueProcessor($value);
                    $this->$property = $value;
                }
            }
        };

        $class = get_class($instance);
        do {
            $bindedSetObjectVarsClosure = \Closure::bind($setObjectVarsClosure, $instance, $class);
            $bindedSetObjectVarsClosure($data, $class, $valueProcessor);
        } while ($class = get_parent_class($class));
    }

    private function getSpecialCaseClass(ArrayedObject $arrayedObject)
    {
        $class = $arrayedObject->getClass();
        $reflectionClass = new \ReflectionClass($class);
        foreach ($this->specialCasesClasses as $specialCaseClass) {
            if ($reflectionClass->isSubclassOf($specialCaseClass) || $class === $specialCaseClass) {
                return $specialCaseClass;
            }
        }

        return null;
    }

    private function isSpecialCase(ArrayedObject $arrayedObject)
    {
        return (bool) $this->getSpecialCaseClass($arrayedObject);
    }

    private function createSpecialCaseInstance(ArrayedObject $arrayedObject)
    {
        $specialCaseClass = $this->getSpecialCaseClass($arrayedObject);
        $functionName = $this->specialCaseClassFunctionMap[$specialCaseClass];

        return $this->$functionName($arrayedObject);
    }

    private function createDateTimeInterfaceInstance(ArrayedObject $arrayedObject)
    {
        try {
            $instance = (new \ReflectionClass($arrayedObject->getClass()))->newInstanceWithoutConstructor();
        } catch (\ReflectionException $e) {
            $instance = (new \ReflectionClass($arrayedObject->getClass()))->newInstance();
        }

        $reflectionClass = new \ReflectionClass(\DateTime::class);
        $dateTimeConstructor = $reflectionClass->getConstructor();

        $data = $arrayedObject->getData();
        $date = isset($data['date']) ? $data['date'] : null;
        $dateTimeZone = isset($data['timezone']) ? $data['timezone'] : null;
        $dateTimeConstructor->invokeArgs($instance, [$date, new \DateTimeZone($dateTimeZone)]);

        return $instance;
    }

    private function createDateIntervalInstance(ArrayedObject $arrayedObject)
    {
        $constructionData = ['P0D'];
        try {
            $instance = (new \ReflectionClass($arrayedObject->getClass()))->newInstanceWithoutConstructor();

            $reflectionClass = new \ReflectionClass($instance);

            $dateIntervalConstructor = $reflectionClass->getConstructor();
            $dateIntervalConstructor->invokeArgs($instance, $constructionData);
        } catch (\ReflectionException $e) {
            $instance = (new \ReflectionClass($arrayedObject->getClass()))->newInstanceArgs($constructionData);
        }

        $data = $arrayedObject->getData();
        foreach ($data as $property => $value) {
            $instance->$property = $value;
        }

        return $instance;
    }
}
