<?php
namespace ElevenFingersCore\GAPPS\Sports\Games\Results\Views;
use ElevenFingersCore\Utilities\MessageTrait;

class GameResultsMultiTeamView extends GameResultsView{


    protected function getResultsString_default():string{
        $ranked_team_scores = $this->getRankedScores();
        $html = '';
        $html .= '<table class="table game-scores multiteam">
        <thead><tr><th>Team</th><th>Score</th><th>Ranking</th></tr></thead>';
        foreach($ranked_team_scores AS $team){
            $html .= '<tr><td>'.$team['title'].'</td><td>'.$team['score'].'</td><td>'.$team['rank'].'</td></tr>';
        }
        
        $html .= '</tbody></table>';
       
        return $html;
    }
}