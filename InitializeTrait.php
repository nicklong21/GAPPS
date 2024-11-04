<?php
namespace ElevenFingersCore\GAPPS;


Trait InitializeTrait{
    protected $DATA = [];

    protected $id;


    public function initialize(array $DATA){
        $this->DATA = array_merge($this->DATA,$DATA);
        $this->id = $this->DATA['id']??0;
    }

    public function getID():int{
        return $this->id;
    }

    public function getDATA():array{
        return $this->DATA;
    }
}