<?php

namespace \PHPixie\ORM\Planners;

abstract class StrategyPlanner
{
    protected $strategies = array();

    protected function strategy($name)
    {
        if (!isset($this->strategies[$name]))
            $this->strategies[$name] = $this->buildStrategy($name);

        return $this->strategies[$name];
    }

    abstract protected function buildStrategy($name);
}
