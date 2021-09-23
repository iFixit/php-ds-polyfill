<?php
namespace Ds\Traits;

use Ds\Sequence;
use OutOfRangeException;
use UnderflowException;

/**
 * Common functionality of all structures that implement 'Sequence'. Because the
 * polyfill's only goal is to achieve consistent behaviour, all sequences will
 * share the same implementation using an array array.
 *
 * @package Ds\Traits
 *
 * @template TValue
 */
trait GenericSequence
{
    /**
     * @var array internal array used to store the values of the sequence.
     *
     * @psalm-var array<TValue>
     */
    private $array = [];

    /**
     * @param iterable $values
     *
     * @psalm-param iterable<TValue> $values
     */
    public function __construct(iterable $values = [])
    {
        foreach ($values as $value) {
            $this->push($value);
        }
    }

    /**
     * @return array<TValue>
     */
    public function toArray(): array
    {
        return $this->array;
    }

    /**
     * @param callable(TValue): TValue $callback
     */
    public function apply(callable $callback)
    {
        foreach ($this->array as &$value) {
            $value = $callback($value);
        }
    }

    /**
     * @return self<TValue>
     */
    public function merge($values): Sequence
    {
        $copy = $this->copy();
        $copy->push(...$values);
        return $copy;
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->array);
    }

    public function contains(...$values): bool
    {
        foreach ($values as $value) {
            if ($this->find($value) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return self<TValue>
     */
    public function filter(callable $callback = null): Sequence
    {
        return new self(array_filter($this->array, $callback ?: 'boolval'));
    }

    /**
     * @return array-key|false
     */
    public function find($value)
    {
        return array_search($value, $this->array, true);
    }

    /**
     * @return TValue
     */
    public function first()
    {
        if ($this->isEmpty()) {
            throw new UnderflowException();
        }

        return $this->array[0];
    }

    /**
     * @return TValue
     */
    public function get(int $index)
    {
        if ( ! $this->validIndex($index)) {
            throw new OutOfRangeException();
        }

        return $this->array[$index];
    }

    public function insert(int $index, ...$values)
    {
        if ( ! $this->validIndex($index) && $index !== count($this)) {
            throw new OutOfRangeException();
        }

        array_splice($this->array, $index, 0, $values);
        $this->checkCapacity();
    }

    public function join(string $glue = null): string
    {
        return implode($glue, $this->array);
    }

    /**
     * @return TValue
     */
    public function last()
    {
        if ($this->isEmpty()) {
            throw new UnderflowException();
        }

        return $this->array[count($this) - 1];
    }

    /**
     * @return self<TValue>
     */
    public function map(callable $callback): Sequence
    {
        return new self(array_map($callback, $this->array));
    }

    /**
     * @return TValue
     */
    public function pop()
    {
        if ($this->isEmpty()) {
            throw new UnderflowException();
        }

        $value = array_pop($this->array);
        $this->checkCapacity();

        return $value;
    }

    public function push(...$values)
    {
        $this->ensureCapacity($this->count() + count($values));

        foreach ($values as $value) {
            $this->array[] = $value;
        }
    }

    /**
     * @template U
     * @param U $initial
     * @param callable(U, TValue): U
     * @return U
     */
    public function reduce(callable $callback, $initial = null)
    {
        return array_reduce($this->array, $callback, $initial);
    }

    /**
     * @return TValue
     */
    public function remove(int $index)
    {
        if ( ! $this->validIndex($index)) {
            throw new OutOfRangeException();
        }

        $value = array_splice($this->array, $index, 1, null)[0];
        $this->checkCapacity();

        return $value;
    }

    public function reverse()
    {
        $this->array = array_reverse($this->array);
    }

    /**
     * @return self<TValue>
     */
    public function reversed(): Sequence
    {
        return new self(array_reverse($this->array));
    }

    /**
     * Converts negative or large rotations into the minimum positive number
     * of rotations required to rotate the sequence by a given $r.
     */
    private function normalizeRotations(int $r)
    {
        $n = count($this);

        if ($n < 2) return 0;
        if ($r < 0) return $n - (abs($r) % $n);

        return $r % $n;
    }

    public function rotate(int $rotations)
    {
        for ($r = $this->normalizeRotations($rotations); $r > 0; $r--) {
            array_push($this->array, array_shift($this->array));
        }
    }

    public function set(int $index, $value)
    {
        if ( ! $this->validIndex($index)) {
            throw new OutOfRangeException();
        }

        $this->array[$index] = $value;
    }

    /**
     * @return TValue
     */
    public function shift()
    {
        if ($this->isEmpty()) {
            throw new UnderflowException();
        }

        $value = array_shift($this->array);
        $this->checkCapacity();

        return $value;
    }

    /**
     * @return self<TValue>
     */
    public function slice(int $offset, int $length = null): Sequence
    {
        if (func_num_args() === 1) {
            $length = count($this);
        }

        return new self(array_slice($this->array, $offset, $length));
    }

    public function sort(callable $comparator = null)
    {
        if ($comparator) {
            usort($this->array, $comparator);
        } else {
            sort($this->array);
        }
    }

    /**
     * @return self<TValue>
     */
    public function sorted(callable $comparator = null): Sequence
    {
        $copy = $this->copy();
        $copy->sort($comparator);
        return $copy;
    }

    /**
     * @return int|float
     */
    public function sum()
    {
        return array_sum($this->array);
    }

    public function unshift(...$values)
    {
        if ($values) {
            $this->array = array_merge($values, $this->array);
            $this->checkCapacity();
        }
    }

    private function validIndex(int $index)
    {
        return $index >= 0 && $index < count($this);
    }

    public function getIterator()
    {
        foreach ($this->array as $value) {
            yield $value;
        }
    }

    public function clear()
    {
        $this->array = [];
        $this->capacity = self::MIN_CAPACITY;
    }

    public function offsetSet($offset, $value)
    {
        if ($offset === null) {
            $this->push($value);
        } else {
            $this->set($offset, $value);
        }
    }

    /**
     * @return TValue
     */
    public function &offsetGet($offset)
    {
        if ( ! $this->validIndex($offset)) {
            throw new OutOfRangeException();
        }

        return $this->array[$offset];
    }

    public function offsetUnset($offset)
    {
        if (is_integer($offset) && $this->validIndex($offset)) {
            $this->remove($offset);
        }
    }

    /**
     * @return bool
     */
    public function offsetExists($offset)
    {
        return is_integer($offset)
            && $this->validIndex($offset)
            && $this->get($offset) !== null;
    }
}
