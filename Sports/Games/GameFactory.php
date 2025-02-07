<?php
namespace ElevenFingersCore\GAPPS\Sports\Games;
use ElevenFingersCore\Database\DatabaseConnectorPDO;
use ElevenFingersCore\GAPPS\FactoryTrait;
use ElevenFingersCore\Utilities\MessageTrait;
use ElevenFingersCore\GAPPS\Sports\Games\Teams\TeamFactory;
use ElevenFingersCore\GAPPS\Sports\Games\Scores\GameScoreFactory;

class GameFactory{
    use FactoryTrait;
    use MessageTrait;
    protected $dependencies;

    protected $TeamFactory;

    protected $ScoreFactory;

    protected $season_id; 

    protected $Games = [];

    protected $db_table = 'games';
    protected $schema = [
        'id'=>0,
        'title'=>'',
        'type'=>null,
        'date'=>'0000-00-00',
        'start_time'=>'00:00:00',
        'season_id'=>0,
        'region_id'=>0,
        'division_id'=>0,
        'venue_id'=>0,
        'status'=>'',
        'note'=>'',
    ];

    function __construct(DatabaseConnectorPDO $DB, array $dependencies){
        $this->database = $DB;
        $this->dependencies = $dependencies;
        $this->setItemClass($dependencies['game']);
    }

    function setSeasonID(int $id){
        $this->season_id = $id;
    }

    function getSeasonID():int{
        return $this->season_id??0;
    }

    public function getGame(?int $id = 0, ?array $DATA = []):Game{
        $TeamFactory = $this->getTeamFactory();
        $ScoreFactory = $this->getScoreFactory();
        /** Game $Game */
        $Game = $this->getItem($id,$DATA);
        $Game->setTeamFactory($TeamFactory);
        $Game->setScoreFactory($ScoreFactory);
        $Game->setSeasonID($this->getSeasonID());
        $game_view_class = $this->dependencies['game_view'];
        $Game->setGameView(new $game_view_class);
        return $Game;
    }

    /**
     * Summary of getGames
     * @param array $filter
     * @return Game[]
     */
    public function getGames(?Array $filter = []):array{
        $Games = [];
        if(empty($this->Games)){
            $this->Games = [];
            $season_id = $this->getSeasonID();
            $data = $this->database->getArrayListByKey($this->db_table,['season_id'=>$season_id]);
            foreach($data AS $DATA){
                $this->Games[] = $this->getGame(null, $DATA);
            }
            $this->getGamesTeams($this->Games);
        }
        if(!empty($filter)){
            $FilteredGames = array();
            $filtered_ids = $this->database->getResultListByKey($this->db_table,$filter,'id');
            foreach($this->Games AS $Game){
                if(in_array($Game->getID(), $filtered_ids)){
                    $FilteredGames[] = $Game;
                }
            }
            $Games = $FilteredGames;
        }else{
            $Games = $this->Games;
        }
        return $Games;
    }

    /**
     * Summary of getGamesTeams
     * @param Game[]
     * @return Game[]
     */
    protected function getGamesTeams(array $Games):Array{
        $game_ids = array();
        foreach($Games AS $Game){
            $game_ids[] = $Game->getID();
        }
        $TeamFactory = $this->getTeamFactory();
        $TeamsByGameID = $TeamFactory->getTeamsByGameID($game_ids);
        foreach($Games AS $Game){
            $game_id = $Game->getID();
            $Teams = isset($TeamsByGameID[$game_id])?$TeamsByGameID[$game_id]:array();
            $Game->setTeams($Teams);
        }
        return $Games;
    }

    public function getSchoolGames(int $school_id, array $filter = array()){
        $SchoolGames = [];
        $TeamFactory = $this->getTeamFactory();
        $game_ids = $TeamFactory->getGameIDsBySchool($school_id,$this->getSeasonID());
        if(!empty($game_ids)){
            $SeasonGames = $this->getGames($filter);
            foreach($SeasonGames AS $Game){
                if(in_array($Game->getID(),$game_ids)){
                    $SchoolGames[] = $Game;
                }
            }
        }
        return $SchoolGames;
    }

    public function findGame($filter):?Game{
        $Game = null;
        $DATA = $this->database->getArrayByKey($this->db_table, $filter);
        if($DATA){
            $Game = $this->getGame(null, $DATA);
        }
        return $Game;
    }

    public function getTeamFactory():TeamFactory{
        if(empty($this->TeamFactory)){
            $this->TeamFactory = new TeamFactory($this->database, $this->dependencies);
        }
        return $this->TeamFactory;
    }
    public function setTeamFactory(TeamFactory $Factory){
        $this->TeamFactory = $Factory;
    }

    public function getScoreFactory():GameScoreFactory{
        if(empty($this->ScoreFactory)){
            $this->ScoreFactory = new GameScoreFactory($this->database, $this->dependencies);
        }
        return $this->ScoreFactory;
    }
    public function setScoreFactory(GameScoreFactory $Factory){
        $this->ScoreFactory = $Factory;
    }

    public function saveGame(Game $Game, array $DATA):bool{
        $StartDate = new \DateTimeImmutable($DATA['game_date']);
        $game_data = [
            'type'=>$DATA['type'],
            'date'=>$StartDate->format('Y-m-d'),
            'start_time'=>$StartDate->format('H:i:s'),
            'season_id'=>$Game->getSeasonID(),
            'status'=>$DATA['game_status'],
        ];
        $insert = $this->saveItem($game_data, $Game->getID());
        $Game->initialize($insert);
        $TeamFactory = $this->getTeamFactory();
        $TeamFactory->saveGameTeams($Game, $DATA);
        $Teams = $Game->getTeams();
        $game_title = '';
        $HomeTeam = $Game->getHomeTeam();
        foreach($Teams AS $Team){
            if(!$Team->isHomeTeam()){
                $game_title = $Team->getSchoolName();
                break;
            }
        }
        if(!empty($HomeTeam)){
            $game_title .= ' @ '.$HomeTeam->getSchoolName();
        }
        return true;
    }

    public function deleteGame(Game $Game):bool{
        $id = $Game->getID();
        return $this->deleteItem($id);
    }
}