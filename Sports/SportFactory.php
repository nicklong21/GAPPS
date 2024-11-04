<?php
namespace ElevenFingersCore\GAPPS\Sports;
use ElevenFingersCore\Database\DatabaseConnectorPDO;
use ElevenFingersCore\GAPPS\Sports\Rosters\RosterFactory;
use ElevenFingersCore\GAPPS\Sports\Seasons\SeasonFactory;
use ElevenFingersCore\GAPPS\Sports\Games\GameFactory;

class SportFactory{
    protected $database;
    protected $Registry;
    protected $Sports;
    static $db_table = 'sports';

    static $schema = [
        'id'=>0,
        'agroup'=>'Sports',
        'title'=>'',
        'type'=>'Fall Sports',
        'zgroup'=>'High School',
        'status'=>'active',
        'slug'=>'',
    ];

    function __construct(DatabaseConnectorPDO $DB, SportRegistry $Registry){
        $this->database = $DB;
        $this->Registry = $Registry;
    }
    public function getSportByName(string $name):?Sport{
        $Sport = null;
        $DATA = $this->database->getArrayByKey(static::$db_table, array('name'=>$name));
        if($DATA){
           $Sport = $this->getSport(null,$DATA);
        }
        return $Sport;
    }
    public function getSportBySlug(string $slug):?Sport{
        $Sport = null;
        $DATA = $this->database->getArrayByKey(static::$db_table, array('slug'=>$slug));
        if($DATA){
           $Sport = $this->getSport(null,$DATA);
        }
        return $Sport;
    }

    public function getSportBySeasonID(int $season_id):?Sport{
        $SeasonFactory = $this->getSeasonFactory();
        $Season = $SeasonFactory->getSeason($season_id);
        $sport_id = $Season->getSportID();
        $Sport = $this->getSport($sport_id);
        return $Sport;
    }
    public function getSport(?int $id = 0, ?array $DATA = array()):Sport{
        if(!empty($id)){
            $DATA = $this->database->getArrayByKey(static::$db_table,array('id'=>$id));
        }

        $sport_data = [];
        foreach(static::$schema AS $key=>$val){
            $sport_data[$key] = $DATA[$key]?? $val;
        }
        $sport_slug = $sport_data['slug']??'sport';
        $dependencies = $this->Registry::getDependencies($sport_slug);
        $SportClass = $dependencies['sport'];
        $Sport = new $SportClass($sport_data); 
        $RosterFactory = $this->getRosterFactory($sport_slug);
        $Sport->setRosterFactory($RosterFactory);
        $GameFactory = $this->getGameFactory($sport_slug);
        $Sport->setGameFactory($GameFactory);
        $SeasonFactory = $this->getSeasonFactory($sport_slug);
        $Sport->setSeasonFactory($SeasonFactory);
         
        return $Sport;
    }

    public function getSportsByIDs(array $ids):array{
        $filter = ['id'=>array('IN'=>$ids)];
        $sport_data = $this->database->getArrayListByKey(static::$db_table,$filter, 'title');
        $Sports = array();
        foreach($sport_data AS $DATA){
            $Sports[] = $this->getSport(0,$DATA);
        }
        return $Sports;
    }
    public function getSports(null|string|array $group = null, null|string|array $age_group = null, null|string|array $semester = null, ?string $status = 'active'):array{
        $filter = [];
        if(!empty($group)){
            if(is_array($group)){
                $filter['agroup'] = ['IN'=>$group];
            }else if (strtoupper($group) !=='ALL'){
                $filter['agroup'] = $group;
            }
        }else{
            $group = 'Sports';
        }

        if(!empty($semester)){
            if(is_array($semester)){
                $filter['type'] = ['IN'=>$semester];
            }else{
                $filter['type'] = $semester;
            }
        }
        
        if(!empty($age_group)){
            if(is_array($age_group)){
                $filter['zgroup'] = ['IN'=>$age_group];
            }else{
                $filter['zgroup'] = $age_group;
            }
        }
        if(!empty($status)){
            $filter['status'] = 'active';
        }
        $sport_data = $this->database->getArrayListByKey(static::$db_table,$filter, 'title');
        $Sports = array();
        foreach($sport_data AS $DATA){
            $Sports[] = $this->getSport(0,$DATA);
        }
        return $Sports;
    }

    public function getSeasonFactory(?string $sport_slug = 'sport'):SeasonFactory{
        $dependencies = $this->Registry::getDependencies($sport_slug);
        $SeasonFactory = new SeasonFactory($this->database, $dependencies, $this);
        return $SeasonFactory;
    }
    public function getRosterFactory(?string $sport_slug = 'sport'):RosterFactory{
        $dependencies = $this->Registry::getDependencies($sport_slug);
        $RosterFactory = new RosterFactory($this->database, $dependencies);
        return $RosterFactory;
    }
    public function getGameFactory(?string $sport_slug = 'sport'):GameFactory{
        $dependencies = $this->Registry::getDependencies($sport_slug);
        $GameFactory = new GameFactory($this->database, $dependencies);
        return $GameFactory;
    }
}