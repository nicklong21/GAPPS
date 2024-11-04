<?php
namespace ElevenFingersCore\GAPPS\Sports\Games\Views;
use ElevenFingersCore\Utilities\MessageTrait;

class GameGolfView extends GameView{

    public function getEditScoreForm():string{ 
        $html = '';
        $roster_students = $this->getTeamRosters();
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
        $teams = [$hometeam, $awayteam];
        foreach($teams AS $team){
            $students = $roster_students[$team['id']]??[];
            if(!empty($students)){
                $html .= '<table class="table table-bordered table-striped my-3">';
                $html .= '<thead>
                <tr><th colspan="2">'.$team['school'].' Individual Scores</th></tr>';
                $html .= '
                </thead>
                <tbody>';
                $scores = $team['data']['scores']??[];
                foreach($students AS $student_id=>$student_name){
                    $score = $scores[$student_id]??'';
                    $html .= '<tr><td>'.$student_name.'</td><td><input type="number" name="individual_score['.$team['id'].']['.$student_id.']" value="'.(trim($score)).'"></td></tr>';
                }
                $html .= '</tbody>
                </table>';
            }
        }
        $html .= '</form>';
        return $html;
    }

}