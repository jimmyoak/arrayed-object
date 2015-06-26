<?php

namespace JimmyOak\Test\ArrayedObject;

use JimmyOak\ArrayedObject\ArrayedObject;
use JimmyOak\Test\ArrayedObject\Value\DummyClass;

class ArrayedObjectTest extends \PHPUnit_Framework_TestCase
{
    /** @test */
    public function shouldGetClassNameAndData()
    {
        $class = DummyClass::class;
        $data = [
            'someRandomData'
        ];

        $arrayedObject = new ArrayedObject($class, $data);

        $this->assertSame($class, $arrayedObject->getClass());
        $this->assertSame($data, $arrayedObject->getData());
    }
}
