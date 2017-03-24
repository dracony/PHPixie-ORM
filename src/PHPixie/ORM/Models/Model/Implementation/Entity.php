<?php

namespace PHPixie\ORM\Models\Model\Implementation;

abstract class Entity implements \PHPixie\ORM\Models\Model\Entity
{
    protected $entityPropertyMap;
    protected $data;
    protected $config;
    protected $relationshipProperties;

    public function __construct($entityPropertyMap, $config, $data)
    {
        $this->entityPropertyMap = $entityPropertyMap;
        $this->config = $config;
        $this->data = $data;
    }
    
    public function modelName()
    {
        return $this->config->model;
    }
    
    public function data()
    {
        return $this->data;
    }
    
    public function getField($name, $default = null)
    {
        return $this->data->get($name, $default);
    }
    
    public function getRequiredField($name)
    {
        return $this->data->getRequired($name);
    }
    
    public function setField($name, $value)
    {
        $this->data->set($name, $value);
        return $this;
    }
    
    public function relationshipPropertyNames()
    {
        $this->requirePropertyNames();
        return $this->relationshipProperties;
    }
    
    public function getRelationshipProperty($name, $createMissing = true)
    {
        $this->requirePropertyNames();
        
        if (!array_key_exists($name, $this->relationshipProperties)) {
            throw new \PHPixie\ORM\Exception\Relationship("Relationship property '$name' is not defined for '{$this->modelName()}'");
        }
        
        return $this->relationshipProperty($name, $createMissing);
    }
    
    protected function relationshipProperty($name, $createMissing = true)
    {
        $property = $this->relationshipProperties[$name];
        
        if($property === null && $createMissing) {
            $property = $this->entityPropertyMap->property($this, $name);
            $this->relationshipProperties[$name] = $property;
        }
        
        return $this->relationshipProperties[$name];
    }
    
    public function asObject($recursive = false, $filter = false)
    {
        $this->requirePropertyNames();

        /**
         * @var $data \PHPixie\ORM\Data\Types\Map
         */
        $data = $this->data->data();

        if($recursive) {
            foreach($this->relationshipProperties as $name => $property) {
                if($property instanceof \PHPixie\ORM\Relationships\Relationship\Property\Entity\Data && $property->isLoaded()) {
                    /**
                     * @var $property \PHPixie\ORM\Relationships\Type\OneTo\Type\One\Property\Entity
                     */
                    $data->$name = $property->asData($recursive, $filter);
                }
            }
        }

        return $data;
    }
    
    public function __get($name)
    {
        $this->requirePropertyNames();
        
        if (array_key_exists($name, $this->relationshipProperties)) {
            return $this->relationshipProperty($name);
        }
        
        return $this->getRequiredField($name);
    }
    
    public function __call($name, $params)
    {
        $property = $this->getRelationshipProperty($name);
        return call_user_func_array($property, $params);
    }
    
    public function __set($name, $value)
    {
        $this->setField($name, $value);
    }
    
    protected function requirePropertyNames()
    {
        if ($this->relationshipProperties === null) {
            $propertyNames = $this->entityPropertyMap->getPropertyNames($this->modelName());
            $this->relationshipProperties = array_fill_keys($propertyNames, null);
        }
    }
    
}
