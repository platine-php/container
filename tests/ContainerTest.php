<?php

declare(strict_types=1);

namespace Platine\Test\Container;

use Platine\Container\ConstructorResolver;
use Platine\Container\Container;
use Platine\Container\Exception\ContainerException;
use Platine\Container\Exception\NotFoundException;
use Platine\Container\StorageCollection;
use Platine\Dev\PlatineTestCase;
use Platine\Test\Fixture\ContainerTestAbstractClass;
use Platine\Test\Fixture\ContainerTestClass;
use Platine\Test\Fixture\ContainerTestClassConstructorParamDefaultValue;
use Platine\Test\Fixture\ContainerTestClassConstructorParamDefaultValueClass;
use Platine\Test\Fixture\ContainerTestClassConstructorVariadicParam;
use Platine\Test\Fixture\ContainerTestClassCyclicOne;
use Platine\Test\Fixture\ContainerTestClassInterfaceDependency;
use Platine\Test\Fixture\ContainerTestClassNoPublicConstructor;
use Platine\Test\Fixture\ContainerTestClassUsingGlobalValue;
use Platine\Test\Fixture\ContainerTestClassWithoutConstructor;
use Platine\Test\Fixture\ContainerTestClassWithoutConstructorParam;
use Platine\Test\Fixture\ContainerTestDelegate;
use Platine\Test\Fixture\ContainerTestInterface;
use Platine\Test\Fixture\ContainerTestInterfaceImpl;
use stdClass;

/**
 * Container class tests
 *
 * @group core
 * @group container
 */
class ContainerTest extends PlatineTestCase
{

    public function testConstructionDefault(): void
    {
        $c = new Container();
        $this->assertInstanceOf(Container::class, $c);
    }

    public function testGetInstance(): void
    {
        $c = Container::getInstance();
        $this->assertInstanceOf(Container::class, $c);
    }

    public function testConstructionUsingCustomResolver(): void
    {
        $cr = new ConstructorResolver();
        $c = new Container($cr);
        $this->assertInstanceOf(ConstructorResolver::class, $cr);
        $this->assertEquals($cr, $c->getResolver());
    }

    public function testConstructionUsingCustomStorageCollection(): void
    {
        $cr = new StorageCollection();
        $c = new Container(null, $cr);
        $this->assertInstanceOf(StorageCollection::class, $cr);
        $this->assertEquals($cr, $c->getStorages());
    }

    public function testClone(): void
    {
        $c = new Container();
        $cc = clone $c;
        $this->assertInstanceOf(Container::class, $c);
        $this->assertInstanceOf(Container::class, $cc);
        $this->assertEquals($c, $cc);
        $this->assertNotSame($c, $cc);
    }

    public function testBind(): void
    {
        $c = new Container();
        $c->bind(
            ContainerTestInterface::class,
            ContainerTestInterfaceImpl::class
        );
        $c->bind(
            ContainerTestClassInterfaceDependency::class,
            null,
            array('a' => ContainerTestInterfaceImpl::class)
        );
        $this->assertInstanceOf(
            ContainerTestClassInterfaceDependency::class,
            $c->get(ContainerTestClassInterfaceDependency::class)
        );
    }

    public function testBindParameter(): void
    {
        $c = new Container();
        $c->bind(ContainerTestClassUsingGlobalValue::class)
                ->bindParameter('globalValue', 45);
        $obj = $c->get(ContainerTestClassUsingGlobalValue::class);
        $this->assertEquals(45, $obj->value);
    }

    public function testBindInterface(): void
    {
        $c = new Container();
        $c->bind(
            ContainerTestClassInterfaceDependency::class,
            null,
            array('a' => ContainerTestInterfaceImpl::class)
        );
        $c->bind(ContainerTestInterface::class, ContainerTestInterfaceImpl::class);
        $obj = $c->get(ContainerTestClassInterfaceDependency::class);
        $this->assertInstanceOf(ContainerTestClassInterfaceDependency::class, $obj);
    }


    public function testBindAbstract(): void
    {
        $c = new Container();
        $c->bind(ContainerTestAbstractClass::class, ContainerTestClassWithoutConstructor::class);
        $obj = $c->get(ContainerTestAbstractClass::class);
        $this->assertInstanceOf(ContainerTestClassWithoutConstructor::class, $obj);
    }

    public function testBindUsingDirectClosure(): void
    {
        $c = new Container();
        $c->bind(ContainerTestClassWithoutConstructor::class, function () {
            return new stdClass();
        });
        $o = $c->get(ContainerTestClassWithoutConstructor::class);
        $this->assertInstanceOf('\\stdClass', $o);
    }

    public function testBindUsingClosureWithContainerInstance(): void
    {
        $c = new Container();
        $c->bind(
            ContainerTestInterface::class,
            ContainerTestInterfaceImpl::class
        );

        $c->bind(
            ContainerTestClassInterfaceDependency::class,
            function (Container $o) {
                return new ContainerTestClassInterfaceDependency(
                    $o->get(ContainerTestInterface::class)
                );
            }
        );
        $this->assertInstanceOf(
            ContainerTestClassInterfaceDependency::class,
            $c->get(ContainerTestClassInterfaceDependency::class)
        );
    }

    public function testBindUsingSimpleValue(): void
    {
        $c = new Container();
        $c->bind(ContainerTestClassWithoutConstructor::class, 123);
        $o = $c->get(ContainerTestClassWithoutConstructor::class);
        $this->assertEquals(123, $o);
    }

    public function testBindUsingDirectCallableFunction(): void
    {
        $c = new Container();
        $c->bind(ContainerTestClassWithoutConstructor::class, 'Platine\\Test\\Fixture\\container_delegate');
        $o = $c->get(ContainerTestClassWithoutConstructor::class);
        $this->assertInstanceOf('\\stdClass', $o);
    }

    public function testBindUsingDirectCallableClassMethod(): void
    {
        $c = new Container();
        $c->bind(ContainerTestClassWithoutConstructor::class, array(new ContainerTestDelegate(), 'create'));
        $o = $c->get(ContainerTestClassWithoutConstructor::class);
        $this->assertInstanceOf('\\stdClass', $o);
    }

    public function testShare(): void
    {
        $c = new Container();
        $c->share(ContainerTestClassWithoutConstructor::class);
        $o = $c->get(ContainerTestClassWithoutConstructor::class);
        $this->assertInstanceOf(ContainerTestClassWithoutConstructor::class, $o);
        $this->assertEquals($o, $c->get(ContainerTestClassWithoutConstructor::class));
    }

    public function testSetInstanceIdIsNull(): void
    {
        $c = new Container();
        $ori = new ContainerTestClassWithoutConstructor();
        $c->instance($ori);
        $o = $c->get(ContainerTestClassWithoutConstructor::class);
        $this->assertInstanceOf(ContainerTestClassWithoutConstructor::class, $o);
        $this->assertEquals($o, $ori);
    }

    public function testSetInstanceUsingCustomId(): void
    {
        $c = new Container();
        $ori = new ContainerTestClassWithoutConstructor();
        $c->instance($ori, 'my_instance');
        $o = $c->get('my_instance');
        $this->assertInstanceOf(ContainerTestClassWithoutConstructor::class, $o);
        $this->assertEquals($o, $ori);
        $this->assertCount(1, $c->getInstances());
    }

    public function testGetCyclicDependency(): void
    {
        $this->expectException(ContainerException::class);
        $c = new Container();
        $c->bind(ContainerTestClassCyclicOne::class);
        $c->get(ContainerTestClassCyclicOne::class);
    }

    public function testGetNotFound(): void
    {
        $this->expectException(NotFoundException::class);
        $c = new Container();
        $c->get(ContainerTestClassWithoutConstructor::class);
    }

    public function testGetConstructorIsNotPublic(): void
    {
        $this->expectException(ContainerException::class);
        $c = new Container();
        $c->bind(ContainerTestClassNoPublicConstructor::class);
        $c->get(ContainerTestClassNoPublicConstructor::class);
    }

    public function testGetConstructorWithoutParameters(): void
    {
        $c = new Container();
        $c->bind(ContainerTestClassWithoutConstructorParam::class);
        $o = $c->get(ContainerTestClassWithoutConstructorParam::class);
        $this->assertInstanceOf(ContainerTestClassWithoutConstructorParam::class, $o);
    }

    public function testGetUsingAbstractClass(): void
    {
        $this->expectException(ContainerException::class);
        $c = new Container();
        $c->bind(ContainerTestAbstractClass::class);
        $o = $c->get(ContainerTestAbstractClass::class);
    }

    public function testGetConstructorUsingVariadicParam(): void
    {
        $c = new Container();
        $c->bind(ContainerTestClassConstructorVariadicParam::class);
        $o = $c->get(ContainerTestClassConstructorVariadicParam::class);
        $this->assertInstanceOf(ContainerTestClassConstructorVariadicParam::class, $o);
        $this->assertNotEmpty($o->a);
        $c->bind(ContainerTestClassConstructorVariadicParam::class, null, array('a' => 23));
        $o = $c->get(ContainerTestClassConstructorVariadicParam::class);
        $this->assertNotEmpty($o->a);
    }

    public function testGetConstructorUsingDefaultValueForParam(): void
    {
        $c = new Container();
        $c->bind(ContainerTestClassConstructorParamDefaultValue::class);
        $o = $c->get(ContainerTestClassConstructorParamDefaultValue::class);
        $this->assertInstanceOf(ContainerTestClassConstructorParamDefaultValue::class, $o);
        $this->assertEquals(50, $o->a);
    }

    public function testGetConstructorCannotFindDependencies(): void
    {
        $this->expectException(ContainerException::class);
        $c = new Container();
        $c->bind(ContainerTestClass::class);
        $o = $c->get(ContainerTestClass::class);
    }

    public function testGetClassNotFound(): void
    {
        $this->expectException(ContainerException::class);
        $c = new Container();
        $o = $c->get('FooContainerTestClass');
    }

    public function testGetDefineAllConstructorParametersForNonClassType(): void
    {
        $a = 23;
        $b = 2.3;
        $containter = new Container();
        $containter->bind(ContainerTestClassWithoutConstructor::class);
        $containter->bind(ContainerTestClass::class, null, array(
            'a' => $a,
            'b' => $b
        ));
        $o = $containter->get(ContainerTestClass::class);
        $this->assertInstanceOf(ContainerTestClass::class, $o);
        $this->assertEquals($o->a, $a);
        $this->assertEquals($o->b, $b);
    }

    public function testGetConstructorUsingDefaultValueForParamClass(): void
    {
        $c = new Container();
        $c->bind(ContainerTestClassWithoutConstructorParam::class);
        $c->bind(ContainerTestClassConstructorParamDefaultValueClass::class);
        $o = $c->get(ContainerTestClassConstructorParamDefaultValueClass::class);
        $this->assertInstanceOf(ContainerTestClassConstructorParamDefaultValueClass::class, $o);
        $this->assertInstanceOf(ContainerTestClassWithoutConstructorParam::class, $o->a);
    }
}
