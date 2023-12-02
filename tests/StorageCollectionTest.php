<?php

declare(strict_types=1);

namespace Platine\Test\Container;

use Platine\Container\Container;
use Platine\Container\Storage;
use Platine\Container\StorageCollection;
use Platine\Dev\PlatineTestCase;

/**
 * StorageCollection class tests
 *
 * @group core
 * @group container
 */
class StorageCollectionTest extends PlatineTestCase
{
    public function testConstructorOneValueIsNotInstanceOfStorage(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $c = new StorageCollection(array('foo'));
    }

    public function testConstructorParamContainsListOfStorage(): void
    {
        $name = 'foo';
        $closure = function () {
            return 'bar';
        };
        $c = new StorageCollection(array(new Storage($name, $closure)));
        $this->assertCount(1, $c->all());
        $this->assertTrue($c->has($name));
    }

    public function testGetAndDelete(): void
    {
        $name = 'foo';
        $closure = function () {
            return 'bar';
        };
        $c = new StorageCollection(array(new Storage($name, $closure)));
        $this->assertCount(1, $c->all());
        $this->assertTrue($c->has($name));
        $this->assertInstanceOf(Storage::class, $c->get($name));
        $this->assertEquals('foo', $c->get($name)->getName());
        $c->delete($name);
        $this->assertFalse($c->has($name));
        //TODO: when delete an storage only is deleted from list not from all
        $this->assertCount(1, $c->all());
    }
}
