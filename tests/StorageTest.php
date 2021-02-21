<?php

declare(strict_types=1);

namespace Platine\Test\Container;

use Platine\Container\Container;
use Platine\Container\Storage;
use Platine\Container\Parameter;
use Platine\Container\ParameterCollection;
use Platine\PlatineTestCase;

/**
 * Storage class tests
 *
 * @group core
 * @group container
 */
class StorageTest extends PlatineTestCase
{

    public function testConstructionDefault(): void
    {
        $name = 'foo';
        $value = function () {
            return 'bar';
        };
        $c = new Storage($name, $value);
        $this->assertEquals($name, $c->getName());
        $this->assertFalse($c->isShared());
        $this->assertInstanceOf(ParameterCollection::class, $c->getParameters());
    }

    public function testShare(): void
    {
        $name = 'foo';
        $value = function () {
            return 'bar';
        };
        $c = new Storage($name, $value);
        $this->assertEquals($name, $c->getName());
        $this->assertFalse($c->isShared());
        $this->assertInstanceOf(ParameterCollection::class, $c->getParameters());
        $c->share();
        $this->assertTrue($c->isShared());
    }

    public function testBindParameter(): void
    {
        $name = 'foo';
        $value = function () {
            return 'bar';
        };
        $c = new Storage($name, $value);
        $c->bindParameter($name, $value);
        $this->assertCount(1, $c->getParameters()->all());
    }
}
