<?php

class MockRegionModel implements Iterator
{
    private $iterator;

    public function __construct($regions)
    {
        $this->iterator = new \ArrayIterator($regions);
    }

    public function getCollection()
    {
        return $this;
    }

    public function addCountryFilter()
    {
        return $this;
    }

    public function getSize()
    {
        return count($this->iterator);
    }

    public function current()
    {
        return $this->iterator->current();
    }

    public function next()
    {
        $this->iterator->next();
    }

    public function key()
    {
        return $this->iterator->key();
    }

    public function valid()
    {
        return $this->iterator->valid();
    }

    public function rewind()
    {
        $this->iterator->rewind();
    }
}
