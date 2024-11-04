<?php
namespace ElevenFingersCore\GAPPS\Sports\Games\Teams;
use ElevenFingersCore\GAPPS\Sports\Games\Scores\GameScore;
use ElevenFingersCore\GAPPS\Sports\Rosters\Roster;
use ElevenFingersCore\Utilities\MessageTrait;
use ElevenFingersCore\GAPPS\InitializeTrait;
use ElevenFingersCore\GAPPS\Sports\Rosters\RosterFactory;

class Team{
    use MessageTrait;
    use InitializeTrait;
    protected $GameScore;
    protected $Roster;

    protected $RosterFactory;


    function __construct(array $DATA){
        $this->initialize($DATA);
    }

    public function getStatus():?string{
        return $this->DATA['status'];
    }

    public function getGameID():int{
        return $this->DATA['game_id'];
    }

    public function getRosterID():int{
        return $this->DATA['roster_id'];
    }

    public function getSchoolID():int{
        return $this->DATA['school_id'];
    }

    public function getSchoolName():?string{
        return $this->DATA['school_name'];
    }

    public function getSeasonID():int{  
        return $this->DATA['season_id'];
    }

    public function isHomeTeam():bool{
        return $this->DATA['type'] == 'hometeam'?true:false;
    }

    public function getGameScore():?GameScore{
        return $this->GameScore;
    }

    public function setGameScore(GameScore $GameScore){
        $this->GameScore = $GameScore;
    }
    public function getRoster():?Roster{
        if(empty($this->Roster)){
            $season_id = $this->getSeasonID();
            $school_id = $this->getSchoolID();
            if(!empty($season_id) && !empty($school_id)){
                $Factory = $this->getRosterFactory();
                $this->Roster = $Factory->getSchoolSeasonRoster($season_id,$school_id);
            }
        }
        return $this->Roster??null;
    }

    public function setRoster(Roster $Roster){
        $this->Roster = $Roster;
    }

    public function setRosterFactory(RosterFactory $Factory){
        $this->RosterFactory = $Factory;
    }

    public function getRosterFactory():RosterFactory{
        return $this->RosterFactory;
    }

    public function getWinLoss():string{
        $winloss = '';
        $GameScore = $this->getGameScore();
        if($GameScore){
            $result = $GameScore->getResult();
            if($result){
                $winloss = '<div class="'.strtolower($result).'">'.$result.'</div>';
            }
        }
        return $winloss;
    }

    public function getScoreTotal():?string{
        $scoreTotal = '';
        $GameScore = $this->getGameScore();
        if($GameScore){
            $scoreTotal = $GameScore->getScore();
        }
        return $scoreTotal;
    }

}
