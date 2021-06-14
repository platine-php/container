<?php

declare(strict_types=1);

namespace Platine\Test\Container;

use Platine\Container\Container;
use Platine\Container\ConstructorResolver;
use Platine\Container\Exception\ContainerException;
use Platine\Dev\PlatineTestCase;
use Platine\Test\Fixture\ContainerTestClassNoPublicConstructor;

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
}
