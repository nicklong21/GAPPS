<?php
namespace ElevenFingersCore\GAPPS\Sports\Games\Views;
use ElevenFingersCore\Utilities\MessageTrait;

class GameVolleyballView extends GameView{


    public function getResultsTable():string{
        $teams_info = $this->getTeamInfo();
        $hometeam = $teams_info['hometeam']?$teams_info['hometeam']:['school'=>'','result'=>'','score'=>'','data'=>[],'students'=>[]];
        $awayteam = $teams_info['awayteam']?$teams_info['awayteam']:['school'=>'','result'=>'','score'=>'','data'=>[],'students'=>[]];
        
        $html = '<table class="table game-scores tennis_scores table-bordered table-striped my-3">';
        $html .= '<thead>
            <tr><th>School</th><th>Set<br/>1</th><th>Set<br/>2</th><th>Set<br/>3</th><th>Set<br/>4</th><th>Set<br/>5</th><th>Match</th></tr>
        </thead>';
        $html .= '<tbody>';
        $html .= '<tr>
        <th><span class="'.$hometeam['result'].'">'.$hometeam['school'].'</span></th>
        ';
        $home_line_data = $hometeam['data']??['sets'=>[1=>'',2=>'',3=>'',4=>'',5=>'']];
        for($i = 1; $i <= 5; $i++){
            $set_score = $home_line_data['sets'][$i]??'';
            $html .= '<td>'.$set_score.'</td>';
        }
        $html .= '<td class="'.$hometeam['result'].'">'.$hometeam['result'].'</td>
        </tr>';

        $html .= '<tr>
        <th><span class="'.$awayteam['result'].'">'.$awayteam['school'].'</span></th>
        ';
        $away_line_data = $awayteam['data']??['sets'=>[1=>'',2=>'',3=>'',4=>'',5=>'']];
        for($i = 1; $i <= 5; $i++){
            $set_score = $away_line_data['sets'][$i]??'';
            $html .= '<td>'.$set_score.'</td>';
        }
        $html .= '<td class="'.$awayteam['result'].'">'.$awayteam['result'].'</td>
        </tr>';
        
        $html .= '</tbody>';
        $html .= '</table>';
        return $html;
    }


    public function getEditScoreForm():string{ 
        $html = '';
        $teams_info = $this->getTeamInfo();
        $hometeam = $teams_info['hometeam']?$teams_info['hometeam']:['id'=>0,'school'=>'','result'=>'','score'=>'','data'=>[],'students'=>[]];
        $awayteam = $teams_info['awayteam']?$teams_info['awayteam']:['id'=>0,'school'=>'','result'=>'','score'=>'','data'=>[],'students'=>[]];
        $html = '<form class="edit-scores">';
        $html .= '<table class="table game-scores table-bordered table-striped my-3">
        <thead><tr><th>School</th><th>Set<br/>1</th><th>Set<br/>2</th><th>Set<br/>3</th><th>Set<br/>4</th><th>Set<br/>5</th></tr></thead>';
        $html .= '<tbody>';
        if(!empty($hometeam['school'])){
        $html .= '<tr><th>'.$hometeam['school'].'</th>';
        $home_line_data = $hometeam['data']??['sets'=>[1=>'',2=>'',3=>'',4=>'',5=>'']];
        for($i = 1; $i <= 5; $i++){
            $set_score = $home_line_data['sets'][$i]??'';
            $html .= '<td><input type="number" name="sets['.$hometeam['id'].']['.$i.']" value ="'.$set_score.'"></td>';
        }
        $html .= '</tr>';
        }else{
            $html .= '<tr><td colspan="6">No Hometeam Selected.</td></tr>';
        }
        if(!empty($awayteam['school'])){
        $html .= '<tr><th>'.$awayteam['school'].'</th>';
        $away_line_data = $awayteam['data']??['sets'=>[1=>'',2=>'',3=>'',4=>'',5=>'']];
        for($i = 1; $i <= 5; $i++){
            $set_score = $away_line_data['sets'][$i]??'';
            $html .= '<td><input type="number" name="sets['.$awayteam['id'].']['.$i.']" value ="'.$set_score.'"></td>';
        }
        $html .= '</tr>';
        }else{
            $html .= '<tr><td colspan="6">No Awayteam Selected.</td></tr>';
        }
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
                $additional_data = !empty($TeamScore)?$TeamScore->getAdditionalData():[];
                $Roster = $Team->getRoster();
                $students = [];
                if($Roster){
                    $RosterStudents = $Roster->getRosterStudents();
                    foreach($RosterStudents AS $Student){
                        $students[$Student->getStudentID()] = $Student->getName(); 
                    }
                }
                $team_data = [
                    'id'=>$Team->getID(),
                    'school'=>$school,
                    'score'=>$score,
                    'result'=>$result,
                    'data'=>$additional_data,
                    'students'=>$students,
                ];
                if($Team->isHomeTeam()){
                    $this->teams_info['hometeam'] = $team_data;
                }else{
                    $this->teams_info['awayteam'] = $team_data;
                }
            }
        }
        return $this->teams_info;
    }


}