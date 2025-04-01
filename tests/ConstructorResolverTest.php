<?php

declare(strict_types=1);

namespace Platine\Test\Container;

use Platine\Container\Container;
use Platine\Dev\PlatineTestCase;
use Platine\Container\Exception\ContainerException;
use Platine\Container\Resolver\ConstructorResolver;
use Platine\Test\Fixture\ContainerReflectionParamClass;
use Platine\Test\Fixture\ContainerTestClassNoPublicConstructor;
use ReflectionMethod;

/**
 * ConstructorResolver class tests
 *
 * @group core
 * @group container
 */
class ConstructorResolverTest extends PlatineTestCase
{
    public function testResolveClassNotFound(): void
    {
        $this->expectException(ContainerException::class);
        $c = new ConstructorResolver();
        $c->resolve(new Container(), 'not_found_class');
    }

    public function testResolveClassConstructorIsNotPublic(): void
    {
        $this->expectException(ContainerException::class);
        $c = new ConstructorResolver();
        $c->resolve(new Container(), ContainerTestClassNoPublicConstructor::class);
    }

    public function testGetTypesEmpty(): void
    {
        $c = new ConstructorResolver();
        $o = new ContainerReflectionParamClass();
        $rm = new ReflectionMethod($o, 'foo');
        $rp = $rm->getParameters();

        foreach ($rp as $b) {
            $f = $this->runPrivateProtectedMethod($c, 'getTypes', [$b]);
            $this->assertCount(0, $f);
        }
    }

    public function testGetTypesUnion(): void
    {
        $c = new ConstructorResolver();
        $o = new ContainerReflectionParamClass();
        $rm = new ReflectionMethod($o, 'bar');
        $rp = $rm->getParameters();

        foreach ($rp as $b) {
            $f = $this->runPrivateProtectedMethod($c, 'getTypes', [$b]);
            $this->assertCount(2, $f);
        }
    }
}
