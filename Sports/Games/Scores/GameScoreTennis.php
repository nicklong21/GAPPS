<?php
namespace ElevenFingersCore\GAPPS\Sports\Games\Scores;


class GameScoreTennis extends GameScore{


    static function compareScores(array $data):array{
        $team_ids = array_keys($data['line']);
        $team1_id = $team_ids[0];
        $team2_id = $team_ids[1];
        $game_scores = [
            $team1_id => ['score'=>0,'result'=>'','additional'=>[]],
            $team2_id => ['score'=>0,'result'=>'','additional'=>[]],
        ];
        $line_count = 0;
        $team1_data = $data['line'][$team1_id];
        $team2_data = $data['line'][$team2_id];

        foreach ($team1_data as $line => $team1_line_data) {
            $team2_line_data = $team2_data[$line];
            
            // Compare each student's scores
            $team1_wins = 0;
            $team2_wins = 0;
            
            for ($i = 0; $i < count($team1_line_data['score']); $i++) {
                if ($team1_line_data['score'][$i] !== "" && $team2_line_data['score'][$i] !== "") {
                    if ((int)$team1_line_data['score'][$i] > (int)$team2_line_data['score'][$i]) {
                        $team1_wins++;
                    } elseif ((int)$team1_line_data['score'][$i] < (int)$team2_line_data['score'][$i]) {
                        $team2_wins++;
                    }
                }
            }
            
            // Determine the result for this line (individual result)
            $line_result_1 = 'Tie';
            $line_result_2 = 'Tie';
            
            if ($team1_wins > $team2_wins) {
                $line_result_1 = 'Win';
                $line_result_2 = 'Loss';
                $game_scores[$team1_id]['score']++;
            } elseif ($team2_wins > $team1_wins) {
                $line_result_1 = 'Loss';
                $line_result_2 = 'Win';
                $game_scores[$team2_id]['score']++;
            }
            
            // Add individual scores and results to the 'additional' field
            $game_scores[$team1_id]['additional'][$line] = [
                'roster_student_id' => $team1_line_data['roster_student_id'],
                'student_name'=>$team1_line_data['student_name'],
                'roster_student_id_2'=>$team1_line_data['roster_student_id_2'],
                'student_name_2'=>$team1_line_data['student_name_2'],
                'result' => $line_result_1,
                'score' => $team1_line_data['score']
            ];
            
            $game_scores[$team2_id]['additional'][$line] = [
                'roster_student_id' => $team2_line_data['roster_student_id'],
                'student_name'=>$team2_line_data['student_name'],
                'roster_student_id_2'=>$team2_line_data['roster_student_id_2'],
                'student_name_2'=>$team2_line_data['student_name_2'],
                'result' => $line_result_2,
                'score' => $team2_line_data['score']
            ];
            
            $line_count++;
        }
        
        // Determine the overall team result based on total matches won
        if ($game_scores[$team1_id]['score'] > $game_scores[$team2_id]['score']) {
            $game_scores[$team1_id]['result'] = 'Win';
            $game_scores[$team2_id]['result'] = 'Loss';
        } elseif ($game_scores[$team1_id]['score'] < $game_scores[$team2_id]['score']) {
            $game_scores[$team1_id]['result'] = 'Loss';
            $game_scores[$team2_id]['result'] = 'Win';
        } else {
            $game_scores[$team1_id]['result'] = 'Tie';
            $game_scores[$team2_id]['result'] = 'Tie';
        }
        $game_scores[$team1_id]['additional'] = json_encode($game_scores[$team1_id]['additional']);
        $game_scores[$team2_id]['additional'] = json_encode($game_scores[$team2_id]['additional']);

        return $game_scores;
    }
}