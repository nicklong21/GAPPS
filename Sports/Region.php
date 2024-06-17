<?php
namespace ElevenFingersCore\GAPPS\Sports;

use ElevenFingersCore\Database\DatabaseConnectorPDO;
use ElevenFingersCore\Utilities\MessageTrait;
use ElevenFingersCore\Utilities\InitializeTrait;

class Region{
    use MessageTrait;
    use InitializeTrait;
    protected $database;
    protected $id = 0;
    protected $DATA;

    static $db_table = 'sports_regions';
    static $template = array(
        'id'=>0,
        'title'=> '',
        'season_id'=>0,
        'sport_id'=>0,
    );

    function __construct(DatabaseConnectorPDO $DB, ?int $id = 0, ?array $DATA = array()){
        $this->database = $DB;
        $this->initialize($id,$DATA);
    }

    public function getTitle():?string{
        return $this->DATA['title'];
    }

    /** @return Region[] */
    public static function getSeasonRegions(DatabaseConnectorPDO $DB, int $season_id):array{
        $Regions = array();
        $data = $DB->getArrayListByKey(static::$db_table,array('season_id'=>$season_id),'title');
        foreach($data AS $DATA){
            $Regions[] = new static($DB, null, $DATA);
        }
        return $Regions;
    }

}


?>