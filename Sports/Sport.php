<?php
namespace ElevenFingersCore\GAPPS\Sports;

use ElevenFingersCore\Utilities\MessageTrait;
use ElevenFingersCore\GAPPS\InitializeTrait;
use ElevenFingersCore\GAPPS\Sports\Rosters\RosterFactory;
use ElevenFingersCore\GAPPS\Sports\Seasons\Season;
use ElevenFingersCore\GAPPS\Sports\Seasons\SeasonFactory;
use ElevenFingersCore\GAPPS\Sports\Games\GameFactory;
class Sport{
    use MessageTrait;
    use InitializeTrait;
    protected $Seasons = array();
    protected $GameFactory;
    protected $RosterFactory;
    protected $SeasonFactory;

    protected $pitch_count = false;

    function __construct(array $DATA = array()){
        $this->initialize($DATA);
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

    public function getActvityType():?string{
        return $this->getAGroup();
    }

    public function getAGroup():?string{
        return $this->DATA['agroup'];
    }

    public function getSemester():?string{
        return $this->getType();
    }

    public function getType():?string{
        return $this->DATA['type'];
    }

    public function getAgeGroup():?string{
        return $this->getZGroup();
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

    public function getCategory():string{
        return $this->DATA['category']??$this->getTitle();
    }

    public function getSubCategoryTitle():string{
        return $this->DATA['sub_category_title']??$this->getTitle();
    }

    public function isMaxPrepSport():bool{
        return $this->DATA['maxpreps_stats']?true:false;
    }

    public function getParticipatingSchoolsLabel():string{
        return $this->DATA['participating_label']??'Participating Schools';
    }

    public function usePitchCount():bool{
        return $this->pitch_count;
    }

    public function getCurrentSeason():Season{
        $Seasons = $this->getSeasons();
        //$logger->debug($this->getTitle().' Sport->getCurrentSeason()',array('seasons'=>array_keys($Seasons)));
        if(!empty($Seasons)){
            $CurrentSeason = $Seasons[0];
        }else{
            $CurrentSeason = $this->getSeasonFactory()->getSeason();
            $CurrentSeason->setSport($this);
        }
        return $CurrentSeason;
    }

    public function getSeason(int $id):Season{
        $Season = $this->getSeasonFactory()->getSeason($id);
        $Season->setSport($this);
        return $Season;
    }

    /** @return Season[] */
    public function getSeasons():array{
        if(empty($this->Seasons)){
            $Seasons = $this->getSeasonFactory()->getSportSeasons($this->id);
            /** @var Season $Season */
            foreach($Seasons AS $Season){
                $Season->setSport($this);
            }
            $this->Seasons = $Seasons;
        }
        return $this->Seasons;
    }

    public function getRosterFactory():RosterFactory{
        return $this->RosterFactory;
    }

    public function setRosterFactory(RosterFactory $Factory){
        $this->RosterFactory = $Factory;
    }

    public function getGameFactory():GameFactory{
        return $this->GameFactory;
    }
    public function setGameFactory(GameFactory $Factory){
        $this->GameFactory = $Factory;
    }

    public function getGameTypes():array{
        $Game = $this->getGameFactory()->getGame();
        return $Game->getGameTypes();
    }

    public function getGameStatusOptions():array{
        $Game = $this->getGameFactory()->getGame();
        return $Game->getGameStatusOptions();
    }

    public function getSeasonFactory():SeasonFactory{
        $SeasonFactory = $this->SeasonFactory;
        $SeasonFactory->setSports($this);
        return $this->SeasonFactory;
    }
    public function setSeasonFactory(SeasonFactory $Factory){
        $this->SeasonFactory = $Factory;
    }

}
