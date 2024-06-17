<?php
namespace ElevenFingersCore\GAPPS\Sports;

use ElevenFingersCore\Database\DatabaseConnectorPDO;
use ElevenFingersCore\Utilities\MessageTrait;
use ElevenFingersCore\Utilities\InitializeTrait;
use RuntimeException;

class Sport{
    use MessageTrait;
    use InitializeTrait;
    protected $database;
    protected $id = 0;
    protected $DATA;
    protected $Seasons = array();
    static $db_table = 'sports';
    static $template = array(
        'id'=>0,
        'agroup'=>'Sports',
        'title'=>'',
        'type'=>'Fall Sports',
        'zgroup'=>'High School',
        'status'=>'active',
        'slug'=>'',
        'sport_classname'=>'ElevenFingersCore\GAPPS\Sports\Sport',
        'roster_classname'=>'ElevenFingersCore\GAPPS\Sports\Roster',
        'game_classname'=>'ElevenFingersCore\GAPPS\Sports\Game',
    );

    protected $game_types = array(
        'regular'=>'Non-Region Game',
        'regional'=>'Regional Game',
        'semi-final'=>'State Playoff Game',
        'championship'=>'State Championship Game',
    );

    protected $game_status = array(
        'Scheduled',
        'Played',
        'Canceled',
        'Completed',
        'Not Completed',);

    function __construct(DatabaseConnectorPDO $DB, ?int $id = 0, ?array $DATA = array()){
        $this->database = $DB;
        $this->initialize($id,$DATA);
    }

    public static function findSport(DatabaseConnectorPDO $DB, string $slug):?Sport{
        $Sport = null;
        $DATA = $DB->getArrayByKey(static::$db_table, array('slug'=>$slug));
        if($DATA){
            $classname = isset($DATA['sport_classname'])?$DATA['sport_classname']:'ElevenFingersCore\GAPPS\Sports\Sport';
            $Sport = new $classname($DB, null, $DATA);
        }
        return $Sport;
    }

    public static function getSport(DatabaseConnectorPDO $DB, ?int $id = 0, ?array $DATA = array() ):Sport{
        if(!empty($id)){
            $DATA = $DB->getArrayByKey(static::$db_table,array('id'=>$id));
        }
        if(!empty($DATA)){
            $SportClass = $DATA['sport_classname'];
            if(class_exists($SportClass) && is_a($SportClass, 'ElevenFingersCore\GAPPS\Sports\Sport', true)){
                $Sport = new $SportClass($DB, null, $DATA);
            }else{
                throw new RuntimeException($SportClass.' is not a valid Sport Classname');
            }
        }else{
            throw new RuntimeException('Cannot find Sport from ID: '.$id);
        }
        return $Sport;
    }

    public function getID():int{
        return $this->id;
    }

    public function getTitle():?string{
        return $this->DATA['title'];
    }

    public function getAGroup():?string{
        return $this->DATA['agroup'];
    }

    public function getType():?string{
        return $this->DATA['type'];
    }

    public function getZGroup():?string{
        return $this->DATA['zgroup'];
    }

    public function getStatus():?string{
        return $this->DATA['status'];
    }

    public function getSlug():?string{
        return $this->DATA['slug'];
    }

    public function getCurrentSeason():Season{
        if(empty($this->Seasons)){
            $sql = 'SELECT * FROM '.Season::$db_table.' WHERE sport_id = :sport_id ORDER BY date_start DESC LIMIT 0,1';
            $params = array(':sport_id'=>$this->id);
            $Data = $this->database->getResultArray($sql,$params);
            $Season = new Season($this->database, null, $Data);
        }else{
            $Season = $this->Seasons[0];
        }
        return $Season;
    }

    /** @return Season[] */
    public function getSeasons():array{
        if(empty($this->Seasons)){
            $Seasons = Season::getSeasons($this->database, array('sport_id'=>$this->id),'date_start DESC');
            /** @var Season $Season */
            foreach($Seasons AS $Season){
                $Season->setSport($this);
            }
            $this->Seasons = $Seasons;
        }
        return $this->Seasons;
    }

    public function getSportRoster(?int $id = 0, ?array $DATA = array(), ?array $filter = null):Roster{
        $roster_class = $this->getRosterClass();
        if(empty($id) && empty($DATA) && !empty($filter)){
            $Roster = $roster_class::findRoster($this->database, $filter);
        }
        if(empty($Roster)){
            $Roster = new $roster_class($this->database, $id, $DATA);
        }
        $RosterTable = $this->getRosterTable();
        $Roster->setRosterTable($RosterTable);
        return $Roster;
    }

    public function getRosterTable():RosterTable{
        $roster_class = $this->getRosterClass();
        $table_class = $roster_class.'Table';
        if(!class_exists($table_class)){
            $table_class = 'ElevenFingersCore\\GAPPS\\Sports\\RosterTable';
        }
        return new $table_class();
    }

    public function getSportGame(?int $id = 0, ?array $DATA = array()):Game{
        $game_class = $this->getGameClass();
        $Game = new $game_class($this->database, $id, $DATA);
        return $Game;
    }

    public function getRosterClass():string{
        return $this->DATA['roster_classname'];
    }

    public function getGameClass():string{
        return $this->DATA['game_classname'];
    }

    public function getGameTypes():array{
        return $this->game_types;
    }

    public function getGameStatus():array{
        return $this->game_status;
    }

/** @return Roster[] */
    public function getRosters($filter):array{
        $RosterClass = $this->getRosterClass();
        $Rosters = $RosterClass::getRosters($this->database, $filter);
        return $Rosters;
    }

    public function getGame(?int $id, ?array $DATA = array(), ?array $filter = null):Game{
        $GameClass = $this->getGameClass();
        if(empty($id) && empty($DATA) && !empty($filter)){
            $Games = $this->getGames($filter);
            if(!empty($Games)){
                $Game = $Games[0];
            }
        }
        if(empty($Game)){
            $Game = new $GameClass($this->database, $id, $DATA);
        }
        return $Game;
    }
/** @return Game[] */
    public function getGames($filter):array{
        $GameClass = $this->getGameClass();
        $Games = $GameClass::getGames($this->database, $filter);
        return $Games;
    }

    /** @return Venue[] */
    public function getSportVenues($filter):array{
        $filter['sport_id'] = $this->getID();
        return Venue::getVenues($this->database, $filter);
    }

    /** @return Sport[] */
    public static function getSports(DatabaseConnectorPDO $DB, ?array $filter = array(), null|string|array $order_by = null):array{
        $sport_data = $DB->getArrayListByKey(static::$db_table,$filter, $order_by);
        $Sports = array();
        foreach($sport_data AS $data){
            $Sports[] = static::getSport($DB,0,$data);
        }
        return $Sports;
    }

}
