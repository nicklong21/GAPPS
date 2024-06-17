<?php
namespace ElevenFingersCore\GAPPS\Sports;

use ElevenFingersCore\Database\DatabaseConnectorPDO;
use ElevenFingersCore\Utilities\MessageTrait;
use ElevenFingersCore\Utilities\InitializeTrait;

class Division{
    use MessageTrait;
    use InitializeTrait;
    protected $database;
    protected $id = 0;
    protected $DATA;

    static $db_table = 'sports_divisions';
    static $template = array(
        'id'=>0,
        'title'=> '',
        'season_id'=>0,
        'sport_id'=>0,
        'roster_limit'=>12,
        'aes_percentage'=>10,
        'aes_max'=>0
    );

    function __construct(DatabaseConnectorPDO $DB, ?int $id = 0, ?array $DATA = array()){
        $this->database = $DB;
        $this->initialize($id,$DATA);
    }

    public function getTitle():?string{
        return $this->DATA['title'];
    }

    public function getRosterLimit():int{   
        return $this->DATA['roster_limit'];
    }
    public function getAESPercentage():int{ 
        return $this->DATA['aes_percentage'];
    }

    public function getAESMax():int{
        return $this->DATA['aes_max'];
    }

    /** @return Division[] */
    public static function getSeasonDivisions(DatabaseConnectorPDO $DB, int $season_id):array{
        $Divisions = array();
        $data = $DB->getArrayListByKey(static::$db_table,array('season_id'=>$season_id),'title');
        foreach($data AS $DATA){
            $Divisions[] = new static($DB, null, $DATA);
        }
        return $Divisions;
    }

}


?>