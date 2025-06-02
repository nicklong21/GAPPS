<?php
namespace ElevenFingersCore\GAPPS\Sports\Venues;
use ElevenFingersCore\Database\DatabaseConnectorPDO;
use ElevenFingersCore\GAPPS\FactoryTrait;
use ElevenFingersCore\GAPPS\Sports\SportRegistry;
use ElevenFingersCore\Utilities\MessageTrait;
use ElevenFingersCore\GAPPS\ChangeLog;
class VenueFactory{
    use MessageTrait;
    use FactoryTrait;
    protected $database;
    protected $Registry;
    protected $db_table = 'venues';
    protected $db_xref = 'venues_sports';
    protected $ChangeLogger;
    protected $init_event_ids = true;
    protected $schema = [
        'id'=>0,
        'school_id'=>0,
        'title'=>'',
        'address1'=>'',
        'address2'=>'',
        'city'=>'',
        'state'=>'',
        'zip'=>'',
        'instructions'=>'',
        'googlemap'=>'',
        'publish'=>0,
        'is_active'=>0,
    ];

    function __construct(DatabaseConnectorPDO $DB, SportRegistry $Registry){
        $this->database = $DB;
        $this->Registry = $Registry;
        $this->setItemClass(Venue::class);
    }

    public function setChangeLogger(ChangeLog $Logger){
        $this->ChangeLogger = $Logger;
    }

    protected function addChangeLogRecord(string $type, int $record_id, string $value){
        if(!empty($this->ChangeLogger)){
            $this->ChangeLogger->addLog('Venues',$type,$record_id,$value);
        }
    }

    public function getVenue(?int $id = null, ?array $DATA = array()):Venue{
        /**
         * @var Venue $Venue
         */
        $Venue = $this->getItem($id, $DATA);
        if($this->init_event_ids){
            $venue_id = $Venue->getID();
            $all_event_ids = $this->database->getResultListByKey($this->db_xref, ['venue_id'=>$venue_id],'sport_id');
            $Venue->setEventIDs($all_event_ids);
        }
        return $Venue;
    }

    /**
     * Summary of getVenues
     * @param mixed $filter
     * @return Venue[]
     */
    public function getVenues(?array $filter = array()):array{
        $Venues = [];
        $venue_data = $this->database->getArrayListByKey($this->db_table,$filter,'title');
        $venue_ids = [];
        foreach($venue_data AS $DATA){
            $Venue = $this->getVenue(null, $DATA);
            $venue_ids[] = $Venue->getID();
            $Venues[] = $Venue;
        }
        $events_by_venue_id = $this->getEventsByVenueIDs($venue_ids);
        foreach($Venues AS $Venue){
            $event_ids = $events_by_venue_id[$Venue->getID()]??[];
            $Venue->setEventIDs($event_ids);
        }
        return $Venues;
    }

    /**
     * Summary of getVenuesByEventID
     * @param int $event_id
     * @return Venue[]
     */
    public function getVenuesByEventID(int $event_id):array{
        $Venues = [];
        $venue_ids = $this->database->getResultListByKey($this->db_xref,['sport_id'=>$event_id],'venue_id');
        if(!empty($venue_ids)){
            $this->init_event_ids = false;
            $venue_data = $this->database->getArrayListByKey($this->db_table, ['id'=>['IN'=>$venue_ids]],'title');
            $events_by_venue_id = $this->getEventsByVenueIDs($venue_ids);
            foreach($venue_data AS $DATA){
                $Venue = $this->getVenue(null, $DATA);
                $event_ids = $events_by_venue_id[$Venue->getID()]??[];
                $Venue->setEventIDs($event_ids);
                $Venues[] = $Venue;
            }
            $this->init_event_ids = true;
        }
        return $Venues;
    }
    /**
     * Summary of getVenuesBySchoolID
     * @param int $school_id
     * @return Venue[]
     */
    public function getVenuesBySchoolID(int $school_id):array{
        $Venues = [];
        $venue_data = $this->database->getArrayListByKey($this->db_table,['school_id'=>$school_id],'title');
        $venue_ids = [];
        foreach($venue_data AS $DATA){
            $Venue = $this->getVenue(null, $DATA);
            $venue_ids[] = $Venue->getID();
            $Venues[] = $Venue;
        }
        $events_by_venue_id = $this->getEventsByVenueIDs($venue_ids);
        foreach($Venues AS $Venue){
            $event_ids = $events_by_venue_id[$Venue->getID()]??[];
            $Venue->setEventIDs($event_ids);
        }
        return $Venues;
    }

    public function getEventsByVenueIDs(array $venue_ids):array{
        $venue_event_data = $this->database->getArrayListByKey($this->db_xref,['venue_id'=>['IN'=>$venue_ids]]);
        $events_by_venue_id = [];
        foreach($venue_event_data AS $event_data){
            $venue_id = $event_data['venue_id'];
            if(empty($events_by_venue_id[$venue_id])){
                $events_by_venue_id[$venue_id] = [];
            }
            $events_by_venue_id[$venue_id][] = $event_data['sport_id'];
        }
        return $events_by_venue_id;
    }

    public function saveVenue(Venue $Venue, array $DATA):bool{
        $venue_id = $Venue->getID();
        $insert = $this->saveItem($DATA, $venue_id);
        $Venue->initialize($insert);
        if(array_key_exists('activity', $DATA)){
            $event_ids = $DATA['activity'];
            $this->updateVenueEvents($Venue,$event_ids);
        }
        $change_type = $venue_id?'ALTER':'INSERT';
        $change_value = $Venue->getTitle().' Record Changed';
        $this->addChangeLogRecord($change_type,$Venue->getID(),$change_value);
        return true;
    }

    public function updateVenueEvents(Venue $Venue, array $event_ids){
        $venue_id = $Venue->getID();
        $this->database->deleteByKey($this->db_xref,['venue_id'=>$venue_id, 'sport_id'=>['NOT IN'=>$event_ids]]);
        $existing_events = $this->database->getResultListByKey($this->db_xref, ['venue_id'=>$venue_id],'sport_id');
        $new_events = array_diff($event_ids,$existing_events);
        if(!empty($new_events)){
            foreach($new_events AS $event_id){
                $insert = ['venue_id'=>$venue_id,'sport_id'=>$event_id];
                $this->database->insertArray($this->db_xref,$insert, 'id');
            }
        }
        $Venue->setEventIDs($event_ids);
    }

    public function deleteVenue(Venue $Venue):bool{
        $venue_id = $Venue->getID();
        $change_value = $Venue->getTitle().' Record Deleted';
        $this->addChangeLogRecord('DELETE',$Venue->getID(),$change_value);
        return $this->deleteItem($venue_id);
    }
}