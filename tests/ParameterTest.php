<?php

declare(strict_types=1);

namespace Platine\Test\Container;

use Platine\Container\Container;
use Platine\Container\Parameter;
use Platine\PlatineTestCase;

/**
 * Parameter class tests
 *
 * @group core
 * @group container
 */
class ParameterTest extends PlatineTestCase
{

    public function testSimpleParameter(): void
    {
        $name = 'foo';
        $value = 'bar';
        $c = new Parameter($name, $value);
        $this->assertEquals($name, $c->getName());
        $this->assertEquals($value, $c->getValue(new Container()));
    }

    public function testClosureParameter(): void
    {
        $name = 'foo';
        $value = 'bar_closure';
        $c = new Parameter($name, function () {
                    return 'bar_closure';
        });
        $this->assertEquals($name, $c->getName());
        $this->assertEquals($value, $c->getValue(new Container()));
    }
}
