<?php
namespace ElevenFingersCore\GAPPS\Sports\Games\Views;

use ElevenFingersCore\GAPPS\Sports\Venues\Venue;
use ElevenFingersCore\GAPPS\Sports\Games\Teams\Team;
use ElevenFingersCore\GAPPS\Sports\Games\Scores\GameScore;
use ElevenFingersCore\Utilities\MessageTrait;


class GameView{
    use MessageTrait;
    protected $Teams = [];

    protected $teams_info = [];

    protected $Scores;
    protected $Venue;
    protected $game_details = [];

    protected $game_type;

    function __construct(){}

    public function getDetailsHTML():string{
        $html = '';
        $season_title = $this->getGameDetail('season_title');
        $game_title = $this->getGameDetail('game_title');
        $game_status = $this->getGameDetail('game_status');
        $teams_info = $this->getTeamInfo();
        $hometeam_school = $teams_info['hometeam']['school']??'';
        $awayteam_school = $teams_info['awayteam']['school']??'';
        $start_time = $this->getGameStartTime('F d, Y - g:i:s A');
        $Venue = $this->getVenue();
        $venue_html = $Venue->getAddressString();


        $html .= '<div class="title"><h3>'.$season_title.'</h3></div>';
        $html .= '<p class="center"><strong>'.$game_title.'</strong></p>';
        $html .= '<table class="table striped">';
        $html .= '<tr><th>Hometeam:<th><td>'.$hometeam_school.'</td></tr>';
        $html .= '<tr><th>Awayteam:<th><td>'.$awayteam_school.'</td></tr>';
        $html .= '<tr><th>Date:</th><td>'.$start_time.'</td></tr>';
        $html .= '<tr><th>Location:</th></td>'.$venue_html.'</td></tr>';
        if($game_status == 'Completed'){
            $html .= '<tr><th>Results:</th><td>'.$this->getResultsTable().'</td></tr>';
        }
        $html .= $Venue->getGoogleMapString();
        $html .= $Venue->getInstructionsString();
        return $html;
    }

    public function getResultsTable():string{
        $teams_info = $this->getTeamInfo();
        $hometeam = $teams_info['hometeam'];
        $awayteam = $teams_info['awayteam'];
       

        $html = '';
        $html .= '<table class="table game-scores table-bordered table-striped my-3">
        <thead><tr><th>School</th><th>Score</th><th>Game</th></tr></thead>';
        $html .= '<tbody>';
        $html .= '<tr><th><span class="'.$hometeam['result'].'">'.$hometeam['school'].'</span></th><td>'.$hometeam['score'].'</td><td class="'.$hometeam['result'].'">'.$hometeam['result'].'</td></tr>';
        $html .= '<tr><th><span class="'.$awayteam['result'].'">'.$awayteam['school'].'</span></th><td>'.$awayteam['score'].'</td><td class="'.$awayteam['result'].'">'.$awayteam['result'].'</td></tr>';
        
        $html .= '</table>';
        return $html;
    }

    public function getEditScoreForm():string{
        $html = '';
        $teams_info = $this->getTeamInfo();
        $hometeam = $teams_info['hometeam'];
        $awayteam = $teams_info['awayteam'];
        $html = '<form class="edit-scores">';
        $html .= '<table class="table game-scores table-bordered table-striped my-3">
        <thead><tr><th>School</th><th>Score</th></tr></thead>';
        $html .= '<tbody>';
        $html .= '<tr><th>'.$hometeam['school'].'</th><td><input type="number" name="game_score['.$hometeam['id'].']" value="'.$hometeam['score'].'"></td></tr>';
        $html .= '<tr><th>'.$awayteam['school'].'</th><td><input type="number" name="game_score['.$awayteam['id'].']" value="'.$awayteam['score'].'"></td></tr>';
        $html .= '</table>';
        $html .= '</form>';
        return $html;
    }

    protected function getTeamInfo():array{

        if(empty($this->teams_info)){
            $Teams = $this->getTeams();
            $Scores = $this->getGameScores();
            $this->teams_info = ['hometeam'=>[],'awayteam'=>[]];
            foreach($Teams AS $Team){
                $school = $Team->getSchoolName();
                $TeamScore = $Scores[$Team->getID()]??null;
                $score = !empty($TeamScore)?$TeamScore->getScore():'';
                $result = !empty($TeamScore)?$TeamScore->getResult():'';
                $data = !empty($TeamScore)?$TeamScore->getAdditionalData():[];
                if($Team->isHomeTeam()){
                    $this->teams_info['hometeam'] = [
                        'id'=>$Team->getID(),
                        'school'=>$school,
                        'score'=>$score,
                        'result'=>$result,
                        'data'=>$data,
                    ];
                }else{
                    $this->teams_info['awayteam'] = [
                        'id'=>$Team->getID(),
                        'school'=>$school,
                        'score'=>$score,
                        'result'=>$result,
                        'data'=>$data,
                    ];
                }
            }
        }
        return $this->teams_info;
    }

    public function setGameDetail(string $key, $value){
        $this->game_details[$key] = $value;
    }

    public function getGameDetail(string $key){
        return $this->game_details[$key]??null;
    }

    public function setGameType(string $type){
        $this->game_type = $type;
    }

    public function getGameType():string{
        return $this->game_type;
    }

    public function getGameStartTime(?string $format = null){
        $start_time = $this->getGameDetail('start_time');
        $StartTime = null;
        if(is_string($start_time)){
            $StartTime = new \DateTimeImmutable($start_time);
        }
        return $format?$StartTime->format($format):$StartTime;
    }

    public function setVenue(Venue $Venue){
        $this->Venue = $Venue;
    }

    public function getVenue():Venue{
        return $this->Venue;
    }

    /**
     * Summary of setGameScores
     * @param GameScore[] $GameScores
     * @return void
     */
    public function setGameScores(array $GameScores){
        $this->Scores = $GameScores;
    }
    /**
     * Summary of getGameScores
     * @return GameScore[]
     */
    public function getGameScores():array{
        return $this->Scores;
    }

    /**
     * Summary of setTeams
     * @param Team[] $Teams
     * @return void
     */
    public function setTeams(array $Teams){
        $this->Teams = $Teams;
    }

    /**
     * Summary of getTeams
     * @return Team[]
     */
    public function getTeams():array{
        return $this->Teams;
    }

    public function getTeamRosters():array{
        $rosters = [];
        $Teams = $this->getTeams();
        foreach($Teams AS $Team){
            $students = [];
            $Roster = $Team->getRoster();
            if(!empty($Roster)){
                $RosterStudents = $Roster->getRosterStudents();
                foreach($RosterStudents AS $Student){
                    /* roster_students.id*/
                    $students[$Student->getID()] = $Student->getName();
                }
            }
            $rosters[$Team->getID()] = $students;
        }
        return $rosters;
    }
}


