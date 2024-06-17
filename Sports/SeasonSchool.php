<?php
namespace ElevenFingersCore\GAPPS\Sports;

use ElevenFingersCore\Database\DatabaseConnectorPDO;
use ElevenFingersCore\Utilities\MessageTrait;
use ElevenFingersCore\Utilities\InitializeTrait;
use ElevenFingersCore\GAPPS\Schools\School;
use ElevenFingersCore\Utilities\SortableObject;

class SeasonSchool{
    use MessageTrait;
    use InitializeTrait;
    protected $database;
    protected $id = 0;
    protected $DATA;
    protected $Season;
    protected $School;
    protected $Roster;
    protected $Games;
    static $db_table = 'school_seasons';
    static $template = array(
        'id'=>0,
        'season_id'=>0,
        'school_id'=>0,
        'region_id'=>0,
        'division_id'=>0,
        'status'=>'',
        'division_flag'=>NULL,
       );

    function __construct(DatabaseConnectorPDO $DB, ?int $id = 0, ?array $DATA = array()){
        $this->database = $DB;
        $this->initialize($id,$DATA);
    }

    public function getRoster():Roster{
        if(empty($this->Roster)){
            $Season = $this->getSeason();
            $this->Roster = $Season->getRoster($this->DATA['roster_id']);
        }
        return $this->Roster;
    }

    public function setRoster(Roster $Roster){
        $this->Roster = $Roster;
        $this->DATA['roster_id'] = $Roster->getID();
    
    }

/** @return Game[] */
    public function getGames(?string $by_status = null):array{
        $Games = array();
        if(empty($this->Games)){
            $Season = $this->getSeason();
            $this->Games = $Season->getGames(null, $this->getSchoolID());
        }
        if(!empty($by_status)){
            foreach($this->Games AS $Game){
                if($Game->getStatus() == $by_status){
                    $Games[] = $Game;
                }
            }
        }else{
            $Games = $this->Games;
        }
        return $Games;
    }

    public function setGames(array $Games){
        $this->Games = $Games;
    }

    public function getSchool():School{
        if(empty($this->School)){
            $this->School = new School($this->database, $this->DATA['school_id']);
        }
        return $this->School;
    }

    public function getSchoolTitle():string{
        $School = $this->getSchool();
        $title = $School->getTitle();
        return $title;
    }

    public function setSchool(School $School){
        $this->School = $School;
        $this->DATA['school_id'] = $School->getID();
    }

    public function getSeason():Season{
        if(empty($this->Season)){
            $this->Season = new Season($this->database, $this->DATA['season_id']);
        }
        return $this->Season;
    }

    public function setSeason(Season $Season){
        $this->Season = $Season;
        $this->DATA['season_id'] = $Season->getID();
    }

    public function getSeasonID():int{
        return $this->DATA['season_id'];
    }

    public function getSchoolID():int{
        return $this->DATA['school_id'];
    }

    public function getRegionID():int{  
        return $this->DATA['region_id'];
    }

    public function getDivisionID():int{
        return $this->DATA['division_id'];
    }

    public function getStatus():?string{
        return $this->DATA['status'];
    }
/** @return SeasonSchool[] */
    public static function getSeasonSchools(DatabaseConnectorPDO $DB, int $season_id):array{
        $SeasonSchools = array();
        $data = $DB->getArrayListByKey(static::$db_table, array('season_id'=>$season_id));
        foreach($data AS $DATA){
            $SeasonSchools[] = new static($DB, null, $DATA);
        }
        $Sortable = new SortableObject('getSchoolTitle');
        $Sortable->Sort($SeasonSchools);
        return $SeasonSchools;
    }

}


?>