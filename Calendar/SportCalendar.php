<?php
namespace ElevenFingersCore\GAPPS\Calendar;

use ElevenFingersCore\Calendar\Calendar;
use ElevenFingersCore\GAPPS\Sports\Games\GameFactory;
use ElevenFingersCore\GAPPS\Sports\Venues\VenueFactory;
use ElevenFingersCore\Utilities\SortableObject;
class SportCalendar extends Calendar{
    
    private $season_id = 0;

    private $VenueFactory;

    private $GameFactory;

    private $Games = [];

    private $school_id = null;
    static $db_table = 'games';


    public function setSeasonID(int $season_id){
        $this->season_id = $season_id;
    }

    public function setSchoolID(int $school_id){
        $this->school_id = $school_id;
    }

    public function setGameFactory(GameFactory $Factory){
        $this->GameFactory = $Factory;
    }

    private function getGameFactory():GameFactory{
        return $this->GameFactory;
    }

    public function setVenueFactory(VenueFactory $Factory){
        $this->VenueFactory = $Factory;
    }

    private function getVenueFactory():VenueFactory{
        return $this->VenueFactory;
    }

    public function getEvents(bool $refresh = false):array{
        if(empty($this->Events) || $refresh){
           
            $Games = $this->getGames();
            
            $Events = [];
            foreach($Games AS $Game){
                $data = [
                    'id'=>$Game->getID(),
                    'Game'=>$Game,
                ];
                $Events[] = $this->makeCalendarEvent($data);
            }
            
            $SortableObject = new SortableObject('getUnixTime');
            $SortableObject->sort($Events);
            $this->Events = array_values($Events);
        }
        return $this->Events;
    }

    protected function getVenues(array $venue_ids):array{
        $Factory = $this->getVenueFactory();
        $Venues = $Factory->getVenues(['id'=>['IN'=>$venue_ids]]);
        $VenuesByID = [];
        foreach($Venues AS $Venue){
            $VenuesByID[$Venue->getID()] = $Venue;
        }
        return $VenuesByID;
    }

    private function getGames():array{
        $Factory = $this->getGameFactory();
        $filter = [];
        if(!empty($this->school_id)){
            $season_id = $Factory->getSeasonID();
            $game_ids = $this->database->getResultListByKey('games_teams',['season_id'=>$season_id,'school_id'=>$this->school_id],'game_id');
            if(!empty($game_ids)){
                $filter['id'] = ['IN'=>$game_ids];
            }else{
                $filter['id'] = 0;
            }
        }
        $Games = $Factory->getGames($filter);
        return $Games;
    }

    protected function parseFilter():array{
        $re = [
            'query_str'=>' season_id = :season_id',
            'args'=>[':season_id'=>$this->season_id],
        ];
        if(!empty($this->school_id)){
            $game_ids = $this->database->getResultListByKey('games_teams',['season_id'=>$this->season_id,'school_id'=>$this->school_id],'game_id');
            if(!empty($game_ids)){
                $re['query_str'] .= ' AND  id IN ('.implode(',',$game_ids).') ';
            }else{
                $re['query_str'] .= ' AND id = 0';
            }
        }

        return $re;
    }


    public function makeCalendarEvent(array $data):SportCalendarEvent{
        return new SportCalendarEvent($this->database,0,$data);
    }

}