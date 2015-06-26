<?php

namespace JimmyOak\Test\ArrayedObject;

use JimmyOak\ArrayedObject\ArrayedObject;
use JimmyOak\ArrayedObject\ArrayedObjectFactory;
use JimmyOak\Test\ArrayedObject\Value\AnotherDummyClass;
use JimmyOak\Test\ArrayedObject\Value\DummyClass;

class ArrayedObjectFactoryTest extends \PHPUnit_Framework_TestCase
{
    /** @var ArrayedObjectFactory */
    private $factory;

    protected function setUp()
    {
        $this->factory = ArrayedObjectFactory::instance();
    }


    /** @test */
    public function shouldBeSingleton()
    {
        $isConstructorCallable = is_callable([$this->factory, '__construct']);

        $this->assertFalse($isConstructorCallable);
    }

    /** @test */
    public function shouldCreateArrayedObjectFromObject()
    {
        $expectedData = $this->getExpectedData();
        $object = new DummyClass();

        $arrayedObject = $this->factory->create($object);

        $this->assertInstanceOf(ArrayedObject::class, $arrayedObject);
        $this->assertSame(DummyClass::class, $arrayedObject->getClass());
        $this->assertEquals($expectedData, $arrayedObject->getData());
    }

    private function getExpectedData()
    {
        $expectedData = [
            'aPrivateProperty' => 'A STRING',
            'aProtectedProperty' => 1234,
            'aPublicProperty' => 'ANOTHER STRING',
            'anObject' =>
                new ArrayedObject(AnotherDummyClass::class, [
                    'aValue' => 'Jimmy',
                    'anotherValue' => 'Kane',
                    'oneMoreValue' => 'Oak',
                ]),
            'anArrayOfObjects' =>
                array(
                    new ArrayedObject(AnotherDummyClass::class, [
                        'aValue' => 'Jimmy',
                        'anotherValue' => 'Kane',
                        'oneMoreValue' => 'Oak',
                    ]),
                    new ArrayedObject(AnotherDummyClass::class, [
                        'aValue' => 'Jimmy',
                        'anotherValue' => 'Kane',
                        'oneMoreValue' => 'Oak',
                    ]),
                ),
            'aParentProperty' => 5,
        ];

        return $expectedData;
    }
}
