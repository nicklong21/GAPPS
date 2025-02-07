<?php
namespace ElevenFingersCore\GAPPS\Sports\Games\Teams;
use ElevenFingersCore\Database\DatabaseConnectorPDO;
use ElevenFingersCore\GAPPS\FactoryTrait;
use ElevenFingersCore\GAPPS\Sports\Rosters\RosterFactory;
use ElevenFingersCore\Utilities\MessageTrait;
use ElevenFingersCore\GAPPS\Sports\Games\Game;


class TeamFactory{
    use FactoryTrait;
    use MessageTrait;

    protected $dependencies;
    protected $db_table = 'games_teams';
    protected $schema = [
        'id'=>0,
        'season_id'=>0,
        'game_id'=>0,
        'roster_id'=>0,
        'school_id'=>0,
        'school_name'=>NULL,
        'type'=>'',
    ];

    function __construct(DatabaseConnectorPDO $DB, array $dependencies){
        $this->database = $DB;
        $this->dependencies = $dependencies;
        $item_class = $dependencies['team'];
        $this->setItemClass($item_class);
    }

    public function getTeam(?int $id = 0, ?array $DATA = []):Team{
        $Team = $this->getItem($id, $DATA);
        $RosterFactory = $this->getRosterFactory();
        $Team->setRosterFactory($RosterFactory);
        return $Team;
    }

    public function getGameIDsBySchool(int $school_id, int $season_id):array{
        $filter = ['school_id'=>$school_id,'season_id'=>$season_id];
        $game_ids = $this->database->getResultListByKey($this->db_table, $filter,'game_id');
        return $game_ids;
    }

    public function getTeamsByGameID(int|Array $ids):array{
        $TeamsByGameID = [];
        if(is_int($ids)){
            $filter = array('game_id'=>$ids);
        }else{
            $filter = array('game_id'=>['IN'=>$ids]);
        }
        $data = $this->database->getArrayListByKey($this->db_table,$filter);
        foreach($data AS $DATA){
            $Team = $this->getTeam(null, $DATA);
            if(is_int($ids)){
                $TeamsByGameID[$Team->getID()] = $Team;
            }else{
                $game_id = $Team->getGameID();
                if(empty($TeamsByGameID[$game_id])){
                    $TeamsByGameID[$game_id] = array();
                }
                $TeamsByGameID[$game_id][$Team->getID()] = $Team;
            }
        }
        return $TeamsByGameID;
    }

    protected function getRosterFactory(){
        return new RosterFactory($this->getDatabaseConnector(), $this->dependencies);
    }

    public function saveGameTeams(Game $Game, array $DATA):bool{
        $season_id = $Game->getSeasonID();
        $game_id = $Game->getID();
        $hometeams_jsonstr = $data['hometeams']??array();
        $awayteams_jsonstr = $data['awayteams']??array();
        $allteams = array();
        foreach($hometeams_jsonstr AS $str){
            $team_data = json_decode($str,true);
            $team_data['type'] = 'hometeam';
            $school_id = $team_data['school_id']??$team_data['school_name'];
            $allteams[$school_id] = $team_data;
        }
        foreach($awayteams_jsonstr AS $str){
            $team_data = json_decode($str,true);
            $team_data['type'] = 'awayteam';
            $school_id = $team_data['school_id']??$team_data['school_name'];
            $allteams[$school_id] = $team_data;
        }
        $Teams = $this->getTeamsByGameID($game_id);
        foreach($Teams AS $i=>$Team){
            $school_id = $Team->getSchoolID();
            if(empty($school_id)){
                $school_id = $Team->getSchoolName();
            }
            if(!isset($allteams[$school_id])){
                $this->deleteTeam($Team);
                unset($Teams[$i]);
            }else{
                $this->saveTeam($Team, $allteams[$school_id]);
                unset($allteams[$school_id]);
            }   
        }
        if(!empty($allteams)){
            foreach($allteams AS $insert){
                $newTeam = $this->getTeam(0);
                $insert['game_id'] = $game_id;
                $insert['season_id'] = $season_id;
                $this->saveTeam($newTeam,$insert);
                $Teams[] = $newTeam;
            }
        }
        $Game->setTeams($Teams);
        return true;
    }

    public function saveTeam($Team, $DATA):bool{
        $insert = $this->saveItem($DATA, $Team->getID());
        $Team->initialize($insert);
        return true;
    }

    public function deleteTeam($Team):bool{
        return $this->deleteItem($Team->getID());
    }
}
