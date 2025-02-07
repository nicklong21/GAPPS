<?php
namespace ElevenFingersCore\GAPPS\Academics;
use ElevenFingersCore\GAPPS\Sports\SportFactory;
use ElevenFingersCore\GAPPS\Academics\Rosters\RosterFactory;
use ElevenFingersCore\GAPPS\Academics\Seasons\SeasonFactory;

class AcademicEventFactory extends SportFactory{

    public function getEventByName(string $name):?AcademicEvent{
        return $this->getSportByName($name);
    }

    public function getEventBySlug(string $slug):?AcademicEvent{
        return $this->getSportBySlug($slug);
    }

    public function getEventBySeasonID(int $season_id):?AcademicEvent{
        return $this->getSportBySeasonID($season_id);
    }

    public function getEvent(?int $id = null, ?array $DATA = null){
        if(!empty($id)){
            $DATA = $this->database->getArrayByKey(static::$db_table,array('id'=>$id));
        }
        if(!empty($DATA)){
            $event_data = [];
            foreach(static::$schema AS $key=>$val){
                $event_data[$key] = $DATA[$key]?? $val;
            }
            $event_slug = $event_data['slug'];
            $dependencies = $this->Registry::getDependencies($event_slug);
            $EventClass = $dependencies['event'];
            $Sport = new $EventClass($event_data); 
            $RosterFactory = new RosterFactory($this->database, $dependencies);
            $Sport->setRosterFactory($RosterFactory);
            $SeasonFactory = $this->getSeasonFactory($event_slug);
            $Sport->setSeasonFactory($SeasonFactory);
        }else{
            throw new \RuntimeException('Invalid Event ID: '.$id);
        } 
        return $Sport;
    }

    public function getSport(?int $id = 0, ?array $DATA = array()):AcademicEvent{
        return $this->getEvent($id,$DATA);
    }

    public function getEventsByIDs(array $ids):array{
        return $this->getSportsByIDs($ids);
    }

    public function getEvents(null|string|array $group = null, null|string|array $age_group = null, null|string|array $semester = null, ?string $status = 'active'):array{
        if(empty($group)){
            $group = ['Academics','Fine Arts'];
        }
        
        return $this->getSports($group,$age_group,$semester, $status);
    }
}