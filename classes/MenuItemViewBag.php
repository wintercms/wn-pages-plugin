<?php namespace Winter\Pages\Classes;

/**
 * The view bag data holder for a menu item.
 *
 * Provides the ability to set default values for the view bag but still allow a user to add their
 * own view bag properties.
 *
 * @package winter\pages
 * @author Ben Thomson
 */
class MenuItemViewBag implements \ArrayAccess, \Iterator, \Countable
{
    /**
     * Data holder.
     */
    private array $data = [];

    /**
     * Keys index, based off the available data.
     */
    private array $keys = [];

    /**
     * Current position in the array, for tracking.
     */
    private int $position = 0;

    /**
     * Constructor.
     *
     * Populates the view bag with the given data, ensuring that the default values are set and
     * available at all times.
     */
    public function __construct(MenuItemViewBag|array $data = [])
    {
        if ($data instanceof MenuItemViewBag) {
            $this->data = $data->toArray();
        } else {
            $this->data = array_merge([
                'isHidden' => false,
                'isExternal' => false,
                'cssClass' => '',
            ], $data);
        }

        $this->keys = array_keys($this->data);
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return count($this->keys);
    }

    /**
     * @inheritDoc
     */
    public function &current(): mixed
    {
        return $this->data[$this->keys[$this->position]];
    }

    /**
     * @inheritDoc
     */
    public function key(): mixed
    {
        return $this->keys[$this->position];
    }

    /**
     * @inheritDoc
     */
    public function rewind(): void
    {
        $this->position = 0;
    }

    /**
     * @inheritDoc
     */
    public function next(): void
    {
        ++$this->position;
    }

    /**
     * @inheritDoc
     */
    public function valid(): bool
    {
        return array_key_exists($this->position, $this->keys);
    }

    /**
     * @inheritDoc
     */
    public function offsetExists(mixed $offset): bool
    {
        return in_array($offset, $this->keys);
    }

    /**
     * @inheritDoc
     */
    public function &offsetGet(mixed $offset): mixed
    {
        return $this->data[$offset];
    }

    /**
     * @inheritDoc
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (is_null($offset)) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }

        $this->keys = array_keys($this->data);
    }

    /**
     * @inheritDoc
     */
    public function offsetUnset(mixed $offset): void
    {
        if ($offset === 'isHidden') {
            $this->data['isHidden'] = false;
        } elseif ($offset === 'isExternal') {
            $this->data['isExternal'] = false;
        } elseif ($offset === 'cssClass') {
            $this->data['cssClass'] = '';
        } else {
            unset($this->data[$offset]);
        }
    }

    /**
     * Serializes the view bag data.
     */
    public function __serialize(): array
    {
        return $this->data;
    }

    /**
     * Returns an array of the current view bag data.
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Unserializes the view bag data.
     */
    public function __unserialize(array $data): void
    {
        $this->data = $data;
        $this->keys = array_keys($this->data);
    }

    /**
     * Renders a JSON representation of the view bag data.
     */
    public function __toString(): string
    {
        return json_encode($this->data);
    }
}
