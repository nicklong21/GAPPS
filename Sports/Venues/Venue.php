<?php
namespace ElevenFingersCore\GAPPS\Sports\Venues;

use ElevenFingersCore\Database\DatabaseConnectorPDO;
use ElevenFingersCore\GAPPS\Schools\School;
use ElevenFingersCore\Utilities\MessageTrait;
use ElevenFingersCore\GAPPS\InitializeTrait;
use ElevenFingersCore\Utilities\SortableObject;

class Venue{
    use MessageTrait;
    use InitializeTrait;
    protected $event_ids;

    function __construct(array $DATA){
        $this->initialize($DATA);
    }

    public function getTitle():string{
        return $this->DATA['title']??'';
    }
    public function getSchoolID(){
        return $this->DATA['school_id'];
    }

    public function isPublish():bool{
        return $this->DATA['publish']?true:false;
    }

    public function isActive():bool{
        return $this->DATA['is_active']?true:false;
    }

    public function getEventIDs():array{
        
        return $this->event_ids??[];
    }

    public function setEventIDs(array $event_ids){
        $this->event_ids = $event_ids;
    }

    public function getAddressString():string{
        $DATA = $this->getDATA();
        $address = '';
        if(!empty($DATA)){
            $address = trim($DATA['address1']).'<br/>';
            if(!empty(trim($DATA['address2']))){
                $address .= trim($DATA['address2']).'<br/>';
            }
            $address .= $DATA['city'].', '.$DATA['state'].' '.$DATA['zip'];
        }
        return $address;
    }

    public function getInstructionsString():string{
        return $this->DATA['instructions']??'';
    }

    public function getGoogleMapString():string{
        return $this->DATA['googlemap']??'';
    }

}

