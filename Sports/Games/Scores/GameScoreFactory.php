<?php
namespace ElevenFingersCore\GAPPS\Sports\Games\Scores;
use ElevenFingersCore\Database\DatabaseConnectorPDO;
use ElevenFingersCore\GAPPS\FactoryTrait;
use ElevenFingersCore\Utilities\MessageTrait;

class GameScoreFactory{
    use FactoryTrait;
    use MessageTrait;
    protected $dependencies;
    protected $season_id; 
    protected $Games = [];
    protected $db_table = 'games_scores';
    protected $schema = [
        'id'=>0,
        'season_id'=>0,
        'roster_id'=>0,
        'game_id'=>0,
        'team_id'=>0,
        'score'=>0,
        'result'=>'',
        'additional'=>'',
    ];

    function __construct(DatabaseConnectorPDO $DB, array $dependencies){
        $this->database = $DB;
        $this->dependencies = $dependencies;
        $this->setItemClass($dependencies['score']);
    }

    public function setSeasonID(int $id){
        $this->season_id = $id;
    }

    public function getSeasonID():int{
        return $this->season_id;
    }

    public function getGameScore(?int $id = 0, ?array $DATA = []):GameScore{
        $GameScore = $this->getItem($id,$DATA);
        return $GameScore;
    }

    public function getTeamScoreForGame(int $team_id, int $game_id):GameScore{
        $filter = ['team_id'=>$team_id,'game_id'=>$game_id];
        $DATA = $this->database->getArrayByKey($this->db_table,$filter);
        return $this->getGameScore(null, $DATA);
    }

    public function getTeamScoresForSeason(int $team_id):array{
        $filter = ['team_id'=>$team_id];
        $score_data = $this->database->getArrayListByKey($this->db_table, $filter);
        $Scores = [];
        foreach($score_data AS $DATA){
            $Scores[$DATA['game_id']] = $this->getGameScore(null, $DATA);
        }
        return $Scores;
    }
    public function getRosterScoresForSeason(int $roster_id):array{
        $filter = ['roster_id'=>$roster_id];
        $score_data = $this->database->getArrayListByKey($this->db_table, $filter);
        $Scores = [];
        foreach($score_data AS $DATA){
            $Scores[$DATA['game_id']] = $this->getGameScore(null, $DATA);
        }
        return $Scores;
    }

    public function getScoresForGame(int $game_id):array{
        $filter = ['game_id'=>$game_id];
        $score_data = $this->database->getArrayListByKey($this->db_table, $filter);
        $Scores = [];
        foreach($score_data AS $DATA){
            $Scores[$DATA['team_id']] = $this->getGameScore(null, $DATA);
        }
        return $Scores;
    }

    public function initGameScoreDependencies(array $Scores){
        $dependency_list = $this->getDependencyValue('game_score_dependencies');
        $dependency_registry = $this->getDependencies();
        foreach($dependency_list AS $type=>$dependency){
            $class_name = $dependency['class'];
            $factory_class = $dependency['factory'];
            $Factory = new $factory_class($this->database, $dependency_registry, $class_name);
            $Factory->initGameScoresDependency($Scores,$type);
        }
    }

    public function saveGameScore(GameScore $Score, array $DATA):bool{
        $id = $Score->getID();
        $insert = $this->saveItem($DATA, $id);
        $Score->initialize($insert);
        return true;
    }

    public function deleteGameScore(GameScore $Score):bool{
        return $this->deleteItem($Score->getID());
    }

    public function deleteScoresForGame(int $game_id):bool{
        $success = false;
        if($game_id){
            $Scores = $this->getScoresForGame($game_id);
            foreach($Scores AS $Score){
                $success = $this->deleteGameScore($Score);
                if(!$success){
                    break;
                }
            }
        }else{
            $this->addErrorMsg('Invalid Game ID');
        }
        return $success;
    }

    public function getDependencies():array{
        return $this->dependencies;
    }

    public function getDependencyValue(string $key){
        $value = isset($this->dependencies[$key])?$this->dependencies[$key]:null;
        return $value;
    }


}