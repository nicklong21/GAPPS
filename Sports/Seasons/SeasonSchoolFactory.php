<?php
namespace ElevenFingersCore\GAPPS\Sports\Seasons;
use ElevenFingersCore\Database\DatabaseConnectorPDO;
use ElevenFingersCore\GAPPS\FactoryTrait;
use ElevenFingersCore\GAPPS\Schools\SchoolFactory;
use ElevenFingersCore\Utilities\MessageTrait;

class SeasonSchoolFactory{
    use FactoryTrait;
    use MessageTrait;
    protected $database;
    protected $dependencies;

    protected $SeasonFactory;
    protected $Seasons = [];
    protected $db_table = 'school_seasons';
    protected $schema = [
        'id'=>0,
        'season_id'=>0,
        'school_id'=>0,
        'region_id'=>0,
        'division_id'=>0,
        'status'=>'',
        'division_flag'=>NULL,
        'division_flag2'=>NULL,
    ];

    function __construct(DatabaseConnectorPDO $DB, array $dependencies, SeasonFactory $SeasonFactory){
        $this->database = $DB;
        $item_class = $dependencies['school'];
        $this->dependencies = $dependencies;
        $this->setItemClass($item_class);
        $this->SeasonFactory = $SeasonFactory;
    }

    public function getSeasonSchool(?int $id = null, ?array $DATA = array()):SeasonSchool{
        $SeasonSchool = $this->getItem($id, $DATA);
        $this->initializeSeasonSchool($SeasonSchool);
        return $SeasonSchool;
    }

    public function getSeasonSchoolsByID(?array $ids, array $rows = array()):array{
        if($ids){
            $rows = $this->database->getArrayListByKey($this->db_table,['id'=>['IN'=>$ids]]);
        }
        $SeasonSchools = [];
        foreach($rows AS $DATA){
            $SeasonSchools[] = $this->getSeasonSchool(null, $DATA);
        }
        return $SeasonSchools;
    }

    public function initializeSeasonSchool(SeasonSchool $SeasonSchool){
        $season_id = $SeasonSchool->getSeasonID();
        if(isset($this->Seasons[$season_id])){
            $Season = $this->Seasons[$season_id];
        }else{
            $Season = $this->SeasonFactory->getSeason($season_id);
        }
        $SeasonSchool->setSeason($Season);
    }

    public function getSchoolIDsByDivision(int $division_id):array{
        $school_ids = $this->database->getResultListByKey($this->db_table,['division_id'=>$division_id],'school_id');
        return $school_ids;
    }

    public function getSchoolIDsByRegion(int $region_id):array{
        $school_ids = $this->database->getResultListByKey($this->db_table,['region_id'=>$region_id],'school_id');
        return $school_ids;
    }

    public function getSchoolsBySeason(array|Season $Season, ?int $school_id = null):array{
        
        if(is_array($Season)){
            $season_ids = [];
            foreach($Season AS $ASeason){
                $this->Seasons[$ASeason->getID()] = $ASeason;
                $season_ids[] = $ASeason->getID();
            }
            $filter = array('season_id'=>['IN'=>$season_ids]);
        }else{
            $this->Seasons[$Season->getID()] = $Season;
            $filter = array('season_id'=>$Season->getID());
        }
        
        if(!empty($school_id)){
            $filter['school_id'] = $school_id;
        }
        $schools_data = $this->database->getArrayListByKey($this->db_table, $filter);
        
        $SeasonSchools = array();
        foreach($schools_data AS $DATA){
            $SeasonSchool = $this->getSeasonSchool(null, $DATA);
            $SeasonSchools[] = $SeasonSchool;
        }
        return $SeasonSchools;
    }

    public function updateSeasonSchools(Season $Season, $data):bool{
        $Schools = $this->getSchoolsBySeason($Season);
        $any_errors = false;
        foreach($Schools AS $School){
            $school_id = $School->getSchoolID();
            if(isset($data[$school_id])){
                $school_data = json_decode($data[$school_id],true);
                $success = $this->saveSchool($School, $school_data );
                unset($data[$school_id]);
                if(!$success){
                    $any_errors = true;
                }
            }else{
                $success = $this->deleteSchool($School);
                if(!$success){
                    $any_errors = true;
                }
            }
        }
        if(!empty($data)){
            foreach($data AS $jsonstr){
                $school_data = json_decode($jsonstr,true);
                $School = $this->getSeasonSchool(0);
                $School->setSeason($Season);
                $success = $this->saveSchool($School,$school_data);
                if(!$success){
                    $any_errors = true;
                }
            }
        }
        return !$any_errors;
    }

    public function updateSchoolSeasons(array $SeasonSchools, int $school_id, array $data){
        global $debug;
        $enrolled_events = [];
        $debug[] = 'updateSchoolSeasons';
        $debug[] = $data;
        /** @var SeasonSchool $SeasonSchool */
        foreach($SeasonSchools AS $SeasonSchool){
            $season_id = $SeasonSchool->getSeasonID();
            if(isset($data[$season_id])){
                $this->saveSchool($SeasonSchool,$data[$season_id]);
                $enrolled_events[$season_id] = $SeasonSchool;
                unset($data[$season_id]);
            }else{
                $this->deleteSchool($SeasonSchool);
            }
        }
        $debug[] = $data;
        if(!empty($data)){
            foreach($data AS $season_id=>$d){
                $SeasonSchool = $this->getSeasonSchool();
                $SeasonSchool->setSchoolID($school_id);
                $SeasonSchool->setSeasonID($season_id);
                $this->saveSchool($SeasonSchool,$d);
                $enrolled_events[$season_id] = $SeasonSchool;
            }
        }
        return $enrolled_events;
    }

    public function saveSchool(SeasonSchool $School, array $DATA):bool{
        global $debug;
        $debug[] = 'saveSchool';
        if(empty($DATA['season_id'])){
            $DATA['season_id'] = $School->getSeasonID();
        }
        if(empty($DATA['school_id'])){
            $DATA['school_id'] = $School->getSchoolID();
        }
        $debug[] = $DATA;
        $insert = $this->saveItem($DATA, $School->getID());
        $debug[] = $insert;
        $School->initialize($insert);
        $this->initializeSeasonSchool($School);
        return true;
    }

    public function copySchool(SeasonSchool $School, array $DATA):SeasonSchool{
        return $this->copyItem($School, $DATA);
    }

    public function deleteSchool(SeasonSchool $School):bool{
        $id = $School->getID();
        return $this->deleteItem($id);
    }
}