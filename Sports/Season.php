<?php
namespace ElevenFingersCore\GAPPS\Sports;
use DateTimeImmutable;
use ElevenFingersCore\Database\DatabaseConnectorPDO;
use ElevenFingersCore\Utilities\MessageTrait;
use ElevenFingersCore\Utilities\InitializeTrait;
use ElevenFingersCore\Utilities\UtilityFunctions;

class Season{
    use MessageTrait;
    use InitializeTrait;
    protected $database;
    protected $id = 0;
    protected $DATA;
    protected $Sport;
    protected $Schools = array();
    protected $Rosters = array();
    protected $Games = array();
    protected $Regions;
    protected $Divisions;
    protected $DateStart;
    protected $DateEnd;
    protected $DateEnrollmentStart;
    protected $DateEnrollmentEnd;
    protected $DateRosterCutoff;
    static $db_table = 'sports_seasons';
    static $template = array(
        'id'=>0,
        'title'=>'',
        'year'=>0,
        'sport_id'=>0,
        'date_start'=>0,
        'date_end'=>0,
        'enrollment_start'=>0,
        'enrollment_end'=>0,
        'roster_cutoff'=>0,
        'feedback_letter'=>0,
       );

    function __construct(DatabaseConnectorPDO $DB, ?int $id = 0, ?array $DATA = array()){
        $this->database = $DB;
        $this->initialize($id,$DATA);
    }

    public function getSport():Sport{
        if(empty($this->Sport)){
            $this->Sport = Sport::getSport($this->database, $this->getSportID());
        }
        return $this->Sport;
    }

    /** @return Game[] */
    public function getGames(?array $filter=array(), ?int $school_id = 0):array{
        $Games = array();
        if(empty($this->Games)){
            $args = array('season_id'=>$this->id);
            $GameData = $this->database->getArrayListByKey(Game::$db_table,$args, 'date');
            $this->Games = array();
            $Sport = $this->getSport();
            foreach($GameData AS $DATA){
                $this->Games[] = $Sport->getSportGame(0,$DATA);
            }
        }
        
        if(!empty($filter)){
            $FilteredGames = array();
            $filtered_ids = $this->database->getResultListByKey(Game::$db_table,$filter,'id');
            foreach($this->Games AS $Game){
                if(in_array($Game->getID(), $filtered_ids)){
                    $FilteredGames[] = $Game;
                }
            }
            $Games = $FilteredGames;
        }else{
            $Games = $this->Games;
        }

        if(!empty($school_id)){
            $SchoolGames = array();
            $team_games = $this->database->getResultListByKey(Team::$db_table,array('season_id'=>$this->id,'school_id'=>$school_id),'game_id');
            foreach($Games AS $Game){
                if(in_array($Game->getID(), $team_games)){
                    $SchoolGames[] = $Game;
                }
            }
            $Games = $SchoolGames;
        }
        
        return $Games;
    }

    public function getGame(int $id):Game{
        $Sport = $this->getSport();
        $Game = $Sport->getSportGame($id);
        $Game->setSeason($this);
        return $Game;
    }

/** @return Region[] */
    public function getRegions():array{
        if(empty($this->Regions)){
            $this->Regions = Region::getSeasonRegions($this->database, $this->id);
        }
        return $this->Regions;
    }

    public function updateRegions(array $data):bool{
        $Regions = $this->getRegions();
        $region_data = array();
        $new_data = array();
        $any_errors = false;
        foreach($data AS $d){
            if($d['id']){
                $region_data[$d['id']] = $d;
            }else{
                $new_data[] = $d;
            }
        }
        foreach($Regions AS $Region){
            $region_id = $Region->getID();
            if(isset($region_data[$region_id])){
                $insert = array('title'=>$region_data[$region_id]['title']);
                $success = $Region->Save($insert);
                if(!$success){
                    $any_errors = true;
                    $this->addErrorMsg($Region->getErrorMsg());
                }
            }else{
                $success = $Region->Delete();
                if(!$success){
                    $any_errors = true;
                    $this->addErrorMsg($Region->getErrorMsg());
                }
            }
        }
        foreach($new_data AS $n){
            $Region = new Region($this->database);
            $insert = $n;
            $insert['season_id'] = $this->getID();
            $insert['sport_id'] = $this->getSportID();
            $success = $Region->Save($insert);
            if(!$success){
                $any_errors = true;
                $this->addErrorMsg($Region->getErrorMsg());
            }
        }
        $this->Regions = null;
        return !$any_errors;
    }

/** @return Division[] */
    public function getDivisions():array{
        if(empty($this->Divisions)){
            $this->Divisions = Division::getSeasonDivisions($this->database, $this->id);
        }
        return $this->Divisions;
    }

    public function updateDivisions(array $data):bool{
        $Divisions = $this->getDivisions();
        $division_data = array();
        $new_data = array();
        $any_errors = false;
        foreach($data AS $d){
            if($d['id']){
                $division_data[$d['id']] = $d;
            }else{
                $new_data[] = $d;
            }
        }
        foreach($Divisions AS $Division){
            $division_id = $Division->getID();
            if(isset($division_data[$division_id])){
                $insert = $division_data[$division_id];
                $success = $Division->Save($insert);
                if(!$success){
                    $any_errors = true;
                    $this->addErrorMsg($Division->getErrorMsg());
                }
            }else{
                $success = $Division->Delete();
                if(!$success){
                    $any_errors = true;
                    $this->addErrorMsg($Division->getErrorMsg());
                }
            }
        }
        foreach($new_data AS $d){
            $Division = new Division($this->database);
            $insert = $d;
            $insert['season_id'] = $this->getID();
            $insert['sport_id'] = $this->getSportID();
            $success = $Division->Save($insert);
            if(!$success){
                $any_errors = true;
                $this->addErrorMsg($Division->getErrorMsg());
            }
        }
        $this->Divisions = null;
        return !$any_errors;
    }

/** @return SeasonSchool[] */
    public function getSchools():array{
        if(empty($this->Schools)){
            $this->Schools = SeasonSchool::getSeasonSchools( $this->database, $this->id);
        }
        return $this->Schools;
    }

    public function updateSchools(array $data):bool{
        $Schools = $this->getSchools();
        $any_errors = false;
        foreach($Schools AS $School){
            $school_id = $School->getSchoolID();
            if(isset($data[$school_id])){
                $school_data = json_decode($data[$school_id],true);
                $success = $School->Save( $school_data );
                unset($data[$school_id]);
                if(!$success){
                    $any_errors = true;
                    $this->addErrorMsg($School->getErrorMsg());
                }
            }else{
                $success = $School->Delete();
                if(!$success){
                    $any_errors = true;
                    $this->addErrorMsg($School->getErrorMsg());
                }
            }
        }
        if(!empty($data)){
            foreach($data AS $jsonstr){
                $school_data = json_decode($jsonstr,true);
                $school_id['season_id'] = $this->getID();
                $School = new SeasonSchool($this->database);
                $success = $School->Save( $school_data );
                if(!$success){
                    $any_errors = true;
                    $this->addErrorMsg($School->getErrorMsg());
                }
            }
        }
        $this->Schools = null;
        return !$any_errors;
    }

    public function getRoster(?int $id = 0, ?array $DATA = array(), ?array $filter = null):Roster{
        $Sport = $this->getSport();
        if(!empty($filter)){
            $filter['season_id'] = $this->getID();
        }
        $Roster = $Sport->getSportRoster($id, $DATA, $filter);
        $Roster->setSeason($this);
        return $Roster;
    }

/** @return Roster[] */
    public function getRosters():array{
        if(empty($this->Rosters)){
            $Sport = $this->getSport();
            $this->Rosters = $Sport->getRosters(array('season_id'=>$this->id));
            foreach($this->Rosters AS $Roster){
                $Roster->setSeason($this);
            }
        }
        return $this->Rosters;
    }

    public function findRoster(Array $filter):?Roster{
        $filter['season_id'] = $this->id;
        $Sport = $this->getSport();
        $Roster = $Sport->getSportRoster(null, null, $filter);
        $Roster->setSeason($this);
        return $Roster;
    }

    public function getDATA():array{
        return $this->DATA;
    }


    public function getID():int{
        return $this->id;
    }

    public function getSportID():int{
        return $this->DATA['sport_id'];
    }

    public function setSport(Sport $Sport){
        $this->Sport = $Sport;
        $this->DATA['sport_id'] = $Sport->getID();
    }

    public function getDateStart(string $format = null):DateTimeImmutable|String|Null{
        $date = null;
        if(empty($this->DateStart) && !empty($this->DATA['date_start']) && $this->DATA['date_start'] != '0000-00-00 00:00:00'){
            if(!is_numeric($this->DATA['date_start'])){
                $this->DateStart = new DateTimeImmutable($this->DATA['date_start']);
            }else{
                $Date = new DateTimeImmutable();
                $this->DateStart = $Date->setTimestamp($this->DATA['date_start']);
            }
        }
        if(!empty($this->DateStart)){
            if($format){
                $date = $this->DateStart->format($format);
            }else{
                $date = $this->DateStart;
            }
        }
        return $date;
    }

    public function getDateEnd(string $format = null):DateTimeImmutable|String|Null{
        $date = null;
        if(empty($this->DateEnd) && !empty($this->DATA['date_end']) && $this->DATA['date_end'] != '0000-00-00 00:00:00'){
            if(!is_numeric($this->DATA['date_end'])){
                $this->DateEnd = new DateTimeImmutable($this->DATA['date_end']);
            }else{
                $Date = new DateTimeImmutable();
                $this->DateEnd = $Date->setTimestamp($this->DATA['date_end']);
            }
        }
        if(!empty($this->DateEnd)){
            if($format){
                $date = $this->DateEnd->format($format);
            }else{
                $date = $this->DateEnd;
            }
        }
        return $date;
    }

    public function getDateEnrollmentStart(string $format = null):DateTimeImmutable|String|Null{
        $date = null;
        if(empty($this->DateEnrollmentStart) && !empty($this->DATA['enrollment_start']) && $this->DATA['enrollment_start'] != '0000-00-00 00:00:00'){
            if(!is_numeric($this->DATA['enrollment_start'])){
                $this->DateEnrollmentStart = new DateTimeImmutable($this->DATA['enrollment_start']);
            }else{
                $Date = new DateTimeImmutable();
                $this->DateEnrollmentStart = $Date->setTimestamp($this->DATA['enrollment_start']);
            }
        }
        if(!empty($this->DateEnrollmentStart)){
            if($format){
                $date = $this->DateEnrollmentStart->format($format);
            }else{
                $date = $this->DateEnrollmentStart;
            }
        }
        return $date;
    }

    public function getDateEnrollmentEnd(string $format = null):DateTimeImmutable|String|Null{
        $date = null;
        if(empty($this->DateEnrollmentEnd) && !empty($this->DATA['enrollment_end']) && $this->DATA['enrollment_end'] != '0000-00-00 00:00:00'){
            if(!is_numeric($this->DATA['enrollment_end'])){
                $this->DateEnrollmentEnd = new DateTimeImmutable($this->DATA['enrollment_end']);
            }else{
                $Date = new DateTimeImmutable();
                $this->DateEnrollmentEnd = $Date->setTimestamp($this->DATA['enrollment_end']);
            }
        }
        if(!empty($this->DateEnrollmentEnd)){
            if($format){
                $date = $this->DateEnrollmentEnd->format($format);
            }else{
                $date = $this->DateEnrollmentEnd;
            }
        }
        return $date;
    }

    public function getDateRosterCutoff(string $format = null):DateTimeImmutable|String|Null{
        $date = null;
        if(empty($this->DateRosterCutoff) && !empty($this->DATA['roster_cutoff']) && $this->DATA['roster_cutoff'] != '0000-00-00 00:00:00'){
            if(!is_numeric($this->DATA['roster_cutoff'])){
                $this->DateRosterCutoff = new DateTimeImmutable($this->DATA['roster_cutoff']);
            }else{
                $Date = new DateTimeImmutable();
                $this->DateRosterCutoff = $Date->setTimestamp($this->DATA['roster_cutoff']);
            }
        }
        if(!empty($this->DateRosterCutoff)){
            if($format){
                $date = $this->DateRosterCutoff->format($format);
            }else{
                $date = $this->DateRosterCutoff;
            }
        }
        return $date;
    }

    public function getStatus():?string{
        return $this->DATA['status'];
    }

    public function getOpenStatus(?string $date_format = 'Y-m-d'):?string{
        $status = null;
        $Today = new DateTimeImmutable();
        $SeasonStarts = $this->getDateStart();
        $SeasonEnds = $this->getDateEnd();
        if(!empty($SeasonStarts) && !empty($SeasonEnds)){
            if($Today < $SeasonStarts){
                $status = 'Season Opens '.$SeasonStarts->format($date_format);
            }elseif($Today < $SeasonEnds){
                $status = 'Season Open';
            }else{
                $status = 'Season Ended '.$SeasonEnds->format($date_format);
            }
        }
        return $status;
    }

    public function getEnrollmentPeriod(?string $date_format = 'Y-m-d'):?string{
        $period = null;
        $EnrollmentStarts = $this->getDateEnrollmentStart();
        $EnrollmentEnds = $this->getDateEnrollmentEnd();

        if(!empty($EnrollmentStarts) && !empty($EnrollmentEnds)){
            $period = UtilityFunctions::humanDateRanges($EnrollmentStarts, $EnrollmentEnds);
        }

        return $period;
    }

    public function getSchoolYear():?string{
        $school_year = null;
        $SeasonStarts = $this->getDateStart();
        if(!empty($SeasonStarts)){
            $n = $SeasonStarts->format('n');
            if($n > 6){
                $Y = $SeasonStarts->format('Y');
                $Next = $SeasonStarts->modify('+1 Year');
                $y = $Next->format('y');
            }else{
                $Past = $SeasonStarts->modify('-1 Year');
                $Y = $Past->format('Y');
                $y = $SeasonStarts->format('y');
            }
            $school_year = $Y.'-'.$y;
        }
        return $school_year;
    }

    public function copyFromPreviousYear():bool{
        $this->database->beginTransaction();
        $sql = 'SELECT * FROM '.static::$db_table.' WHERE sport_id=:sport_id ORDER BY year DESC limit 1,1';
        $args = array(':sport_id'=>$this->getSportID());
        $result = $this->database->getResultArray($sql, $args);
        if(!empty($result)){
            $PreviousSeason = new static($this->database, null, $result);
            $Regions = $PreviousSeason->getRegions();
            $Divisions = $PreviousSeason->getDivisions();
            $Schools = $PreviousSeason->getSchools();

            $DATA = array('season_id'=>$this->getID());
            $NewRegions = array();
            foreach($Regions AS $Region){
                $NewRegions[$Region->getID()] = $Region->Copy($DATA);
            }
            $NewDivisions = array();
            foreach($Divisions AS $Division){
                $NewDivisions[$Division->getID()] = $Division->Copy($DATA);
            }
            foreach($Schools AS $School){
                $old_region_id = $School->getRegionID();
                $old_division_id = $School->getDivisionID();
                $new_region_id = isset($NewRegions[$old_region_id])?$NewRegions[$old_region_id]->getID():0;
                $new_division_id = isset($NewDivisions[$old_division_id])?$NewDivisions[$old_division_id]->getID():0;
                $DATA['region_id'] = $new_region_id;
                $DATA['division_id'] = $new_division_id;
                
                $School->Copy($DATA);
            }
            $this->database->commitTransaction();
            $success = true;
        }else{
            $this->addErrorMsg('No Previous Season for this Sport Found');
            $success = false;
        }
        return $success;
    }

    public static function getSeasons(DatabaseConnectorPDO $DB, ?array $filter = array(), null|string|array $order_by):array{
        $Seasons = array();
        $SeasonData = $DB->getArrayListByKey(static::$db_table, $filter, $order_by);
        foreach($SeasonData AS $DATA){
            $Seasons[] = new static($DB, null, $DATA);
        }
        return $Seasons;
    }

}


?>