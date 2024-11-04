<?php
namespace ElevenFingersCore\GAPPS\Sports\Games\Scores;


class GameScoreMultiTeam extends GameScore{


    static function compareScores(array $data):array {
        $scores = $data['game_score']??[];
        // Sort teams by score in descending order, preserving team IDs
        arsort($scores);
        
        // Initialize an empty array to hold the ranking
        $rankings = [];
        $rank = 1;  // Start with rank 1
        $previous_score = null;  // Track the previous score to handle ties
        $team_count = 0;  // Track how many teams have the same score
        
        foreach ($scores as $team_id => $score) {
            // Check if the score is the same as the previous one for tied ranking
            if ($score === $previous_score) {
                // Assign the same rank to teams with the same score
                $rankings[$team_id] = [
                    'score' => $score,
                    'result' => $rank
                ];
                $team_count++;  // Increment the count of tied teams
            } else {
                // Update the rank to reflect the number of previous teams
                $rank += $team_count;
                $rankings[$team_id] = [
                    'score' => $score,
                    'result' => $rank
                ];
                $rank++;  // Increment the rank for the next team
                $team_count = 0;  // Reset the tied team count
            }
            
            // Set the previous score for tie detection
            $previous_score = $score;
        }
        
        return $rankings;
    }
}