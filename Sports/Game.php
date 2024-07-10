<?php
namespace ElevenFingersCore\GAPPS\Sports;

use DateTimeImmutable;
use ElevenFingersCore\Database\DatabaseConnectorPDO;
use ElevenFingersCore\Utilities\MessageTrait;
use ElevenFingersCore\Utilities\InitializeTrait;

class Game{
    use MessageTrait;
    use InitializeTrait;
    protected $database;
    protected $id = 0;
    protected $DATA;
    protected $StartDate;

    protected $Season;

    protected $Teams = array();
    protected $Scores;
    protected $Venue;
    protected $is_locked = true;
    static $db_table = 'games';
    static $template = array(
        'id'=>0,
        'title'=>'',
        'type'=>null,
        'date'=>'0000-00-00',
        'start_time'=>'00:00:00',
        'season_id'=>0,
        'region_id'=>0,
        'division_id'=>0,
        'venue_id'=>0,
        'status'=>'',
        'note'=>'',
    );

    function __construct(DatabaseConnectorPDO $DB, ?int $id = 0, ?array $DATA = array()){
        $this->database = $DB;
        $this->initialize($id,$DATA);
        if($this->DATA['status'] != 'Completed'){
            $this->is_locked = false;
        }
    }

    public static function findGames(DatabaseConnectorPDO $DB, array $filter = array(), null|array|string $order = null, ?int $school_id = 0):array{
        $Games = array();
        if(empty($school_id)){
            $DATA = $DB->getArrayListByKey(static::$db_table,$filter, $order);
        }else{
            $WHERE = $DB->buildQueryString($filter);
            $ORDER = $DB->buildOrderString($order);
            $sql = 'SELECT g.* FROM '.static::$db_table.' g, '.Team::$db_table.' t WHERE '.$WHERE['query_str'].' AND t.game_id = g.id AND t.school_id = :school_id  '.$ORDER;
            $WHERE['args'][':school_id'] = $school_id;
            $DATA = $DB->getResultArrayList($sql,$WHERE['args']);
        }
        foreach($DATA AS $data){
            
            $Games[] = new static($DB, null, $data);
        }
        return $Games;
    }

    public function setIsLocked(bool $v){
        $this->is_locked = $v;
    }

    public function isResultsLocked():bool{
        return $this->is_locked;
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

    /** Team[] */
    public function getTeams():array{
        if(empty($this->Teams)){
            $this->Teams = Team::getTeams($this->database,array('game_id'=>$this->id));
        }
        return $this->Teams;
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

    public function addTeam(Team $Team){
        $this->Teams[] = $Team;
    }

    /** @return GameScore[] */
    public function getScores():?array{
        if(empty($this->Scores)){
            $this->Scores = GameScore::getGameScores($this->database,array('game_id'=>$this->id));
        }
        return $this->Scores;
    }

    public function addScore(GameScore $Score){
        $this->Scores[] = $Score;
    }

    public function getTitle():string{
        return $this->DATA['title'];
    }

    public function getType():string{
        return $this->DATA['type'];
    }

    public function getSeasonID():int{
        return $this->DATA['season_id'];
    }

    public function getRegionID():int{
        return $this->DATA['region_id'];
    }

    public function getDivisionID():int{
        return $this->DATA['division_id'];
    }

    public function getStartDate(?string $format):NULL|string|DateTimeImmutable{
        $date = null;
        if(empty($this->StartDate)){
            if(!empty($this->DATA['date']) && $this->DATA['date'] != '0000-00-00'){
                $this->StartDate = new DateTimeImmutable($this->DATA['date'].' '.$this->DATA['start_time']);
            }
        }
        if(!empty($this->StartDate)){
            if(!empty($format)){
                $date = $this->StartDate->format($format);
            }else{
                $date = $this->StartDate;
            }
        }
        return $date;
    }

    public function getVenue():?Venue{
        if(empty($this->Venue)){
            if(!empty($this->DATA['venue_id'])){
                $this->Venue = new Venue($this->database, $this->DATA['venue_id']);
            }
        }
        return $this->Venue;
    }

    public function setVenue(Venue $Venue){
        $this->Venue = $Venue;
    }

    public function getStatus():?string{
        return $this->DATA['status'];
    }

    public function getResultsHTML():string{
        $Teams = $this->getTeams();
        $GameScores = $this->getScores();
        $home_teams = array(); 
        $away_teams = array();

        foreach($Teams AS $Team){
            if(isset($GameScores[$Team->getID()])){
                $Team->setGameScore($GameScores[$Team->getID()]);
            }
            if($Team->isHomeTeam()){
                $home_teams[] = $Team;
            }else{
                $away_teams[] = $Team;
            }
        }
        $html = '<table class="data-table game-scores">
        <thead><tr><th>Win/Loss</th><th>Team</th><th>Score</th></tr></thead>';
        $html .= '<tbody>';
        foreach($home_teams AS $Team){
            if($this->isResultsLocked()){
                $html .= $this->getResultsRow_locked($Team);
            }else{
                $html .= $this->getResultsRow_open($Team);
            }
        }
        foreach($away_teams AS $Team){
            if($this->isResultsLocked()){
                $html .= $this->getResultsRow_locked($Team);
            }else{
                $html .= $this->getResultsRow_open($Team);
            }
        }
        $html .= '</tbody><table>';

        return $html;
    }

    protected function getResultsRow_locked(Team $Team):string{
        $html = '<tr data-team_id="'.$Team->getID().'"><td>'.$Team->getWinLoss().'</td><td>'.$Team->getSchoolName().'</td><td>'.$Team->getScoreTotal().'</td></tr>';
        return $html;
    }
    protected function getResultsRow_open(Team $Team):string{
        $html = '<tr data-team_id="'.$Team->getID().'"><td>'.$Team->getWinLoss().'</td><td>'.$Team->getSchoolName().'</td><td><input type="text" name="score['.$Team->getID().']" value="'.$Team->getScoreTotal().'"></td></tr>';
        return $html;
    }


    public function saveGame($data):bool{
        $success = true;
        $Season = $this->getSeason();
        $StartDate = new DateTimeImmutable($data['game_date']);
        $this->database->beginTransaction();
        $insert = array(
            'type'=>$data['type'],
            'date'=>$StartDate->format('Y-m-d'),
            'start_time'=>$StartDate->format('H:i:s'),
            'season_id'=>$Season->getID(),
            'status'=>$data['game_status'],           
        );
        $success = $this->Save($insert);
        if($success){
            $success = $this->saveGameTeams($data);
        }
        if($success){
            $Teams = $this->getTeams();
            $game_title = '';
            $HomeTeam = $this->getHomeTeam();
            foreach($Teams AS $Team){
                if(!$Team->isHomeTeam()){
                    $game_title = $Team->getSchoolName();
                    break;
                }
            }
            if(!empty($HomeTeam)){
                $game_title .= ' @ '.$HomeTeam->getSchoolName();
            }

            $success = $this->Save(array('title'=>$game_title));
        }
        
        if($success){
            $success = $this->saveGameScores($data);
        }

        if($success){
            $this->database->commitTransaction();
        }else{
            $this->database->rollbackTransaction();
        }

        return $success;
    }

    protected function saveGameTeams(Array $data):bool{
        $success = true;
        $Season = $this->getSeason();
        $hometeams_jsonstr = isset($data['hometeams'])? $data['hometeams']:array();
        $awayteams_jsonstr = isset($data['awayteams'])? $data['awayteams']:array();
        $allteams = array();
        foreach($hometeams_jsonstr AS $str){
            $team_data = json_decode($str,true);
            $team_data['type'] = 'hometeam';
            $allteams[$team_data['school_id']] = $team_data;
        }
        foreach($awayteams_jsonstr AS $str){
            $team_data = json_decode($str,true);
            $team_data['type'] = 'awayteam';
            $allteams[$team_data['school_id']] = $team_data;
        }
        $Teams = $this->getTeams();
        foreach($Teams AS $Team){
            $team_id = $Team->getID();
            $school_id = $Team->getSchoolID();
            if(!isset($allteams[$school_id])){
                $Team->Delete();
            }else{
                unset($allteams[$school_id]);
            }   
        }
        if(!empty($allteams)){
            foreach($allteams AS $insert){
                $newTeam = new Team($this->database);
                $insert['game_id'] = $this->getID();
                $insert['season_id'] = $Season->getID();
                if(!$newTeam->Save($insert)){
                    $success = false;
                    $this->addErrorMsg($newTeam->getErrorMsg());
                    break;
                };
            }
        }
        $this->Teams = null;
        return $success;
    }

    protected function saveGameScores(Array $data):bool{
        $success = true;
        $Season = $this->getSeason();
        $Teams = $this->getTeams();
        $scores = isset($data['score'])?$data['score']:array();
        $CurrentScores = $this->getScores();
        foreach($Teams AS $Team){
            $team_id = $Team->getID();
            $score_value = isset($scores[$team_id])?$scores[$team_id]:NULL;
            $insert = array('score'=>$score_value);
            if(!isset($CurrentScores[$team_id])){
                $ThisScore = new GameScore($this->database);
                $insert['season_id'] = $Season->getID();
                $insert['game_id'] = $this->getID();
                $insert['team_id'] = $Team->getID();
                $insert['school_id'] = $Team->getSchoolID();
            }else{
                $ThisScore = $CurrentScores[$team_id];
            }
            $insert['result'] = $ThisScore->calculateWinLoss($scores);
            $ThisScore->Save($insert);
        }
        $this->Scores = null;
        return $success;
    }

}
