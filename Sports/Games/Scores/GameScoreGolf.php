<?php
namespace ElevenFingersCore\GAPPS\Sports\Games\Scores;


class GameScoreGolf extends GameScore{


    public function getIndividualScore(int $student_id):?int{
        global $logger;
        $data = $this->getAdditionalData();
        $score = $data['scores'][$student_id]??null;
        return $score;
    }

    static function compareScores(array $data):array{

        $scores = $data['game_score']??[];
        $team_ids = array_keys($scores);
        $team_1_id = $team_ids[0];
        $team_2_id = $team_ids[1];
    
        $team_1_score = $scores[$team_1_id];
        $team_2_score = $scores[$team_2_id];
    
        // Compare scores and determine results
        if ($team_1_score < $team_2_score) {
            $team_1_result = 'Win';
            $team_2_result = 'Loss';
        } elseif ($team_1_score > $team_2_score) {
            $team_1_result = 'Loss';
            $team_2_result = 'Win';
        } else {
            $team_1_result = 'Tie';
            $team_2_result = 'Tie';
        }
    
        // Return the formatted array
        $game_scores =  [
            $team_1_id => [
                'score' => $team_1_score,
                'result' => $team_1_result
            ],
            $team_2_id => [
                'score' => $team_2_score,
                'result' => $team_2_result
            ]
        ];
    
        foreach($team_ids AS $team_id){
            $individual_scores = $data['individual_score'][$team_id]??[];
            $game_scores[$team_id]['additional'] = json_encode(['scores'=>$individual_scores]);
        }
        return $game_scores;
    }
}