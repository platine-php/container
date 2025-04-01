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
 *  @file StorageCollection.php
 *
 *  This class represente the collection of container storages
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

/**
 * @class StorageCollection
 * @package Platine\Container
 */
class StorageCollection
{
    /**
     * The array of storage
     * @var array<string, StorageInterface>
     */
    protected array $storages = [];

    /**
     * The array of all of the container storage
     * @var StorageInterface[]
     */
    protected array $all = [];

    /**
     * Create new collection of storage
     *
     * @param StorageInterface[] $storages  the container storage
     */
    public function __construct(array $storages = [])
    {
        foreach ($storages as $storage) {
            $this->add($storage);
        }
    }

    /**
     * Add new storage to the collection
     * @param StorageInterface $storage
     */
    public function add(StorageInterface $storage): StorageInterface
    {
        $this->all[] = $storage;
        return $this->storages[$storage->getName()] = $storage;
    }

    /**
     * Return all array of container storage
     * @return StorageInterface[] the collection of storage
     */
    public function all(): array
    {
        return $this->all;
    }

    /**
     * Check whether the collection contains the storage for the
     * given name
     * @param  string  $name the name of the storage
     * @return boolean
     */
    public function has(string $name): bool
    {
        return array_key_exists($name, $this->storages);
    }

    /**
     * Get the container storage for the given name from the collection
     * @param  string  $name the name of the storage
     * @return StorageInterface|null
     */
    public function get(string $name): ?StorageInterface
    {
        return array_key_exists($name, $this->storages)
                ? $this->storages[$name]
                : null;
    }

    /**
     * Delete from collection the container storage for the given name
     * @param  string  $name the name of the storage
     * @return StorageCollection
     */
    public function delete(string $name): self
    {
        unset($this->storages[$name]);
        return $this;
    }
}
