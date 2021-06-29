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
 *  @link   http://www.iacademy.cf
 *  @version 1.0.0
 *  @filesource
 */

declare(strict_types=1);

namespace Platine\Container;

use Closure;
use Platine\Container\Exception\ContainerException;
use Platine\Container\Exception\NotFoundException;

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
    protected StorageCollection $storages;

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
     * @param ResolverInterface|null $resolver the resolver to use
     * @param StorageCollection|null $storages the storage's collection
     */
    public function __construct(
        ?ResolverInterface $resolver = null,
        ?StorageCollection $storages = null
    ) {
        $this->resolver = $resolver ? $resolver : new ConstructorResolver();
        $this->storages = $storages ? $storages : new StorageCollection();
    }

    /**
     * Return the global instance of the container
     * @param ResolverInterface|null $resolver
     * @param StorageCollection|null $storages
     * @return Container
     */
    public static function getInstance(
        ?ResolverInterface $resolver = null,
        ?StorageCollection $storages = null
    ): Container {
        if (static::$instance === null) {
            static::$instance = new static(
                $resolver,
                $storages
            );
        }

        return static::$instance;
    }

    /**
     * Remove all lock when copy this object
     * @return void
     */
    public function __clone()
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
    public function getStorages(): StorageCollection
    {
        return $this->storages;
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
        $type = null,
        array $parameters = [],
        bool $shared = false
    ): StorageInterface {
        //remove the previous instance
        unset($this->instances[$id]);

        /** @var mixed */
        $type = $type ? $type : $id;
        if (!($type instanceof Closure)) {
            $type = $this->getClosure($type);
        }

        $params = [];
        foreach ($parameters as $name => $value) {
            $params[] = new Parameter($name, $value);
        }

        return $this->storages->add(new Storage(
            $id,
            $type,
            $shared,
            !empty($params) ? new ParameterCollection($params) : null
        ));
    }

    /**
     * Set the new instance in to the contaner
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
        $this->storages->delete($id);
        $this->instances[$id] = $instance;
    }

    /**
     * Bind the type as shared
     * @param  string $id
     * @param  Closure|string|mixed|null $type       the type to share
     * @param  array<string, mixed>  $parameters
     * @return StorageInterface
     */
    public function share(string $id, $type = null, array $parameters = []): StorageInterface
    {
        return $this->bind($id, $type, $parameters, true);
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $id): bool
    {
        return isset($this->instances[$id]) || $this->storages->has($id);
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
    public function make(string $id, array $parameters = [])
    {
        if (isset($this->lock[$id])) {
            throw new ContainerException(sprintf(
                'Detected a cyclic dependency while provisioning [%s]',
                $id
            ));
        }
        $this->lock[$id] = count($this->lock);

        if (!$this->has($id)) {
            $this->bind($id, null, $parameters, false);
        }

        if (isset($this->instances[$id])) {
            unset($this->lock[$id]);
            return $this->instances[$id];
        }

        /** @var mixed */
        $instance = $this->storages
                ->get($id)
                ->getInstance($this);

        if ($this->storages->get($id)->isShared()) {
            $this->instances[$id] = $instance;
        }
        unset($this->lock[$id]);

        return $instance;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $id)
    {
        if (!$this->has($id)) {
            throw new NotFoundException(sprintf('The type/class [%s] does not exist in the container!', $id));
        }

        return $this->make($id);
    }

    /**
     * Return the closure for the given type
     * @param  string|mixed $type
     * @return Closure
     */
    protected function getClosure($type): Closure
    {
        if (is_callable($type)) {
            return Closure::fromCallable($type);
        } elseif (!is_string($type)) {
            return function () use ($type) {
                return $type;
            };
        }

        return function ($container, $parameters) use ($type) {
            return $container
                    ->getResolver()
                    ->resolve($container, $type, $parameters);
        };
    }
}
