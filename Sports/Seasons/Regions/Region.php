<?php
namespace ElevenFingersCore\GAPPS\Sports\Seasons\Regions;
use ElevenFingersCore\Utilities\MessageTrait;
use ElevenFingersCore\GAPPS\InitializeTrait;
class Region{
    use MessageTrait;
    use InitializeTrait;

    function __construct(array $DATA){
        $this->initialize($DATA);
    }

    public function getTitle():?string{
        return $this->DATA['title']??'';
    }

}
