<?php
namespace ElevenFingersCore\GAPPS\Sports\Rosters;
use ElevenFingersCore\Database\DatabaseConnectorPDO;
use ElevenFingersCore\GAPPS\FactoryTrait;
use ElevenFingersCore\Utilities\MessageTrait;

class RosterFactory{
    use MessageTrait;
    use FactoryTrait;
    protected $dependencies;
    protected $db_table = 'rosters';
    protected $schema = [
        'id'=>0,
        'season_id'=>0,
        'school_id'=>0,
        'title'=>'',
        'status'=>NULL,
    ];

    function __construct(DatabaseConnectorPDO $DB, array $dependencies){
        $this->database = $DB;
        $this->dependencies = $dependencies;
        $item_class = $dependencies['roster'];
        $this->setItemClass($item_class);
    }

    public function getRoster(?int $id = 0, ?array $DATA = []):Roster{
        $roster_table_class = $this->dependencies['roster_table'];
        /** Roster $Roster */
        $Roster = $this->getItem($id,$DATA);
        $RosterTable = new $roster_table_class();
        if(!empty($this->dependencies['roster_varsity_values'])){
            $RosterTable->setVarsityValues($this->dependencies['roster_varsity_values']);
        }
        if(!empty($this->dependencies['roster_varsity_label'])){
            $RosterTable->setVarsityLabel($this->dependencies['roster_varsity_label']);
        }
        $Roster->setRosterTable($RosterTable);
        $roster_student_factory_class = $this->dependencies['roster_student_factory'];
        $RosterStudentFactory = new $roster_student_factory_class($this->database,$this->dependencies);
        $Roster->setRosterStudentFactory($RosterStudentFactory);
        return $Roster;
    }

    public function getSchoolSeasonRoster(int $season_id, int $school_id){
        $filter = array('season_id'=>$season_id, 'school_id'=>$school_id);
        $DATA = $this->database->getArrayByKey($this->db_table,$filter);
        return $this->getRoster(null, $DATA);
    }

    /**
     * Summary of getRosters
     * @param array $filter
     * @return Roster[]
     */
    public function getRosters(Array $filter):array{
        $Rosters = [];
        $data = $this->database->getArrayListByKey($this->db_table,$filter);
        foreach($data AS $DATA){
            $Rosters[] = $this->getRoster(null, $DATA);
        }
        return $Rosters;
    }

    /**
     * Summary of getSeasonRosters
     * @param int $season_id
     * @return Roster[]
     */
    public function getSeasonRosters(int $season_id):array{
        return $this->getRosters(['season_id'=>$season_id]);
    }


    public function findRoster($filter):?Roster{
        $Roster = null;
        $data = $this->database->getArrayByKey($this->db_table, $filter);
        if($data){
            $Roster = $this->getRoster(null, $data);
        }
        return $Roster;
    }

    public function saveRoster(Roster $Roster, array $DATA):bool{
        $roster_id = $Roster->getID();
        $DATA['season_id'] = $Roster->getSeasonID();
        $DATA['school_id'] = $Roster->getSchoolID();
        $insert = $this->saveItem($DATA, $roster_id);
        $Roster->initialize($DATA);
        return true;
    }

    public function deleteRoster(Roster $Roster):bool{
        $roster_id = $Roster->getID();
        return $this->deleteItem($roster_id);
    }
}