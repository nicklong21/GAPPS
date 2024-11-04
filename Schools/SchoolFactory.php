<?php
namespace ElevenFingersCore\GAPPS\Schools;
use ElevenFingersCore\Database\DatabaseConnectorPDO;
use ElevenFingersCore\GAPPS\FactoryTrait;
use ElevenFingersCore\Utilities\MessageTrait;
class SchoolFactory{
    use MessageTrait;
    use FactoryTrait;
    protected $database;

    protected $db_table = 'schools';

    protected $schema = [
        'id'=>0,
        'title'=>'',
        'type'=>'',
        'address1'=>'',
        'address2'=>'',
        'city'=>'',
        'state'=>'',
        'zip'=>'',
        'phone'=>'',
        'website'=>'',
        'school_admin_account'=>0,
        'status'=>'',
        'qb_id'=>'',
    ];

    function __construct(DatabaseConnectorPDO $DB){
        $this->database = $DB;
        $this->setItemClass(School::class);
    }

    public function getSchool(?int $id = null, ?array $DATA = array()):School{
        $School = $this->getItem($id, $DATA);
        $StudentFactory = new StudentFactory($this->database);
        $School->setStudentFactory($StudentFactory);
        return $School;
    }

    /**
     * Summary of getSchoolsByIds
     * @param array $ids
     * @return School[]
     */
    public function getSchoolsByIds(array $ids){
        $filter = array('id'=>array('IN'=>$ids));
        $schools_data = $this->database->getArrayListByKey($this->db_table, $filter,'title');
        $Schools = array();
        foreach($schools_data AS $DATA){
            $Schools[] = $this->getSchool(null,$DATA);
        }
        return $Schools;
    }

    public function getSchools(?string $status = null){
        $filter = [];
        if(!empty($status)){
            $filter['status'] = $status;
        }
        $schools_data = $this->database->getArrayListByKey($this->db_table, $filter,'title');
        $Schools = array();
        foreach($schools_data AS $DATA){
            $Schools[] = $this->getSchool(null,$DATA);
        }
        return $Schools;
    }


    public function saveSchool(School $School, Array $DATA):bool{
        $insert = $this->saveItem($DATA, $School->getID());
        if(!empty($insert)){
            $School->initialize($insert);
        }
        return true;
    }

}