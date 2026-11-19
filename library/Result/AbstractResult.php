<?php
declare(strict_types=1);
namespace Coinsnap\Result;

if (!defined('ABSPATH')) {
    exit;
}

abstract class AbstractResult implements \ArrayAccess {
    /** @var array */
    private $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function offsetExists($offset): bool
    {
        $data = $this->getData();
        return array_key_exists($offset, $data);
    }

    // No "mixed" return type: PHP 7.4 reads it as a class name and throws a TypeError on every read.
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        $data = $this->getData();
        return $data[$offset] ?? null;
    }

    public function offsetSet($offset, $value): void
    {
        throw new \RuntimeException('You should not change the data in a result.');
    }

    public function offsetUnset($offset): void
    {
        throw new \RuntimeException('You should not change the data in a result.');
    }
}
