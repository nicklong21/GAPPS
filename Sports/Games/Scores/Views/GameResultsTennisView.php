<?php
namespace ElevenFingersCore\GAPPS\Sports\Games\Results\Views;
use ElevenFingersCore\Utilities\MessageTrait;

class GameResultsMultiTeamView extends GameResultsView{


    protected function getResultsString_Best3of5(string $winloss, array $data):string{
        $team_title = $data['team_title'];
        $set_scores = $data['set_scores'];
        $team_type = $data['team_type'];
        $html = '';
        $html .= '<table class="table game-scores tennis-score">';
        $html .= '<thead><tr><th rowspan="2"><span class="'.$winloss.'">'.$team_title.'</span></th><th colspan="5"># Games Won per Set</th></tr>
        <tr><th>Set<br/>1</th><th>Set<br/>2</th><th>Set<br/>3</th></tr>
        </thead>';
        for($j=1; $j<=5; $j++){
            $t = $j>=4?'(Doubles)':'(Single)';
            $html .= '<tr><th>Line '.$j.' '.$t.'</th>';
            $this_line = isset($set_scores[$j])?$set_scores[$j]:null;
            $limit = 3;
            if($this_line){
                $html .= '<td><span class="'.$this_line['line_result'].'">';
                $html .= $this_line['h1_student_name'];
                if($j>=4){
                    $html .= '<br/>'.$this_line['h2_student_name'];
                }
                $html .= '</span></td>';
                
                for($i=1; $i<=$limit; $i++){
                    $html .= '<td><span class="responsive-label">Set '.$i.'</span>';
                    $this_set = !empty($this_line->line_game_sets[$i])?$this_line->line_game_sets[$i]:null;
                    if($this_set){
                        if($team_type == 'home'){
                            $result_class = $this->getWinLossString($this_set['home'],$this_set['away']);
                        }else{
                            $result_class = $this->getWinLossString($this_set['away'],$this_set['home']);
                        }
                        $html .= '<span class="'.$result_class.'">'.$this_set[$team_type].'</span>';
                    }
                    $html .= '</td>';  
                }
            }else{
                $html .= '<td></td>';
                for($i=1; $i<=$limit; $i++){
                    $html .= '<td></td>';
                    }
            }
            $html .= '</tr>';
        
        }
        
        $html .= '</tbody></table>';
       
        return $html;
    }

    protected function getResultsString_8GamePro():string{
        $ranked_team_scores = [];
        $html = '';
        $html .= '<table class="table game-scores multiteam">
        <thead><tr><th>Team</th><th>Score</th><th>Ranking</th></tr></thead>';
        
        
        $html .= '</tbody></table>';
       
        return $html;
    }


    protected function getWinLossString($score1, $score2):string{
        return ($score1>$score2)?'Win':(($score1<$score2)?'Loss':'Tie');
    }
}