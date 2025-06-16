<?php
namespace ElevenFingersCore\GAPPS\Sports\Seasons;
use ElevenFingersCore\Database\DatabaseConnectorPDO;
use ElevenFingersCore\GAPPS\FactoryTrait;
use ElevenFingersCore\GAPPS\Sports\Seasons\Regions\RegionFactory;
use ElevenFingersCore\GAPPS\Sports\Seasons\Divisions\DivisionFactory;
use ElevenFingersCore\GAPPS\Sports\Sport;
use ElevenFingersCore\GAPPS\Sports\SportFactory;
use ElevenFingersCore\GAPPS\Sports\SportRegistry;
use ElevenFingersCore\Utilities\MessageTrait;
use ElevenFingersCore\Utilities\UtilityFunctions;

class SeasonFactory{
    use FactoryTrait;
    use MessageTrait;
    protected $database;

    protected $dependencies;
    protected $db_table = 'sports_seasons';
    protected $DivisionFactory;
    protected $RegionFactory;
    protected $SeasonSchoolFactory;

    protected $SportFactory;

    protected $Sports = [];

    protected $schema = [
        'id'=>0,
        'title'=>'',
        'year'=>0,
        'sport_id'=>0,
        'date_start'=>null,
        'date_end'=>null,
        'enrollment_start'=>null,
        'enrollment_end'=>null,
        'roster_cutoff'=>null,
        'feedback_letter'=>0,
    ];

    function __construct(DatabaseConnectorPDO $DB,  array $dependencies, SportFactory $SportFactory){
        $this->database = $DB;
        $season_class = $dependencies['season'];
        $this->dependencies = $dependencies;
        $this->setItemClass($season_class);
        $region_factory_class = $dependencies['region_factory'];
        $division_factory_class = $dependencies['division_factory'];
        $school_factory_class = $dependencies['school_factory'];
        $this->setRegionFactory( new  $region_factory_class($this->database, $dependencies));
        $this->setDivisionFactory(new $division_factory_class($this->database, $dependencies));
        $this->setSeasonSchoolFactory(new $school_factory_class($this->database, $dependencies,$this));
        $this->SportFactory = $SportFactory;
    }

    public function getSeason(?int $id = null, ?array $DATA = array()):Season{
        
        $Season = $this->getItem($id, $DATA);
        $Season->setDivisionFactory($this->getDivisionFactory());
        $Season->setRegionFactory($this->getRegionFactory());
        $Season->setSeasonSchoolFactory($this->getSeasonSchoolFactory());
        if($Season->getID()){
            $sport_id = $Season->getSportID();
            if(!empty($this->Sports[$sport_id])){
                $Sport = $this->Sports[$sport_id];
            }else{
                $Sport = $this->SportFactory->getSport($sport_id);
                $this->Sports[$Sport->getID()] = $Sport;
            }
            $division_flags = $Sport->getDivisionFlags();
            $Season->setSport($Sport);
            $Season->setDivisionFlags($division_flags);
        }
        
        return $Season;
    }

    /**
     * Summary of getSportSeasons
     * @param int $sport_id
     * @return Season[]
     */
    public function getSportSeasons(int $sport_id):array{
        $data = $this->database->getArrayListByKey($this->db_table,['sport_id'=>$sport_id],'date_start DESC');
        $Seasons = [];
        foreach($data AS $DATA){
            $Seasons[] = $this->getSeason(null, $DATA);
        }
        return $Seasons;
    }

    public function saveSeason(Season $Season, Array $DATA):bool{
        if(isset($DATA['date_start'])){
            if(!empty($DATA['date_start'])){
                $DATA['year'] = UtilityFunctions::formatSchoolYear($DATA['date_start'],'06-01');
            }else{
                throw new \RuntimeException('Attempting to save a Sport Season without a valid start date is not allowed.');
            }
            if(empty($DATA['date_end'])){
                $DATA['date_end'] = null;
            }
            if(empty($DATA['enrollment_start'])){
                $DATA['enrollment_start'] = null;
            }
            if(empty($DATA['enrollment_end'])){
                $DATA['enrollment_end'] = null;
            }
            if(empty($DATA['roster_cutoff'])){
                $DATA['roster_cutoff'] = null;
            }
        }
        $insert = $this->saveItem($DATA, $Season->getID());
        if(!empty($insert)){
            $Season->initialize($insert);
        }
        return true;
    }

    public function getDependencies():array{
        return $this->dependencies;
    }

    public function setSports(array|Sport $sports){
        if(is_array($sports)){
            foreach($sports AS $Sport){
                $sport_id = $Sport->getID();
                $this->Sports[$sport_id] = $Sport;
            }
        }else{
            $sport_id = $sports->getID();
            $this->Sports[$sport_id] = $sports;
        }
    }

    public function copyFromPreviousYear(Season $Season):bool{
        $sport_id = $Season->getSportID();
        $season_id = $Season->getID();
        $previous_record = $this->database->getArrayByKey($this->db_table,['sport_id'=>$sport_id],'year DESC','LIMIT 1,1');
        if(!empty($previous_record)){
            $PreviousSeason = $this->getSeason(null, $previous_record);
            $Regions = $PreviousSeason->getRegions();
            $Divisions = $PreviousSeason->getDivisions();
            $Schools = $PreviousSeason->getSeasonSchools();
            $NewRegions = [];
            $DATA = ['season_id'=>$season_id];
            foreach($Regions AS $Region){
                $NewRegions[$Region->getID()] = $this->getRegionFactory()->copyRegion($Region, $DATA);
            }
            $NewDivisions = [];
            foreach($Divisions AS $Division){
                $NewDivisions[$Division->getID()] = $this->getDivisionFactory()->copyDivision($Division, $DATA);
            }
            $NewSchools = [];
            foreach($Schools AS $School){
                $old_region_id = $School->getRegionID();
                $old_division_id = $School->getDivisionID();
                $new_region_id = isset($NewRegions[$old_region_id])?$NewRegions[$old_region_id]->getID():0;
                $new_division_id = isset($NewDivisions[$old_division_id])?$NewDivisions[$old_division_id]->getID():0;
                $DATA['region_id'] = $new_region_id;
                $DATA['division_id'] = $new_division_id;
                $NewSchools[] = $this->getSeasonSchoolFactory()->copySchool($School,$DATA);
            }
            $Season->setDivisions($NewDivisions);
            $Season->setRegions($NewRegions);
            $Season->setSeasonSchools($NewSchools);
            $success = true;

        }else{
            $this->addErrorMsg('No Previous Season for this Sport Found');
            $success = false;
        }
        return $success;
    }

    
    public function getSportSeasonForSchoolYear(int $sport_id, string $school_year):Season{
        $data = $this->database->getArrayByKey($this->db_table,['year'=>$school_year,'sport_id'=>$sport_id]);
        $Season = $this->getSeason(null, $data);
        return $Season;
    }

    /**
     * Summary of getSeasonsForSchoolYear
     * @param string $school_year
     * @param null|string|array $group
     * @param null|string|array $age_group
     * @return Season[]
     */
    public function getSeasonsForSchoolYear(string $school_year,null|string|array $group = null, null|string|array $age_group = null):array{
        $Sports = $this->SportFactory->getSports($group,$age_group);
        $Sports_by_id = [];
        foreach($Sports AS $Sport){
            $Sports_by_id[$Sport->getID()] = $Sport;
        }
        $this->Sports = $Sports_by_id;
        $sport_ids = array_keys($Sports_by_id);
        $data = $this->database->getArrayListByKey($this->db_table,['year'=>$school_year, 'sport_id'=>['IN'=>$sport_ids]]);
        $Seasons = [];
        foreach($data AS $DATA){
            $Season = $this->getSeason(null, $DATA);
            $Seasons[$Season->getID()] = $Season;
        }
        return $Seasons;
    }

    /**
     * Summary of getSchoolEnrolledSeasonSchools
     * @param int $school_id
     * @param string $school_year
     * @return SeasonSchool[]
     */
    public function getSchoolEnrolledSeasonSchools(int $school_id, string $school_year,null|string|array $group = null, null|string|array $age_group = null):array{
        $Seasons = $this->getSeasonsForSchoolYear($school_year);
        $season_ids = array_keys($Seasons);
        $Factory = $this->getSeasonSchoolFactory();
        $SeasonSchools = $Factory->getSchoolsBySeason($Seasons,$school_id);
        foreach($SeasonSchools AS $School){
            $season_id = $School->getSeasonID();
            $Season = $Seasons[$season_id];
            $School->setSeason($Season);
        }
        return $SeasonSchools;
    }

    /*
$this->RegionFactory = new $region_factory_class($this->database, $dependencies);
        $this->DivisionFactory = new $division_factory_class($this->database, $dependencies);
        $this->SeasonSchoolFactory = new $school_factory_class($this->database, $dependencies);
    */
    public function getRegionFactory():RegionFactory{
        return $this->RegionFactory;
    }
    public function setRegionFactory(RegionFactory $Factory){
        $this->RegionFactory = $Factory;
    }
    public function getDivisionFactory():DivisionFactory{
        return $this->DivisionFactory;
    }
    public function setDivisionFactory(DivisionFactory $Factory){
        $this->DivisionFactory = $Factory;
    }
    public function getSeasonSchoolFactory():SeasonSchoolFactory{
        return $this->SeasonSchoolFactory;
    }
    public function setSeasonSchoolFactory(SeasonSchoolFactory $Factory){
        $this->SeasonSchoolFactory = $Factory;
    }

}