<?php
namespace ElevenFingersCore\GAPPS\Sports\Games\Views;
use ElevenFingersCore\Utilities\MessageTrait;
use ElevenFingersCore\Utilities\SortableObject;
use ElevenFingersCore\Utilities\UtilityFunctions;

class GameMultiTeamView extends GameView{


    public function getDetailsView():string{
        $html = '';
        $season_title = $this->getGameDetail('season_title');
        $game_title = $this->getGameDetail('game_title');
        $game_status = $this->getGameDetail('game_status');
        $teams_info = $this->getTeamInfo();
        $hometeam_school = $teams_info['hometeam']['school']??'';
        $awayteam_schools = [];
        if(!empty($teams_info['awayteam'])){
            foreach($teams_info['awayteam'] AS $team){
                $awayteam_schools[] = $team['school'];
            }
        }
        $start_time = $this->getGameStartTime('F d, Y - g:i:s A');
        $Venue = $this->getVenue();
        $venue_html = $Venue->getAddressString();

        $html .= '<div class="title"><h3>'.$season_title.'</h3></div>';
        $html .= '<p class="center"><strong>'.$game_title.'</strong></p>';
        $html .= '<table class="table striped">';
        $html .= '<tr><th>Host:<th><td>'.$hometeam_school.'</td></tr>';
        foreach($awayteam_schools AS $awayteam){
            $html .= '<tr><th>Attending:<th><td>'.$awayteam.'</td></tr>';
        }
        
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
        $ranked_team_scores = $this->getRankedScores();
        $html = '';
        $html .= '<table class="table game-scores multiteam">
        <thead><tr><th>Team</th><th>Score</th><th>Ranking</th></tr></thead>';
        foreach($ranked_team_scores AS $team){
            $ordinal_rank = UtilityFunctions::getOrdinal($team['rank']);
            $html .= '<tr><td>'.$team['title'].'</td><td>'.$team['score'].'</td><td>'.$ordinal_rank.'</td></tr>';
        }
        $html .= '</tbody></table>';
        return $html;
    }

    protected function getRankedScores():array{
        $ranked_scores = [];
        $Teams = $this->getTeams();
        $Scores = $this->getGameScores();
        $teams_data = array();
        foreach($Teams AS $Team){
            $data = [
                'id'=>$Team->getID(),
                'school'=>$Team->getSchoolName(),
                'score'=>null,
                'rank'=>0,
            ];
            $Score = $Scores[$Team->getID()]??null;
            if($Score){
                $data['score'] = $Score->getScore();
                $data['rank'] = $Score->getResult();
            }
            $teams_data[] = $data;
        }
        $Sortable = new SortableObject('rank');
        $Sortable->Sort($teams_data);
        return $teams_data;
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
        foreach($awayteam AS $teamdata){
            $html .= '<tr><th>'.$teamdata['school'].'</th><td><input type="number" name="game_score['.$teamdata['id'].']" value="'.$teamdata['score'].'"></td></tr>';
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
                if($Team->isHomeTeam()){
                    $this->teams_info['hometeam'] = [
                        'school'=>$school,
                        'score'=>$score,
                        'result'=>$result,
                        'id'=>$Team->getID(),
                    ];
                }else{
                    $this->teams_info['awayteam'][] = [
                        'school'=>$school,
                        'score'=>$score,
                        'result'=>$result,
                        'id'=>$Team->getID(),
                    ];
                }
            }
        }
        return $this->teams_info;
    }

}