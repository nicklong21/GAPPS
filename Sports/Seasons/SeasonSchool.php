<?php
namespace ElevenFingersCore\GAPPS\Sports\Seasons;

use ElevenFingersCore\Database\DatabaseConnectorPDO;
use ElevenFingersCore\GAPPS\Schools\SchoolFactory;
use ElevenFingersCore\Utilities\MessageTrait;
use ElevenFingersCore\GAPPS\InitializeTrait;
use ElevenFingersCore\GAPPS\Schools\School;
use ElevenFingersCore\GAPPS\Sports\Rosters\Roster;
use ElevenFingersCore\Utilities\SortableObject;

class SeasonSchool{
    use MessageTrait;
    use InitializeTrait;
    protected $season_id;
    protected $school_id;
    protected $Season;
    protected $School;
    protected $Roster;
    protected $Games;
    

    function __construct(array $DATA){
        $this->initialize($DATA);
        $this->season_id = $this->DATA['season_id']??0;
        $this->school_id = $this->DATA['school_id']??0;
    }

    public function getSeasonID():int{
        return $this->season_id;
    }

    public function setSeasonID(int $id){
        $this->season_id = $id;
    }

    public function getSchoolID():int{
        return $this->school_id;
    }

    public function setSchoolID(int $id){
        $this->school_id = $id;
    }

    public function getRegionID():int{  
        return $this->DATA['region_id']??0;
    }

    public function getDivisionID():int{
        return $this->DATA['division_id']??0;
    }

    public function getStatus():string{
        return $this->DATA['status']??'';
    }

    public function getSeason():Season{
        return $this->Season;
    }
    public function setSeason(Season $Season){
        $this->Season = $Season;
        $this->season_id = $Season->getID();
    }

    public function getSchool():School{
        return $this->School;
    }
    public function setSchool(School $School){
        $this->School = $School;
        $this->school_id = $School->getID();
    }

}
