<?php
namespace ElevenFingersCore\GAPPS\Sports;

use ElevenFingersCore\Database\DatabaseConnectorPDO;
use ElevenFingersCore\Utilities\MessageTrait;
use ElevenFingersCore\Utilities\InitializeTrait;

use function PHPUnit\Framework\isNull;

class GameScore{
    use MessageTrait;
    use InitializeTrait;
    protected $database;
    protected $id = 0;
    protected $DATA;
    static $db_table = 'games_scores';
    static $template = array(
        'id'=>0,
        'season_id'=>0,
        'school_id'=>0,
        'game_id'=>0,
        'team_id'=>0,
        'score'=>NULL,
        'result'=>'',
    );

    function __construct(DatabaseConnectorPDO $DB, ?int $id = 0, ?array $DATA = array()){
        $this->database = $DB;
        $this->initialize($id,$DATA);
    }

    public function getSeasonID():int{
        return $this->DATA['season_id'];
    }
    public function getGameID():int{
        return $this->DATA['game_id'];
    }

    public function getTeamID():int{
        return $this->DATA['team_id'];
    }

    public function getScore():?int{    
        return $this->DATA['score'];
    }

    public function getResult():string{
        return $this->DATA['result'];
    }

    public function getStatus():?string{
        return $this->DATA['status'];
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

    public static function getGameScores(DatabaseConnectorPDO $DB, ?Array $filter=array()):array{
        $Scores = array();
        $score_data = $DB->getArrayListByKey(static::$db_table, $filter);
        foreach($score_data AS $data){
            $Score = new static($DB, null, $data);
            $Scores[$Score->getTeamID()] = $Score;
        }
        return $Scores;
    }

    public function Save(?array $DATA = null):bool{
        global $logger;
        $insert = array();
        if( is_null($DATA)){
            $insert = $this->DATA;
        }else{
            foreach(static::$template AS $key=>$val){
                if(array_key_exists($key,$DATA)){
                    if($key == 'score'){
                        if($DATA[$key] === 0 || $DATA[$key] === "0"){
                            $insert[$key] = 0;
                        }else{
                            $insert[$key] = !empty($DATA[$key])?$DATA[$key]:NULL;
                        }
                    }else{
                        $insert[$key] = !empty($DATA[$key])?$DATA[$key]:$val;
                    }
                }
            }
        }
        $insert['id'] = $this->id;
        $logger->debug('GameScore::Save()',array('DATA'=>$DATA,'insert'=>$insert));
        $success = $this->database->insertArray(static::$db_table, $insert, 'id');
        if($success){
            $this->initialize(null, $insert);
        }else{
            $this->addErrorMsg($this->database->getErrorMsg());
        }
        return $success;
    }

}


?>