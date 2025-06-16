<?php
namespace ElevenFingersCore\GAPPS\Sports\Seasons;
use DateTimeImmutable;
use ElevenFingersCore\Utilities\MessageTrait;
use ElevenFingersCore\GAPPS\InitializeTrait;
use ElevenFingersCore\Utilities\UtilityFunctions;
use ElevenFingersCore\GAPPS\Sports\Rosters\Roster;
use ElevenFingersCore\GAPPS\Sports\Games\Game;
use ElevenFingersCore\GAPPS\Sports\Games\GameFactory;
use ElevenFingersCore\GAPPS\Sports\Sport;
use ElevenFingersCore\GAPPS\Sports\Seasons\Divisions\DivisionFactory;
use ElevenFingersCore\GAPPS\Sports\Seasons\Divisions\Division;
use ElevenFingersCore\GAPPS\Sports\Seasons\Regions\RegionFactory;
use ElevenFingersCore\GAPPS\Sports\Seasons\Regions\Region;

class Season{
    use MessageTrait;
    use InitializeTrait;
    protected $Sport;
    protected $Schools = array();
    protected $Rosters = array();
    protected $Games = array();
    protected $Regions;
    protected $Divisions;
    protected $SeasonSchoolFactory;
    protected $DivisionFactory;
    protected $RegionFactory;

    protected $division_flags = [];


    function __construct(array $DATA){
        $this->initialize($DATA);
    }

    public function initialize(array $DATA){
        $this->DATA = array_merge($this->DATA,$DATA);
        $this->id = $this->DATA['id']??0;
        $this->Schools = [];
        $this->Rosters = [];
        $this->Games = [];
        $this->Regions = [];
        $this->Divisions = [];
    }


    /** @return Game[] */
    public function getGames(?array $filter=array()):array{
        $GameFactory = $this->getGameFactory();
        $Games = $GameFactory->getGames($filter);
        return $Games;
    }

    public function getGame(int $id):Game{
        $GameFactory = $this->getGameFactory();
        $Game = $GameFactory->getGame($id);
        return $Game;
    }

    public function setRegions(array $Regions){
        $this->Regions = $Regions;
    }

/** @return Region[] */
    public function getRegions():array{
        if(empty($this->Regions)){
            $this->Regions = $this->getRegionFactory()->getSeasonRegions($this->getID());
        }
        return $this->Regions;
    }

    public function setDivisions(array $Divisions){
        $this->Divisions = $Divisions;
    }

/** @return Division[] */
    public function getDivisions():array{
        if(empty($this->Divisions)){
            $this->Divisions = $this->getDivisionFactory()->getSeasonDivisions($this->getID());
        }
        return $this->Divisions;
    }

    public function setDivisionFlags(array $flags){
        $this->division_flags = $flags;
    }

    public function getDivisionFlags():array{
        return $this->division_flags;
    }

    public function setSeasonSchools(array $Schools){
        $this->Schools = $Schools;
    }

/** @return SeasonSchool[] */
    public function getSeasonSchools():array{
        if(empty($this->Schools)){
            $this->Schools = $this->getSeasonSchoolFactory()->getSchoolsBySeason($this);
        }
        return $this->Schools;
    }

    public function getSchoolIDsByDivision(int $division_id):array{
        return $this->getSeasonSchoolFactory()->getSchoolIDsByDivision($division_id);
    }

    public function getSchoolIDsByRegion(int $region_id):array{
        return $this->getSeasonSchoolFactory()->getSchoolIDsByRegion($region_id);
    }

    public function getRoster(?int $id = 0):Roster{
        $RosterFactory = $this->getSport()->getRosterFactory();
        $Roster = $RosterFactory->getRoster($id);
        $Roster->setSeasonID($this->getID());
        return $Roster;
    }

    public function getSchoolRoster(?int $school_id = 0):Roster{
        $RosterFactory = $this->getSport()->getRosterFactory();
        $Roster = $RosterFactory->getSchoolSeasonRoster($this->getID(), $school_id);
        $Roster->setSeasonID($this->getID());
        $Roster->setSchoolID($school_id);
        return $Roster;
    }

/** @return Roster[] */
    public function getRosters():array{
        if(empty($this->Rosters)){
            $RosterFactory = $this->getSport()->getRosterFactory();
            $Rosters = $RosterFactory->getSeasonRosters($this->getID());
            foreach($Rosters AS $Roster){
            }
            $this->Rosters = $Rosters;
        }
        return $this->Rosters;
    }

    /**
     * Summary of setRosters
     * @param Roster[] $Rosters
     * @return void
     */
    public function setRosters(array $Rosters){
        $this->Rosters = $Rosters;
    }

    public function getSportID():int{
        return $this->DATA['sport_id'];
    }

    public function setSport(Sport $Sport){
        $this->Sport = $Sport;
        $this->DATA['sport_id'] = $Sport->getID();
    }

    public function getSport():Sport{
        return $this->Sport;
    }

    public function getDateStart(?string $format = null):DateTimeImmutable|String|Null{
        return $this->getDate($this->DATA['date_start'], $format); 
    }

    public function getDateEnd(?string $format = null):DateTimeImmutable|String|Null{
        return $this->getDate($this->DATA['date_end'], $format); 
    }

    public function getDateEnrollmentStart(?string $format = null):DateTimeImmutable|String|Null{
        return $this->getDate($this->DATA['enrollment_start'], $format);  
    }

    public function getDateEnrollmentEnd(?string $format = null):DateTimeImmutable|String|Null{
        return $this->getDate($this->DATA['enrollment_end'], $format);  
    }

    public function getDateRosterCutoff(?string $format = null):DateTimeImmutable|String|Null{
        return $this->getDate($this->DATA['roster_cutoff'], $format);  
    }

    public function isRosterClosed():bool{
        $closed = false;
        $date = $this->getDateRosterCutoff('Y-m-d');
        if(!empty($date)){ 
            $date_time = $date.' 00:00:00';
            $Cutoff = new DateTimeImmutable($date_time);
            $Today = new DateTimeImmutable('now');
            $closed = $Cutoff > $Today;
        }
        return $closed;
    }

    protected function getDate(int|string|null $date, ?string $format = null):DateTimeImmutable|string|null{
        $return_date = null;
        if(!empty($date) && $date != '0000-00-00 00:00:00'){
            if(is_numeric($date)){
                $DateTime = new DateTimeImmutable();
                $DateTime->setTimestamp($date);
            }else{
                $DateTime = new DateTimeImmutable($date);
            }
            if(!empty($format)){
                $return_date = $DateTime->format($format);
            }else{
                $return_date = $DateTime;
            }
        }
        return $return_date;
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
        $school_year = $this->DATA['year']??'';
        return $school_year;
    }

    public function getDATA():array{
        return $this->DATA;
    }

    public function getID():int{
        return $this->id;
    }

    public function getTitle():string{
        return $this->DATA['title']??'';
    }

    public function setDivisionFactory(DivisionFactory $Factory){
        $this->DivisionFactory = $Factory;
    }

    public function getDivisionFactory():DivisionFactory{
        return $this->DivisionFactory;
    }

    public function setRegionFactory(RegionFactory $Factory){
        $this->RegionFactory = $Factory;
    }

    public function getRegionFactory():RegionFactory{
        return $this->RegionFactory;
    }

    public function setSeasonSchoolFactory(SeasonSchoolFactory $Factory){
        $this->SeasonSchoolFactory = $Factory;
    }

    public function getSeasonSchoolFactory():SeasonSchoolFactory{
        return $this->SeasonSchoolFactory;
    }

    public function getGameFactory():GameFactory{
        $Factory = $this->getSport()->getGameFactory();
        $Factory->setSeasonID($this->getID());
        return $Factory;
    }

}


