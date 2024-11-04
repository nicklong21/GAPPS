<?php
namespace ElevenFingersCore\GAPPS\Sports\Games;

use DateTimeImmutable;
use ElevenFingersCore\Utilities\MessageTrait;
use ElevenFingersCore\GAPPS\InitializeTrait;
use ElevenFingersCore\Utilities\UtilityFunctions;
use ElevenFingersCore\GAPPS\Sports\Games\Teams\Team;
use ElevenFingersCore\GAPPS\Sports\Games\Teams\TeamFactory;
use ElevenFingersCore\GAPPS\Sports\Games\Scores\GameScoreFactory;
use ElevenFingersCore\GAPPS\Sports\Games\Scores\GameScore;
use ElevenFingersCore\GAPPS\Sports\Games\Views\GameView;
use ElevenFingersCore\GAPPS\Sports\Venues\Venue;

class Game{
    use MessageTrait;
    use InitializeTrait;
    protected $StartDate;
    protected $season_id;
    protected $TeamFactory;
    protected $ScoreFactory;
    protected $Teams = array();
    protected $Scores;
    protected $Venue;

    protected $GameView;
    protected $is_locked = true;
    protected $game_types = array(
        'regular'=>'Non-Region Game',
        'regional'=>'Regional Game',
        'semi-final'=>'State Playoff Game',
        'championship'=>'State Championship Game',
    );
    protected $game_status_options = array(
        'Scheduled',
        'Played',
        'Canceled',
        'Completed',
        'Not Completed',
    );

    function __construct(array $DATA){
        $this->initialize($DATA);
        
    }

    function initialize(array $DATA)
    {
        $this->DATA = array_merge($this->DATA,$DATA);
        $this->id = $this->DATA['id']??0;
        $this->season_id = $this->DATA['season_id']??0;
        
        if($this->DATA['status'] != 'Completed'){
            $this->is_locked = false;
        }
        $this->season_id = $this->DATA['season_id'];
    }

    public function setIsLocked(bool $v){
        $this->is_locked = $v;
    }

    public function getIsLocked():bool{
        return $this->is_locked;
    }

    public function setSeasonID(int $id){
        $this->season_id = $id;
    }

    public function getSeasonID():int{
        return $this->season_id;
    }

    public function setTeamFactory(TeamFactory $TeamFactory){
        $this->TeamFactory = $TeamFactory;
    }

    public function getTeamFactory():TeamFactory{
        return $this->TeamFactory;
    }

    public function setScoreFactory(GameScoreFactory $ScoreFactory){
        $this->ScoreFactory = $ScoreFactory;
    }

    public function getScoreFactory():GameScoreFactory{
        $this->ScoreFactory->setSeasonID($this->getSeasonID());
        return $this->ScoreFactory;
    }

    /**
     * Summary of getTeams
     * @return Team[]
     */
    public function getTeams():array{
        if(empty($this->Teams)){
            $TeamFactory = $this->getTeamFactory();
            $this->Teams = $TeamFactory->getTeamsByGameID($this->id);
        }
        return $this->Teams;
    }
    public function setTeams(Array $Teams){
        $this->Teams = $Teams;
    }

    public function getHomeTeam():?Team{
        $Teams = $this->getTeams();
        $HomeTeam = null;
        foreach($Teams AS $Team){
            if($Team->isHomeTeam()){
                $HomeTeam = $Team;
            }
        }
        return $HomeTeam;
    }

    public function getSchoolIDs():array{
        $Teams = $this->getTeams();
        $school_ids = array();
        foreach($Teams AS $Team){
            $school_ids[] = $Team->getSchoolID();
        }
        return $school_ids;
    }

    

    /** @return GameScore[] */
    public function getScores():?array{
        if(empty($this->Scores)){
            $ScoreFactory = $this->getScoreFactory();
            $this->Scores = $ScoreFactory->getScoresForGame($this->getID());
        }
        return $this->Scores;
    }

    public function setScores(Array $Scores){
        $this->Scores = $Scores;
    }

    public function getTitle():string{
        return $this->DATA['title']??'';
    }

    public function getType():string{
        return $this->DATA['type']??'';
    }

    public function getRegionID():int{
        return $this->DATA['region_id']??0;
    }

    public function getDivisionID():int{
        return $this->DATA['division_id']??0;
    }

    public function getStartDate(?string $format = null):NULL|string|DateTimeImmutable{
        return UtilityFunctions::getDate($this->DATA['date'],$format);
    }

    public function getVenueID():int{
        return $this->DATA['venue_id']??0;
    }

    public function getStatus():?string{
        $status = $this->DATA['status']??'';
        if($status == 'Scheduled'){
            $StartDate = $this->getStartDate();
            $PlayedDate = $StartDate->modify('+ 2 hours');
            $Now = new DateTimeImmutable('now');
            if($Now > $PlayedDate){
                $status = 'Played';
            }
        }
        
        return $status;
    }

    public function getGameView():GameView{
        $View = $this->GameView;
        $Teams = $this->getTeams();
        $Scores = $this->getScores();
        $type = $this->getType();
        $View->setTeams($Teams);
        $View->setGameScores($Scores);
        $View->setGameType($type);
        return $this->GameView;
    }

    public function setGameView(GameView $View){
        $this->GameView = $View;
    }

    public function saveGameScores(Array $data):bool{
        $success = true;
        $Factory = $this->getScoreFactory();
        $GameScoreClass = $Factory->getItemClass();
        $game_scores = $GameScoreClass::compareScores($data);
        
        if(!empty($game_scores)){
            
            $GameScores = $Factory->getScoresForGame($this->getID());
            foreach($GameScores AS $team_id=>$Score){
                if(isset($game_scores[$team_id])){
                    $team_score = $game_scores[$team_id];
                    $Factory->saveGameScore($Score,$team_score);
                    unset($game_scores[$team_id]);
                }else{
                    $Factory->deleteGameScore($Score);
                }
            }
            if(!empty($game_scores)){
                $Teams = $this->getTeams();
                foreach($game_scores AS $team_id=>$team_score){
                    $Score = $Factory->getGameScore(null);
                    if(isset($Teams[$team_id])){
                        $Team = $Teams[$team_id];
                        $team_score['game_id'] = $this->getID();
                        $team_score['season_id'] = $this->getSeasonID();
                        $team_score['team_id'] = $Team->getID();
                        $team_score['roster_id'] = $Team->getRosterID();
                        $Factory->saveGameScore($Score,$team_score);
                    }else{
                        throw new \RuntimeException('Cannot save score for a Team not participating in this Game');
                    }
                }
            }
           
        }else{
            $success = false;
            $this->addErrorMsg('Cannot save Game Scores: No Scores provided.','Warning');
        }
        return $success;
    }

    



    public function getGameTypes():array{
        return $this->game_types;
    }

    public function getGameStatusOptions():array{
        return $this->game_status_options;
    }

}
