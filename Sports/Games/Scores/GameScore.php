<?php
namespace ElevenFingersCore\GAPPS\Sports\Games\Scores;

use ElevenFingersCore\Database\DatabaseConnectorPDO;
use ElevenFingersCore\Utilities\MessageTrait;
use ElevenFingersCore\GAPPS\InitializeTrait;

class GameScore{
    use MessageTrait;
    use InitializeTrait;

    function __construct(array $DATA){
        $this->initialize($DATA);
    }

    public function getSeasonID():int{
        return $this->DATA['season_id']??0;
    }
    public function getGameID():int{
        return $this->DATA['game_id']??0;
    }

    public function getTeamID():int{
        return $this->DATA['team_id']??0;
    }

    public function getScore():?int{    
        return $this->DATA['score']??0;
    }

    public function getResult():string{
        return $this->DATA['result']??0;
    }

    public function getStatus():?string{
        return $this->DATA['status']??0;
    }

    public function getAdditionalData():array{
        $additional_data = json_decode($this->DATA['additional'],true);
        return $additional_data??[];
    }

    public function calculateWinLoss(array $scores):?string{
        global $logger;
        $team_id = $this->DATA['team_id'];
        $this_score = isset($scores[$team_id])?$scores[$team_id]:0;
        unset($scores[$team_id]);
        if(is_null($this_score) || $this_score === ''){
            $logger->debug('ThisScore is NULL');
            $winloss = NULL;
        }else{
            $winloss = 'Win';
            $this_score = intval($this_score);
            foreach($scores AS $score){
                $int_score = intval($score);
                $logger->debug('Calculate WinLoss '.$this_score.' '. $int_score.'');
                if($int_score > $this_score){
                    $winloss = 'Loss';
                }elseif($int_score == $this_score && $winloss == 'Win'){
                    $winloss = 'Tie';
                }
            }
        }
        return $winloss;
    }

    static function compareScores(array $data):array{
        $scores = $data['game_score']??[];
        $team_ids = array_keys($scores);
        $team_1_id = $team_ids[0];
        $team_2_id = $team_ids[1];
    
        $team_1_score = $scores[$team_1_id];
        $team_2_score = $scores[$team_2_id];
    
        // Compare scores and determine results
        if ($team_1_score > $team_2_score) {
            $team_1_result = 'Win';
            $team_2_result = 'Loss';
        } elseif ($team_1_score < $team_2_score) {
            $team_1_result = 'Loss';
            $team_2_result = 'Win';
        } else {
            $team_1_result = 'Tie';
            $team_2_result = 'Tie';
        }
    
        // Return the formatted array
        return [
            $team_1_id => [
                'score' => $team_1_score,
                'result' => $team_1_result
            ],
            $team_2_id => [
                'score' => $team_2_score,
                'result' => $team_2_result
            ]
        ];
    }

    
}

