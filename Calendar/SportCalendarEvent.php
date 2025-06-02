<?php
namespace ElevenFingersCore\GAPPS\Calendar;

use ElevenFingersCore\Calendar\CalendarEvent;
use ElevenFingersCore\GAPPS\Sports\Venues\Venue;
use ElevenFingersCore\GAPPS\Sports\Games\Game;
class SportCalendarEvent extends CalendarEvent{
    
    private $Game;

    protected function initialize(?int $id = 0, ?array $DATA = []){
        if($id){
            $DATA = $this->database->getArrayByKey('games',['id'=>$id]);
        }

        $eventDATA = static::$template;
        if(!empty($DATA)){
            $eventDATA['id'] = $DATA['id'];
            /** @var Game $Game */
            $Game = $DATA['Game'];
            if(!empty($Game)){
                $this->Game = $Game;         
            $eventDATA['title'] = $Game->getTitle();
            $eventDATA['datetime_start'] = $Game->getStartDate('Y-m-d H:i:s');
            $eventDATA['datetime_end'] = $eventDATA['datetime_start'];
            $eventDATA['status'] = $Game->getStatus();
            }
        }
        $this->DATA = $eventDATA;
        
    }


    public function getSummary():string{
        $html = '<div class="gametime">'.$this->getDateStart('F d, Y g:m A').'</div>';
        
        return $html;
    }

    public function getDetails():string{
        $html = $this->Game->getGameView()->getDetailsTable();
        return $html;
    }
}