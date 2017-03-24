<?php

namespace PHPixie\ORM\Wrappers\Model;

abstract class Entity implements \PHPixie\ORM\Models\Model\Entity
{
    /**
     * @type \PHPixie\ORM\Drivers\Driver\PDO\Entity|\PHPixie\ORM\Drivers\Driver\Mongo\Entity
     */
    protected $entity;
    protected $hidden = [];
    
    public function __construct($entity)
    {
        $this->entity = $entity;
    }
    
    public function modelName()
    {
        return $this->entity->modelName();
    }
    
    public function asObject($recursive = false, $filter = true)
    {
        $data = $this->entity->asObject($recursive, $filter);

        if ($filter) {
            foreach ($this->hidden as $key) {
                if (isset($data->{$key})) {
                    unset($data->{$key});
                }
            }
        }

        return $data;
    }
    
    public function relationshipPropertyNames()
    {
        return $this->entity->relationshipPropertyNames();
    }
    
    public function getRelationshipProperty($relationship, $createMissing = true)
    {
        return $this->entity->getRelationshipProperty($relationship, $createMissing);
    }
    
    public function data()
    {
        return $this->entity->data();
    }
    
    public function getField($name, $default = null)
    {
        return $this->entity->getField($name, $default);
    }
    
    public function getRequiredField($name)
    {
        return $this->entity->getRequiredField($name);
    }
    
    public function setField($key, $value)
    {
        $this->entity->setField($key, $value);
        return $this;
    }
    
    public function __get($name)
    {
        return $this->entity->__get($name);
    }
    
    public function __set($name, $value)
    {
        $this->entity->__set($name, $value);
    }
    
    public function __call($method, $params)
    {
        return $this->entity->__call($method, $params);
    }
}
