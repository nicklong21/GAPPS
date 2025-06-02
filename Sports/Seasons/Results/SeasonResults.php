<?php
namespace ElevenFingersCore\GAPPS\Sports\Seasons\Results;

use ElevenFingersCore\Database\DatabaseConnectorPDO;
use ElevenFingersCore\GAPPS\Sports\Seasons\Season;

class SeasonResults{

    protected $database;
    protected $Season;

    static $db_table = 'games_scores';

    function __construct(DatabaseConnectorPDO $DB, Season $Season){
        $this->database = $DB;
        $this->Season = $Season;
    }

    public function getSeasonResults(int $division_id = 0, int $region_id = 0, int $school_id = 0):array{
        $season_id = $this->Season->getID();
        $args = ['season_id'=>$season_id];
        if($school_id){
            $args['school_id'] = $school_id;
        }elseif($division_id){
            $school_ids = $this->Season->getSchoolIDsByDivision($division_id);
            $args['school_id'] = ['IN'=>$school_ids];
            if($region_id){
                $region_school_ids = $this->Season->getSchoolIDsByRegion($region_id);
                $args['school_id'] = ['IN'=>array_intersect($school_ids,$region_school_ids)];
            }
        }
        $game_results = $this->database->getArrayListByKey(static::$db_table,$args);
        $game_ids = [];
        $results_by_game_id = [];
        foreach($game_results AS $result){
            $game_id = $result['game_id'];
            if(!in_array($game_id,$game_ids)){
                $game_ids[] = $game_id;
            }
            if(empty($results_by_game_id[$game_id])){
                $results_by_game_id[$game_id] = [];
            }
            $results_by_game_id[$game_id][] = $result;
        }
        $game_data = $this->database->getArrayListByKey('games',['id'=>['IN'=>$game_ids],'status'=>'COMPLETED']);
        $season_results_by_school = [];
        foreach($game_data AS $game){
            $game_id = $game['id'];
            $type = $game['type'];
            $results = $results_by_game_id[$game_id]??[];
            foreach($results AS $game_result){
                if(!empty($game_result['school_id'])){
                    $school_id = $game_result['school_id'];
                    if(empty($season_results_by_school[$school_id])){
                        $season_results_by_school[$school_id] = [
                            'overall'=>['Win'=>0,'Loss'=>0,'Tie'=>0],
                        ];
                    }
                    if(empty($season_results_by_school[$school_id][$type])){
                        $season_results_by_school[$school_id][$type] = ['Win'=>0,'Loss'=>0,'Tie'=>0];
                    }
                    $result = $game_result['result'];
                    $season_results_by_school[$school_id][$type][$result]++;
                    if($type !== 'pre-season'){
                        $season_results_by_school[$school_id]['overall'][$result]++;
                    }

                }
            }
        }
        return $season_results_by_school;
    }
}