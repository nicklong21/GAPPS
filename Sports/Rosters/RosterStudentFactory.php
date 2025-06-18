<?php
namespace ElevenFingersCore\GAPPS\Sports\Rosters;
use ElevenFingersCore\GAPPS\ChangeLog;
use ElevenFingersCore\Database\DatabaseConnectorPDO;
use ElevenFingersCore\GAPPS\FactoryTrait;
use ElevenFingersCore\GAPPS\Schools\School;
use ElevenFingersCore\GAPPS\Schools\StudentFactory;
use ElevenFingersCore\Utilities\MessageTrait;

class RosterStudentFactory{
    use FactoryTrait;
    use MessageTrait;
    protected $dependencies;

    private $init_single_dependency = true;

    protected $ChangeLogger;
    protected $db_table = 'rosters_students';
    protected $schema = [
        'id' => 0,
        'roster_id' => 0,
        'student_id' => 0,
        'jersey_number' => NULL,
        'status' => 'ELIGIBLE',
        'attr1' => NULL,
        'attr2' => NULL,
        'lastname' => '',
        'firstname' => '',
        'age' => 0,
        'gender' => '',
        'grade' => 0,
        'is_aes' => 'No',
        'new_student' => 0,
        'is_jv' => 0,
        'date_added' => NULL,
    ];

    function __construct(DatabaseConnectorPDO $DB, array $dependencies){
        $this->database = $DB;
        $this->dependencies = $dependencies;
        $item_class = $this->getDependencyValue('roster_student');
        $this->setItemClass($item_class);
    }

    public function setChangeLogger(ChangeLog $Logger){
        $this->ChangeLogger = $Logger;
    }

    protected function addChangeLogRecord(string $type, int $record_id, string $value){
        if(!empty($this->ChangeLogger)){
            $prepend_value = $this->ChangeLogger->getLogValue();
            $value = $prepend_value.': '.$value;
            $this->ChangeLogger->addLog('Roster Student Record',$type,$record_id,$value);
        }
    }

    public function getRosterStudent(?int $id = 0, ?array $DATA = array()):RosterStudent{
        $RosterStudent = $this->getItem($id, $DATA);
        if($this->init_single_dependency){
            $this->initRosterStudentDependencies([$RosterStudent]);
        }
        return $RosterStudent;
    }

    public function getRosterStudents(int $roster_id):array{
        $RosterStudents = [];
        $this->init_single_dependency = false;
        if(!empty($roster_id)){
            $filter = array('roster_id'=>$roster_id);
            $roster_student_data = $this->database->getArrayListByKey($this->db_table, $filter,['lastname','firstname']);
            foreach($roster_student_data AS $DATA){
                $RosterStudents[] = $this->getRosterStudent(null, $DATA);
            }
        }
        $this->initRosterStudentDependencies($RosterStudents);
        $this->init_single_dependency = true;
        return $RosterStudents;
    }

    public function getRosterStudentsByIDs(array $ids):array{
        $filter = array('id'=>array('IN'=>$ids));
        $this->init_single_dependency = false;
        $roster_student_data = $this->database->getArrayListByKey($this->db_table, $filter);
        $RosterStudents = [];
        foreach($roster_student_data AS $DATA){
            $RosterStudents[] = $this->getRosterStudent(null, $DATA);
        }
        $this->initRosterStudentDependencies($RosterStudents);
        $this->init_single_dependency = true;
        return $RosterStudents;
    }

    public function getRosterStudentsByStudentIDs(int $roster_id, array $student_ids):array{
        $filter = array('roster_id'=>$roster_id, 'student_id'=>['IN'=>$student_ids]);
        $this->init_single_dependency = false;
        $roster_student_data = $this->database->getArrayListByKey($this->db_table, $filter);
        $RosterStudents = [];
        foreach($roster_student_data AS $DATA){
            $RosterStudents[] = $this->getRosterStudent(null, $DATA);
        }
        $this->initRosterStudentDependencies($RosterStudents);
        $this->init_single_dependency = true;
        return $RosterStudents;
    }

    public function initRosterStudentDependencies(array $RosterStudents){
        $dependency_list = $this->getDependencyValue('roster_student_dependencies');
        $dependency_registry = $this->getDependencies();
        if(!empty($dependency_list)){
        foreach($dependency_list AS $type=>$dependency){
            $class_name = $dependency['class'];
            $factory_class = $dependency['factory'];
            $Factory = new $factory_class($this->database, $dependency_registry, $class_name);
            $Factory->initRosterStudentGroupDependency($RosterStudents,$type);
        }
        }
    }

    public function updateRosterStudents(int $roster_id, $data, StudentFactory $studentFactory):bool{
        $RosterStudents = $this->getRosterStudents($roster_id);
        $any_error = false;
        /** @var RosterStudent $RosterStudent */
        foreach($RosterStudents AS $RosterStudent){
            $student_id = $RosterStudent->getStudentID();
            if(isset($data[$student_id])){
                $new_student_data = json_decode($data[$student_id],true);
                $success = $this->saveRosterStudent($RosterStudent,$new_student_data);
                if(!$success){
                    $any_error = true;
                }
                unset($data[$student_id]);
            }else{
                $this->deleteRosterStudent($RosterStudent);
            }
        }
        if(!empty($data)){
           
            foreach($data AS $student_id=>$jsonstr){
                $DATA = json_decode($jsonstr,true);
                $RosterStudent = $this->getRosterStudent(0);
                $Student = $studentFactory->getStudent($DATA['student_id']);
                
                $DATA['roster_id'] = $roster_id;
                $DATA['date_added'] = date('Y-m-d H:i:s');
                $DATA['lastname'] = $Student->getLastName();
                $DATA['firstname'] = $Student->getFirstname();
                $DATA['age'] = $Student->getCurrentAge();
                $DATA['gender'] = $Student->getGender();
                $DATA['grade'] = $Student->getGrade();
                $DATA['is_aes'] = $Student->isAES()?'AES':'No';
                $success = $this->saveRosterStudent($RosterStudent, $DATA);
                if(!$success){
                    $any_error = true;
                }
            }
        }
        return !$any_error;
    }

    public function saveRosterStudent(RosterStudent $RosterStudent, array $DATA):bool{
        global $logger;
        $id = $RosterStudent->getID();
        $student_id = $RosterStudent->getStudentID();
        $jersey_number = $RosterStudent->getDataValue('jersey_number');
        $is_jv = $RosterStudent->getDataValue('is_jv');
        $insert = $this->saveItem($DATA, $id);
        $RosterStudent->initialize($insert);
        $change_type = $id?'ALTER':'INSERT';
        $change_value = $RosterStudent->getName().' Record '.($id?'Updated':'Added');
        if($change_type == 'ALTER'){
            $data_jersey_number = $DATA['jersey_number']??'';
            $data_is_jv = $DATA['is_jv']??'';
            if($student_id != $DATA['student_id'] || $jersey_number != $data_jersey_number || $is_jv != $data_is_jv){
                $this->addChangeLogRecord($change_type,$RosterStudent->getID(),$change_value);
            }
        }else{
            $this->addChangeLogRecord($change_type,$RosterStudent->getID(),$change_value);
        }
        
       
        return true;
    }

    protected function saveRosterStudentDependencies(RosterStudent $RosterStudent, array $DATA){
        $dependency_list = $RosterStudent->getDependencyList();
        foreach($dependency_list AS $type=>$class_name){
            $factory_class = $class_name::getFactoryClass();
            $Factory = new $factory_class($this->database, $this->getDependencies(),$class_name);
            $Factory->saveRosterStudentDependencies($RosterStudent, $DATA);
        }
    }

    public function deleteRosterStudent(RosterStudent $RosterStudent):bool{
        $this->addChangeLogRecord('DELETE',$RosterStudent->getID(),$RosterStudent->getName().' Record Deleted');
        return $this->deleteItem($RosterStudent->getID());
    }

    public function getDependencies():array{
        return $this->dependencies;
    }

    public function getDependencyValue(string $key){
        $value = isset($this->dependencies[$key])?$this->dependencies[$key]:null;
        return $value;
    }

}