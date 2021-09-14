<?php

/**
 * Platine Container
 *
 * Platine Container is the implementation of PSR 11
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2020 Platine Container
 * Copyright (c) 2019 Dion Chaika
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

/**
 *  @file ConstructorResolver.php
 *
 *  This class use constructor to resolve the instance
 *
 *  @package    Platine\Container
 *  @author Platine Developers Team
 *  @copyright  Copyright (c) 2020
 *  @license    http://opensource.org/licenses/MIT  MIT License
 *  @link   http://www.iacademy.cf
 *  @version 1.0.0
 *  @filesource
 */

declare(strict_types=1);

namespace Platine\Container;

use Platine\Container\Exception\ContainerException;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;

class ConstructorResolver implements ResolverInterface
{

    /**
     * {@inheritdoc}
     */
    public function resolve(
        ContainerInterface $container,
        string $type,
        ?ParameterCollection $parameters = null
    ) {
        try {
            $class = new ReflectionClass($type);
        } catch (ReflectionException $e) {
            throw new ContainerException($e->getMessage());
        }

        if (!$class->isInstantiable()) {
            throw new ContainerException(sprintf('Type/class [%s] is not instantiable!', $type));
        }

        $constructor = $class->getConstructor();
        if ($constructor === null) {
            try {
                return $class->newInstanceWithoutConstructor();
            } catch (ReflectionException $e) {
                throw new ContainerException($e->getMessage());
            }
        }

        $callback = function (ReflectionParameter $parameter) use ($container, $parameters) {
            return $this->resolveParameter(
                $container,
                $parameter,
                $parameters
            );
        };

        $arguments = array_map($callback, $constructor->getParameters());
        try {
            return $class->newInstanceArgs($arguments);
        } catch (ReflectionException $e) {
            throw new ContainerException($e->getMessage());
        }
    }

    /**
     * Resolve the parameter
     * @param  ContainerInterface       $container
     * @param  ReflectionParameter     $parameter           the reflection parameter
     * @param  ParameterCollection|null $parameters
     * @return mixed
     */
    protected function resolveParameter(
        ContainerInterface $container,
        ReflectionParameter $parameter,
        ?ParameterCollection $parameters = null
    ) {
        $class = $parameter->getType() && !$parameter->getType()->isBuiltin() 
                ? new ReflectionClass($parameter->getType()->getName())
                : null;
        
        //If the parameter is not a class
        if ($class === null) {
            if ($parameters !== null) {
                if ($parameters->has($parameter->name)) {
                    return $parameters
                                    ->get($parameter->name)
                                    ->getValue($container);
                }
            }
            if ($parameter->isDefaultValueAvailable()) {
                try {
                    return $parameter->getDefaultValue();
                } catch (ReflectionException $e) {
                    throw new ContainerException($e->getMessage());
                }
            }

            if ($parameter->isOptional()) {
                // This branch is required to work around PHP bugs where a parameter is optional
                // but has no default value available through reflection. Specifically, PDO exhibits
                // this behavior.
                return null;
            }

            throw new ContainerException(sprintf('Parameter [%s] is not bound!', $parameter->name));
        }

        return $container->get($class->name);
    }
}
