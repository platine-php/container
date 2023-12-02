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
 *  @file Storage.php
 *
 *  This class is the storage used by the container
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
use Platine\Container\ContainerInterface;
use Platine\Container\Parameter;
use Platine\Container\ParameterCollection;
use Platine\Container\StorageInterface;

class Storage implements StorageInterface
{
    /**
     * The storage name
     * @var string
     */
    protected string $name;

    /**
     * The storage closure
     * @var Closure
     */
    protected Closure $closure;

    /**
     * Whether the instance is shared
     * @var bool
     */
    protected bool $shared;

    /**
     * The container parameter collection instance
     * @var ParameterCollection
     */
    protected ParameterCollection $parameters;

    /**
     * Create new parameter
     *
     * @param string $name  the name of the storage
     * @param Closure $closure
     * @param bool $shared
     * @param ParameterCollection $parameters
     */
    public function __construct(
        string $name,
        Closure $closure,
        bool $shared = false,
        ?ParameterCollection $parameters = null
    ) {
        $this->name = $name;
        $this->closure = $closure;
        $this->shared = $shared;
        $this->parameters = $parameters ? $parameters : new ParameterCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function isShared(): bool
    {
        return $this->shared;
    }

    /**
     * {@inheritdoc}
     */
    public function getInstance(ContainerInterface $container)
    {
        return ($this->closure)($container, $this->parameters);
    }

    /**
     * Set the managed instance as shared
     *
     * @return self
     */
    public function share(): self
    {
        $this->shared = true;
        return $this;
    }

    /**
     * Return the container parameter collection instance
     * @return ParameterCollection
     */
    public function getParameters(): ParameterCollection
    {
        return $this->parameters;
    }

    /**
     * Bind the parameter to use for the managed instance
     *
     * @param string $name  the name of parameter
     * @param Closure|mixed $value the parameter value
     *
     * @return self
     */
    public function bindParameter(string $name, $value): self
    {
        $this->parameters->add(new Parameter($name, $value));
        return $this;
    }
}
