<?php

namespace PHPixie\ORM\Model\Data\Data;

class Map extends \PHPixie\ORM\Model\Data\Data
{

    protected function currentModelData()
    {
        return (object) $this->model->dataProperties();
    }

    public function diff()
    {
        $originalData = array();
        if ($this->originalData !== null)
            $originalData = get_object_vars($this->originalData);

        $currentData = get_object_vars($this->currentData());
        $unset = array_diff(array_keys($originalData), array_keys($currentData));
        $data = array_fill_keys($unset, null);

        foreach ($currentData as $key => $value) {
            if (!array_key_exists($key, $originalData) || $value !== $originalData[$key])
                $data[$key] = $value;
        }

        return $data;
    }

    public function properties()
    {
        return get_object_vars($this->currentData());
    }

}
