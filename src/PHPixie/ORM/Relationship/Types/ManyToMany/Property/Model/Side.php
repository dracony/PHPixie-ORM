<?php

namespace PHPixie\ORM\Relationship\Types\OneToMany\Property\Model;

class Side extends \PHPixie\ORM\Relaionship\Type\Property\Model
{
    public function load()
    {
        return $this->handler->loadProperty($this->side, $this->model);
    }

    public function add($items)
    {
        list($left, $right) = $this->getSides($items);
        $plan = $this->handler->linkPlan($this->config, $left, $right);
        $plan->execute();
        $this->handler->linkProperties($this->config, $left, $right);
    }

    public function remove($items)
    {
        list($left, $right) = $this->getSides($items);
        $plan = $this->handler->unlinkPlan($this->config, $left, $right);
        $plan->execute();
        $this->handler->unlinkProperties($this->config, $left, $right);
    }
    
    public function removeAll()
    {
        list($left, $right) = $this->getSides(null);
        $plan = $this->handler->unlinkPlan($this->config, $left, $right);
        $plan->execute();
        $this->value->removeAll();
    }
    
    protected function getSides($opposing)
    {
        if ($this->side-> type() === 'right')
            return ($this->model, $opposing);
        
        return ($opposing, $this->model);
    }

}
