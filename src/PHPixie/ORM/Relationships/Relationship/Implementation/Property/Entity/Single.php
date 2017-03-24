<?php

namespace PHPixie\ORM\Relationships\Relationship\Implementation\Property\Entity;

abstract class Single extends \PHPixie\ORM\Relationships\Relationship\Implementation\Property\Entity
                      implements \PHPixie\ORM\Relationships\Relationship\Property\Entity\Data
{
    public function value()
    {
        $value = parent::value();

        if ($value !== null && $value->isDeleted()) {
            $this->setValue(null);
            return null;
        }

        return $value;
    }

    public function asData($recursive = false, $filter = true)
    {
        $value = $this->value();
        if ($value === null)
            return null;

        return $value->asObject($recursive, $filter);
    }

    public function set($value)
    {
        if($value === null) {
            return $this->remove();
        }

        if($value instanceof \PHPixie\ORM\Models\Type\Database\Entity && $value->isDeleted()) {
            return $this->remove();
        }

        $this->processSet($value);
        return $this;
    }

    abstract protected function processSet($value);
}
