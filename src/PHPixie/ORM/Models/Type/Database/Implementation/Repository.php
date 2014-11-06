<?php

namespace PHPixie\ORM\Models\Type\Database\Implementation;

abstract class Repository implements \PHPixie\ORM\Models\Type\Database\Repository
{
    protected $model;
    protected $database;
    protected $config;
    
    public function __construct($model, $database, $config)
    {
        $this->model = $model;
        $this->database = $database;
        $this->config = $config;
    }
    
    public function config()
    {
        return $this->config;
    }
    
    public function modelName()
    {
        return $this->config->modelName;
    }
    
    public function query()
    {
        return $this->model->query($this->modelName());
    }
    
    public function create()
    {
        return $this->entity();
    }
    
    public function load($data)
    {
        return $this->entity(false, $data);
    }
    
    protected function entity($isNew = true, $data = null)
    {
        $modelName = $this->modelName();
        $data = $this->buildData($data);
        return $this->model->entity($modelName, $isNew, $data);
    }

    public function connection()
    {
        return $this->database->connection($this->config->connection);
    }
    
    public function delete($entity)
    {
        if ($entity->isDeleted())
            throw new \PHPixie\ORM\Exception\Entity("This model has already been deleted.");

        if (!$entity->isNew()) {
            $this->query()->in($entity)->delete();
        }

        $entity->setIsDeleted(true);
    }

    public function save($entity)
    {
        if ($entity->isDeleted())
            throw new \PHPixie\ORM\Exception\Entity("Deleted models cannot be saved.");
        
        $data = $entity->data();
        $idField = $this->config->idField;
        
        if($entity->isNew()){
            
            $this->insertEntityData($data);
            
            $id = $this->connection()->insertId();
            $entity->setField($idField, $id);
            $entity->setId($id);
            $entity->setIsNew(false);
        } else {
            $this->updateEntityData($entity->id(), $data);
        }

        $data->setCurrentAsOriginal();
    }
    
    protected function insertEntityData($data)
    {
        $this->databaseInsertQuery()
            ->data((array) $data->data())
            ->execute();
    }

    public function databaseSelectQuery()
    {
        return $this->setQuerySource($this->connection()->selectQuery());
    }
    
    public function databaseUpdateQuery()
    {
        return $this->setQuerySource($this->connection()->updateQuery());
    }
    
    public function databaseDeleteQuery()
    {
        return $this->setQuerySource($this->connection()->deleteQuery());
    }
    
    public function databaseInsertQuery()
    {
        return $this->setQuerySource($this->connection()->insertQuery());
    }
    
    public function databaseCountQuery()
    {
        return $this->setQuerySource($this->connection()->countQuery());
    }
    
    abstract protected function setQuerySource($query);
    abstract protected function updateEntityData($id, $data);

}
