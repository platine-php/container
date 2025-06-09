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
 *  @file Container.php
 *
 *  The Container class used to handle dependency injection and keep instances of
 *  loaded classes, etc.
 *
 *  @package    Platine\Container
 *  @author Platine Developers Team
 *  @copyright  Copyright (c) 2020
 *  @license    http://opensource.org/licenses/MIT  MIT License
 *  @link   https://www.platine-php.com
 *  @version 1.0.0
 *  @filesource
 */

declare(strict_types=1);

namespace Platine\Container;

use Closure;
use Platine\Container\Exception\ContainerException;
use Platine\Container\Exception\NotFoundException;
use Platine\Container\Resolver\ConstructorResolver;
use Platine\Container\Resolver\ResolverInterface;

/**
 * @class Container
 * @package Platine\Container
 */
class Container implements ContainerInterface
{
    /**
     * The container global instance
     * @var Container|null
     */
    protected static ?Container $instance = null;

    /**
     * The resolver instance
     * @var ResolverInterface
     */
    protected ResolverInterface $resolver;

    /**
     * The Storage collection instance
     * @var StorageCollection
     */
    protected StorageCollection $storage;

    /**
     * The list of resolved instances
     * @var array<string, object>
     */
    protected array $instances = [];

    /**
     * The current instances in phase of construction
     * @var array<string, int>
     */
    protected array $lock = [];

    /**
     * Create new container instance
     */
    public function __construct()
    {
        $this->resolver =  new ConstructorResolver();
        $this->storage =  new StorageCollection();

        static::$instance = $this;
    }

    /**
     *
     * @param ResolverInterface $resolver
     * @return $this
     */
    public function setResolver(ResolverInterface $resolver): self
    {
        $this->resolver = $resolver;

        return $this;
    }

    /**
     *
     * @param StorageCollection $storage
     * @return $this
     */
    public function setStorage(StorageCollection $storage): self
    {
        $this->storage = $storage;

        return $this;
    }

    /**
     * Return the global instance of the container
     * @return self
     */
    public static function getInstance(): self
    {
        if (static::$instance === null) {
            static::$instance = new self();
        }

        return static::$instance;
    }

    /**
     * Remove all lock when copy this object
     * @return void
     */
    public function __clone(): void
    {
        $this->lock = [];
    }

    /**
     * Return the resolver instance
     * @return ResolverInterface
     */
    public function getResolver(): ResolverInterface
    {
        return $this->resolver;
    }

    /**
     * Return the storage collection instance
     * @return StorageCollection
     */
    public function getStorage(): StorageCollection
    {
        return $this->storage;
    }

    /**
     * Return the array of resolved instances
     * @return array<string, object>
     */
    public function getInstances(): array
    {
        return $this->instances;
    }

    /**
     * Bind new type to the container
     * @param  string $id the id of the type to bind
     * @param  Closure|string|mixed|null  $type the type to bind. if null will
     * use the $id
     * @param  array<string, mixed>  $parameters the array of parameters
     * used to resolve instance of the type
     * @param  bool $shared  whether the instance need to be shared
     * @return StorageInterface
     */
    public function bind(
        string $id,
        mixed $type = null,
        array $parameters = [],
        bool $shared = false
    ): StorageInterface {
        //remove the previous instance
        unset($this->instances[$id]);

        /** @var mixed */
        $resolvedType = $type ?? $id;
        if (!($resolvedType instanceof Closure)) {
            $resolvedType = $this->getClosure($resolvedType);
        }

        $params = [];
        foreach ($parameters as $name => $value) {
            $params[] = new Parameter($name, $value);
        }

        return $this->storage->add(new Storage(
            $id,
            $resolvedType,
            $shared,
            count($params) > 0 ? new ParameterCollection($params) : null
        ));
    }

    /**
     * Set the new instance in to the container
     * @param  object  $instance the instance to set
     * @param  string|null $id  the id of the instance. If null will try
     * to detect the type using get_class()
     * @return void
     */
    public function instance(object $instance, ?string $id = null): void
    {
        if ($id === null) {
            $id = get_class($instance);
        }
        $this->storage->delete($id);
        $this->instances[$id] = $instance;
    }

    /**
     * Bind the type as shared
     * @param  string $id
     * @param  Closure|string|mixed|null $type       the type to share
     * @param  array<string, mixed>  $parameters
     * @return StorageInterface
     */
    public function share(string $id, mixed $type = null, array $parameters = []): StorageInterface
    {
        return $this->bind($id, $type, $parameters, true);
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $id): bool
    {
        return isset($this->instances[$id]) || $this->storage->has($id);
    }

    /**
     * Make the instance for the given type.
     *
     * The difference with get($id) is that if instance is not yet available in container
     * it will be make.
     *
     * @param  string $id
     * @param  array<string, mixed>  $parameters
     * @return mixed
     */
    public function make(string $id, array $parameters = []): mixed
    {
        if (isset($this->lock[$id])) {
            throw new ContainerException(sprintf(
                'Detected a cyclic dependency while provisioning [%s]',
                $id
            ));
        }

        $this->lock[$id] = count($this->lock);

        if ($this->has($id) === false) {
            $this->bind($id, null, $parameters, false);
        }

        if (isset($this->instances[$id])) {
            unset($this->lock[$id]);

            return $this->instances[$id];
        }

        $instance = null;
        $result = $this->storage->get($id);
        if ($result !== null) {
            /** @var mixed */
            $instance = $result->getInstance($this);

            if ($result->isShared()) {
                $this->instances[$id] = $instance;
            }
            unset($this->lock[$id]);
        }

        return $instance;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $id): mixed
    {
        if ($this->has($id) === false) {
            throw new NotFoundException(sprintf('The type/class [%s] does not exist in the container!', $id));
        }

        return $this->make($id);
    }

    /**
     * Return the closure for the given type
     * @param  mixed $type
     * @return Closure
     */
    protected function getClosure(mixed $type): Closure
    {
        if (is_callable($type)) {
            return Closure::fromCallable($type);
        } elseif (is_string($type) === false) {
            return function () use ($type) {
                return $type;
            };
        }

        return function ($container, $parameters) use ($type) {
            return $container->getResolver()
                             ->resolve($container, $type, $parameters);
        };
    }
}
