<?php

declare(strict_types=1);

namespace Platine\Test\Fixture;

use ReflectionException;
use stdClass;

class ClassConstructorThrowException
{
    public function __construct()
    {
        throw new ReflectionException();
    }
}


class ClassUnionOne
{
    public function __construct(public int $a)
    {
    }
}

class ClassUnionTwo
{
    public ClassUnionOne $a;
    public function __construct(null|ClassUnionOne|string $a = null)
    {
        if (is_null($a) || is_string($a)) {
            $a = new ClassUnionOne(50);
        }

        $this->a = $a;
    }
}

abstract class ContainerTestAbstractClass
{
    public function __construct()
    {
    }
}

final class ContainerTestFinalClass
{
}

class ContainerReflectionParamClass
{
    public function foo($a): void
    {
    }

    public function bar(int|float $a): void
    {
    }
}

class ContainerTestClass
{
    public $a;
    public $b;
    public $c;

    public function __construct(int $a, float $b, ContainerTestClassWithoutConstructor $c)
    {
        $this->a = $a;
        $this->b = $b;
        $this->c = $c;
    }

    public function foo(): void
    {
    }

    public function bar(float $a): void
    {
    }
}

class ContainerTestClassCyclicOne
{
    public function __construct(ContainerTestClassCyclicTwo $a)
    {
    }
}

class ContainerTestClassCyclicTwo
{
    public function __construct(ContainerTestClassCyclicOne $a)
    {
    }
}

class ContainerTestClassInterfaceDependency
{
    public $a = null;

    public function __construct(ContainerTestInterface $a)
    {
        $this->a = $a;
    }
}

class ContainerTestClassNoPublicConstructor
{
    private function __construct()
    {
    }
}

class ContainerTestClassUsingGlobalValue
{
    public $value = 1;

    public function __construct(int $globalValue)
    {
        $this->value = $globalValue;
    }
}

class ContainerTestClassWithoutConstructor
{
    public $a;

    public function foo(): void
    {
        $this->a = 100;
    }
}

class ContainerTestClassWithoutConstructorParam
{
    public function __construct()
    {
    }
}

class ContainerTestClassConstructorVariadicParam
{
    public $a = 0;

    public function __construct(?int ...$a)
    {
        $this->a = $a;
    }
}

interface ContainerTestInterface
{
    public function foo(): ?string;
}

class ContainerTestInterfaceImpl implements ContainerTestInterface
{
    public function foo(): ?string
    {
        return null;
    }
}

class ContainerTestClassGetUsingDefine
{
    public function result()
    {
        return 6;
    }
}

class ContainerTestClassConstructorParamDefaultValue
{
    public $a;

    public function __construct(int $a = 50)
    {
        $this->a = $a;
    }
}

class ContainerTestClassConstructorParamDefaultValueClass
{
    public $a;

    public function __construct(ContainerTestClassWithoutConstructorParam $a = null)
    {
        $this->a = $a;
    }
}

class ContainerTestDelegateParent
{
    public function build()
    {
        return new stdClass();
    }
}

class ContainerTestDelegate extends ContainerTestDelegateParent
{
    public function create()
    {
        return new stdClass();
    }

    public function __invoke()
    {
        return new stdClass();
    }

    public static function factory()
    {
        return new stdClass();
    }
}

function container_delegate($c, $p)
{
    return new stdClass();
}
