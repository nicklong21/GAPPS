<?php
namespace ElevenFingersCore\GAPPS\Sports\Seasons\Regions;
use ElevenFingersCore\GAPPS\FactoryTrait;
use ElevenFingersCore\Database\DatabaseConnectorPDO;
use ElevenFingersCore\Utilities\MessageTrait;

class RegionFactory{
    use FactoryTrait;
    use MessageTrait;
    protected $database; 
    protected $dependencies;

    protected $db_table = 'sports_regions';

    protected $schema = [
        'id'=>0,
        'title'=> '',
        'season_id'=>0,
    ];

    function __construct(DatabaseConnectorPDO $DB, array $dependencies){
        $this->database = $DB;
        $this->dependencies = $dependencies;
        $this->setItemClass(Region::class);
    }

    public function getRegion(?int $id = 0, ?array $DATA = array()):Region{
        $Region = $this->getItem($id,$DATA);
        return $Region;
    }
    
    /**
     * Summary of getSeasonRegions
     * @param int $season_id
     * @return Region[]
     */
    public function getSeasonRegions(int $season_id):array{
        $regions_data = $this->database->getArrayListByKey($this->db_table, ['season_id'=>$season_id]);
        $Regions = [];
        foreach($regions_data AS $DATA){
            $Region = $this->getRegion(null, $DATA);
            $Regions[$Region->getID()] = $Region;
        }
        return $Regions;
    }

    public function updateSeasonRegions(int $season_id, array $data):bool{
        $Regions = $this->getSeasonRegions($season_id);
        $region_data = array();
        $new_data = array();
        $any_errors = false;
        foreach($data AS $d){
            if($d['id']){
                $region_data[$d['id']] = $d;
            }else{
                $new_data[] = $d;
            }
        }
        foreach($Regions AS $Region){
            $region_id = $Region->getID();
            if(isset($region_data[$region_id])){
                $insert = array('title'=>$region_data[$region_id]['title']);
                $success = $this->saveRegion($Region, $insert);
                if(!$success){
                    $any_errors = true;
                }
            }else{
                $success = $this->deleteRegion($Region);
                if(!$success){
                    $any_errors = true;
                }
            }
        }
        foreach($new_data AS $n){
            $Region = $this->getRegion(0);
            $insert = $n;
            $insert['season_id'] = $season_id;
            $success = $this->saveRegion($Region,$insert);
            if(!$success){
                $any_errors = true;
            }
        }
        return !$any_errors;
    }

    public function saveRegion(Region $Region, array $DATA):bool{
        $insert = $this->saveItem($DATA, $Region->getID());
        $Region->initialize($insert);
        return true;
    }

    public function copyRegion(Region $Region, array $DATA):Region{
        return $this->copyItem($Region, $DATA);
    }

    public function deleteRegion(Region $Region):bool{
        $id = $Region->getID();
        return $this->deleteItem($id);
    }

    
}