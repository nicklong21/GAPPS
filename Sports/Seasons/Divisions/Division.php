<?php
namespace ElevenFingersCore\GAPPS\Sports\Seasons\Divisions;

use ElevenFingersCore\Database\DatabaseConnectorPDO;
use ElevenFingersCore\Utilities\MessageTrait;
use ElevenFingersCore\GAPPS\InitializeTrait;

class Division{
    use MessageTrait;
    use InitializeTrait;

    function __construct(array $DATA){
        $this->initialize($DATA);
    }

    public function getTitle():?string{
        return $this->DATA['title']??'';
    }

    public function getRosterLimit():int{   
        return $this->DATA['roster_limit']??0;
    }
    public function getAESPercentage():int{ 
        return $this->DATA['aes_percentage']??0;
    }

    public function getAESMax():int{
        return $this->DATA['aes_max']??0;
    }

}


?>