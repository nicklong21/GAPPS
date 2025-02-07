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
        $this->setItemClass($dependencies['roster_student']);
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
        return $RosterStudent;
    }

    public function getRosterStudents(int $roster_id):array{
        $RosterStudents = [];
        if(!empty($roster_id)){
            $filter = array('roster_id'=>$roster_id);
            $roster_student_data = $this->database->getArrayListByKey($this->db_table, $filter,['lastname','firstname']);
            foreach($roster_student_data AS $DATA){
                $RosterStudents[] = $this->getRosterStudent(null, $DATA);
            }
        }
        return $RosterStudents;
    }

    public function getRosterStudentsByIDs(array $ids):array{
        $filter = array('id'=>array('IN'=>$ids));
        $roster_student_data = $this->database->getArrayListByKey($this->db_table, $filter);
        $RosterStudents = [];
        foreach($roster_student_data AS $DATA){
            $RosterStudents[] = $this->getRosterStudent(null, $DATA);
        }
        return $RosterStudents;
    }

    public function getRosterStudentsByStudentIDs(int $roster_id, array $student_ids):array{
        $filter = array('roster_id'=>$roster_id, 'student_id'=>['IN'=>$student_ids]);
        $roster_student_data = $this->database->getArrayListByKey($this->db_table, $filter);
        $RosterStudents = [];
        foreach($roster_student_data AS $DATA){
            $RosterStudents[] = $this->getRosterStudent(null, $DATA);
        }
        return $RosterStudents;
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
            if($student_id != $DATA['student_id'] || $jersey_number != $DATA['jersey_number'] || $is_jv != $DATA['is_jv']){
                $this->addChangeLogRecord($change_type,$RosterStudent->getID(),$change_value);
            }
        }else{
            $this->addChangeLogRecord($change_type,$RosterStudent->getID(),$change_value);
        }
        
       
        return true;
    }

    public function deleteRosterStudent(RosterStudent $RosterStudent):bool{
        $this->addChangeLogRecord('DELETE',$RosterStudent->getID(),$RosterStudent->getName().' Record Deleted');
        return $this->deleteItem($RosterStudent->getID());
    }

    public function getDependencies():array{
        return $this->dependencies;
    }

}