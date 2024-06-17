<?php
namespace ElevenFingersCore\GAPPS\Sports;

use ElevenFingersCore\Database\DatabaseConnectorPDO;
use ElevenFingersCore\GAPPS\Schools\School;
use ElevenFingersCore\Utilities\MessageTrait;
use ElevenFingersCore\Utilities\InitializeTrait;

class Venue{
    use MessageTrait;
    use InitializeTrait;
    protected $database;
    protected $id = 0;
    protected $DATA;
    protected $School;
    static $db_table = 'venues';
    static $db_xref = 'venues_sports';
    static $template = array('id'=>0,
    'school_id'=>0,
    'title'=>'',
    'address1'=>'',
    'address2'=>'',
    'city'=>'',
    'state'=>'',
    'zip'=>'',
    'instructions'=>'',
    'googlemap'=>'',
    'publish'=>0,
    'is_active'=>0,
);

    function __construct(DatabaseConnectorPDO $DB, ?int $id = 0, ?array $DATA = array()){
        $this->database = $DB;
        $this->initialize($id,$DATA);
    }

    public function getTitle():string{
        return $this->DATA['title'];
    }

    public function getSchoolID(){
        return $this->DATA['school_id'];
    }

    public function getSchool():?School{
        if(!empty($this->School) && !empty($this->DATA['school_id'])){
            $this->School = new School($this->database, $this->DATA['school_id']);
        }
        return $this->School;
    }

    public function setSchool(School $School){  
        $this->School = $School;
    }

    public function isPublish():bool{
        return $this->DATA['publish']?true:false;
    }

    public function isActive():bool{
        return $this->DATA['is_active']?true:false;
    }

 /** @return Venue[] */
    public static function getVenues(DatabaseConnectorPDO $DB, ?array $filter = array()):array{
        $Venues = array();
        if(isset($filter['sport_id'])){
            $venue_ids = $DB->getResultListByKey(static::$db_xref, array('sport_id'=>$filter['sport_id']),'venue_id');
            unset($filter['sport_id']);
            if(count($venue_ids) > 0){
                $filter['id'] = array('IN'=>$venue_ids);
            }else{
                $filter['id'] = 0;
            }
        }
        $venue_data = $DB->getArrayListByKey(static::$db_table,$filter,'title');
        foreach($venue_data AS $data){
            $Venues[] = new static($DB, 0, $data);
        }
        return $Venues;
    }

}


?>