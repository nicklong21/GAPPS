<?php

namespace ElevenFingersCore\GAPPS\Sports\Games\Scores\Dependencies;

use DateTimeImmutable;
use ElevenFingersCore\Database\DatabaseConnectorPDO;
use ElevenFingersCore\GAPPS\Sports\Games\Game;
use ElevenFingersCore\GAPPS\Sports\Games\GameFactory;
use ElevenFingersCore\GAPPS\Sports\Games\Scores\GameScore;

class PitchCountFactory extends DependencyFactory{

    private $database;
    static $db_table = 'pitch_count';

    function __construct(DatabaseConnectorPDO $DB){
        $this->database = $DB;
    }


    public function initGameScoreDependency(GameScore $GameScore, string $type){}


    public function initGameScoresDependency(array $GameScores, string $type){}

    public function enterPitchCount(Game $Game, $DATA){

        $game_id = $Game->getID();
        $season_id = $Game->getSeasonID();
        $game_date = $Game->getStartDate('Y-m-d H:i:s');
        $pitch_count = isset($DATA['pitch_count'])?$DATA['pitch_count']:[];
        $current_records = $this->getGamePitchCount($game_id);
        foreach($pitch_count AS $student_id=>$count){
            $insert = [
                'id'=>0,
                'roster_student_id'=>$student_id,
                'count'=>intval($count),
                'game_id'=>$game_id,
                'season_id'=>$season_id,
                'date'=>$game_date,
            ];
            if(array_key_exists($student_id,$current_records)){
                $insert['id'] = $current_records[$student_id]['id'];
                unset($current_records[$student_id]);
            }
            $this->database->insertArray(static::$db_table,$insert,'id');
        }
        if(!empty($current_records)){
            $unset = [];
            foreach($current_records AS $student_id=>$record){
                $unset[] = $record['id'];
            }
            $this->database->deleteByKey(static::$db_table,['id'=>['IN'=>$unset]]);
        }
        
    }

    public function getGamePitchCount(int $game_id):array{

        $records = $this->database->getArrayListByKey(static::$db_table, ['game_id'=>$game_id],null,['key_name'=>'roster_student_id']);
        return $records;
    }

    public function getSeasonPitches(?int $season_id, ?array $student_ids = null){
        $args = ['count'=>['!='=>0]];
        if(!empty($season_id)){
            $args['season_id'] = $season_id;
        }
        if(!is_null($student_ids)){
            $args['roster_student_id'] = ['IN'=>$student_ids];
        }
        $records = $this->database->getArrayListByKey(static::$db_table,$args,'date DESC');
        if(!empty($student_ids)){
            $pitch_count = array_fill_keys($student_ids,[]);
        }else{
            $pitch_count = [];
        }
        foreach($records AS $r){
            if(empty($pitch_count[$r['roster_student_id']])){
                $pitch_count[$r['roster_student_id']] = [];
            }
            if(empty($pitch_count[$r['roster_student_id']][$r['date']])){
                $pitch_count[$r['roster_student_id']][$r['date']] = $r['count'];
            }else{
                $pitch_count[$r['roster_student_id']][$r['date']] += $r['count'];
            }
            
        }
        return $pitch_count;
    }



    public function saveGameScoreDependencies(GameScore $GameScore, array $DATA):bool{
        //$this->enterPitchCount($GameScore,$DATA);
        return true;
    }


}