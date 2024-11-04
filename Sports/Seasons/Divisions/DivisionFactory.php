<?php
namespace ElevenFingersCore\GAPPS\Sports\Seasons\Divisions;
use ElevenFingersCore\GAPPS\FactoryTrait;
use ElevenFingersCore\Database\DatabaseConnectorPDO;
use ElevenFingersCore\Utilities\MessageTrait;
class DivisionFactory{
    use FactoryTrait;
    use MessageTrait;
    protected $database; 
    protected $dependencies;
    protected $db_table = 'sports_divisions';
    protected $schema = [
        'id'=>0,
        'title'=> '',
        'season_id'=>0,
        'roster_limit'=>12,
        'aes_percentage'=>10,
        'aes_max'=>0
    ];
    function __construct(DatabaseConnectorPDO $DB, array $dependencies){
        $this->database = $DB;
        $this->dependencies = $dependencies;
        $this->setItemClass(Division::class);
    }

    public function getDivision(?int $id = 0, ?array $DATA = array()):Division{
        return $this->getItem($id,$DATA);
    }
    /**
     * Summary of getSeasonDivisisions
     * @param int $season_id
     * @return Division[]
     */
    public function getSeasonDivisions(int $season_id):array{
        $division_data = $this->getDatabaseConnector()->getArrayListByKey($this->db_table,['season_id'=>$season_id]);
        $Divisions = [];
        foreach($division_data AS $DATA){
            $Division = $this->getDivision(null, $DATA);
            $Divisions[$Division->getID()] = $Division; 
        }
        return $Divisions;
    }

    public function updateSeasonDivisions(int $season_id, array $data):bool{
        
        $Divisions = $this->getSeasonDivisions($season_id);
        $division_data = array();
        $new_data = array();
        $any_errors = false;
        foreach($data AS $d){
            if($d['id']){
                $division_data[$d['id']] = $d;
            }else{
                $new_data[] = $d;
            }
        }
        foreach($Divisions AS $Division){
            $division_id = $Division->getID();
            if(isset($division_data[$division_id])){
                $insert = $division_data[$division_id];
                $success = $this->saveDivision($Division,$insert);
                if(!$success){
                    $any_errors = true;
                }
            }else{
                $success = $this->deleteDivision($Division);
                if(!$success){
                    $any_errors = true;
                }
            }
        }
        foreach($new_data AS $d){
            $Division = $this->getDivision(0);
            $insert = $d;
            $insert['season_id'] = $season_id;
            $success = $this->saveDivision($Division,$insert);
                if(!$success){
                    $any_errors = true;
                }
        }
        return !$any_errors;
    }

    public function saveDivision(Division $Division, array $DATA):bool{
        $insert = $this->saveItem($DATA, $Division->getID());
        $Division->initialize($insert);
        return true;
    }

    public function copyDivision(Division $Division, array $DATA):Division{
        return $this->copyItem($Division, $DATA);
    }

    public function deleteDivision(Division $Division):bool{
        $id = $Division->getID();
        return $this->deleteItem($id);
    }
}