<?php
namespace ElevenFingersCore\GAPPS\Sports\Games\Scores;


class GameScoreVolleyball extends GameScore{


    static function compareScores(array $data):array{
        $team_ids = array_keys($data['sets']);
        $team1_id = $team_ids[0];
        $team2_id = $team_ids[1];
        $game_scores = [
            $team1_id => ['score'=>0,'result'=>'','additional'=>[]],
            $team2_id => ['score'=>0,'result'=>'','additional'=>[]],
        ];
        $line_count = 0;
        $team1_data = $data['sets'][$team1_id];
        $team2_data = $data['sets'][$team2_id];

        // Compare each student's scores
        $team1_wins = 0;
        $team2_wins = 0;
        
        for ($i = 1; $i <= count($team1_data); $i++) {
            if(intval($team1_data[$i]) < intval($team2_data[$i])){
                $team2_wins++;
            }elseif(intval($team1_data[$i]) > intval($team2_data[$i])){
                $team1_wins++;
            }
        }
        
        // Determine the result for this line (individual result)
        $game_scores[$team1_id]['result'] = 'Tie';
        $game_scores[$team1_id]['score'] = $team1_wins;
        $game_scores[$team1_id]['additional'] = json_encode(['sets'=>$team1_data]);
        $game_scores[$team2_id]['result'] = 'Tie';
        $game_scores[$team2_id]['score'] = $team2_wins;
        $game_scores[$team2_id]['additional'] = json_encode(['sets'=>$team2_data]);
        
        if ($team1_wins > $team2_wins) {
            $game_scores[$team1_id]['result'] = 'Win';
            $game_scores[$team2_id]['result'] = 'Loss';
        } elseif ($team2_wins > $team1_wins) {
            $game_scores[$team1_id]['result'] = 'Loss';
            $game_scores[$team2_id]['result'] = 'Win';
        }
            
        return $game_scores;
    }
}