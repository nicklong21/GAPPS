<?php
namespace ElevenFingersCore\GAPPS\Sports;

use ElevenFingersCore\Database\DatabaseConnectorPDO;
use ElevenFingersCore\GAPPS\Schools\School;
use ElevenFingersCore\Utilities\MessageTrait;
use ElevenFingersCore\Utilities\InitializeTrait;

class Team{
    use MessageTrait;
    use InitializeTrait;
    protected $database;
    protected $id = 0;
    protected $DATA;

    protected $GameScore;
    protected $School;
    protected $Roster;

    protected $Season;
    static $db_table = 'games_teams';
    static $template = array(
        'id'=>0,
        'season_id'=>0,
        'game_id'=>0,
        'roster_id'=>0,
        'school_id'=>0,
        'school_name'=>NULL,
        'type'=>'',
    );

    function __construct(DatabaseConnectorPDO $DB, ?int $id = 0, ?array $DATA = array()){
        $this->database = $DB;
        $this->initialize($id,$DATA);
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

    public function getRoster():?Roster{
        if(empty($this->Roster)){
            $Season = $this->getSeason();
            if(!empty($this->DATA['roster_id'])){
                $this->Roster = $Season->getRoster($this->DATA['roster_id']);
            }else if(!empty($this->DATA['school_id'])){
                $this->Roster = $Season->getRoster(null, null, array('school_id'=>$this->getSchoolID()));
            }
        }
        return $this->Roster;
    }

    public function setRoster(Roster $Roster){
        $this->Roster = $Roster;
    }

    public function getSchoolID():int{
        return $this->DATA['school_id'];
    }

    public function getSchoolName():?string{
        return $this->DATA['school_name'];
    }

    public function getSchool():?School{
        if(empty($this->School)){
            if(!empty($this->DATA['school_id'])){
                $this->School = new School($this->database, $this->DATA['school_id']);
            }
        }
        return $this->School;
    }

    public function setSchool(School $School){
        $this->School = $School;
    }

    public function getSeasonID():int{  
        return $this->DATA['season_id'];
    }

    public function getSeason():Season{
        if(empty($this->Season)){
            $this->Season = new Season($this->database, $this->DATA['season_id']);
        }
        return $this->Season;
    }

    public function setSeason(Season $Season){ 
        $this->Season = $Season;
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

    /** @return Team[] */
    public static function getTeams(DatabaseConnectorPDO $DB, ?array $filter = array()):array{
        global $logger;
        $data = $DB->getArrayListByKey(static::$db_table, $filter);
        $Teams = array();
        foreach($data AS $team_data){
            $Teams[] = new Team($DB, null, $team_data);
        }
        return $Teams;
    }

}
