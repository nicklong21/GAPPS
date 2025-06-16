<?php
namespace ElevenFingersCore\GAPPS\Schools;

use DateTimeImmutable;
use ElevenFingersCore\GAPPS\ChangeLog;
use ElevenFingersCore\GAPPS\FactoryTrait;
use ElevenFingersCore\Utilities\MessageTrait;
use ElevenFingersCore\Database\DatabaseConnectorPDO;
use ElevenFingersCore\Utilities\UtilityFunctions;

class StudentFactory{
    use MessageTrait;
    use FactoryTrait;

    protected $school_year = null;

    protected $db_table = 'students';

    protected $db_enrollment_table = 'school_enrollment';

    protected $batch_enrollment_init = false;

    protected $ChangeLogger;

    protected $schema  = array(
        'id'=>0,
        'student_id_hash'=>'',
        'school_id'=>0,
        'firstname'=>'',
        'lastname'=>'',
        'photo_resource'=>0,
        'gender'=>'',
        'height'=>'',
        'weight'=>'',
        'age'=>0,
        'dob'=>'0000-00-00',
        'is_aes'=>'',
        'aes_status'=>'',
        'status'=>'ELIGIBLE',
        'aes_record'=>0,
        'entered9th'=>0,
        'date_created'=>null,
        'in_sport_roster'=>0,
        'ignore_conflict'=>[],
   );
   protected $enrollment_schema = array(
        'id'=>0,
        'school_id'=>0,
        'school_year'=>null,
        'student_id'=>0,
        'grade'=>0,
        'status'=>'',
   );

    function __construct(DatabaseConnectorPDO $DB){
        $this->database = $DB;
        $this->setItemClass(Student::class);
    }

    public function setChangeLogger(ChangeLog $Logger){
        $this->ChangeLogger = $Logger;
    }

    protected function addChangeLogRecord(string $table, string $type, int $record_id, string $value){
        if(!empty($this->ChangeLogger)){
            $this->ChangeLogger->addLog('Student Enrollment',$type,$record_id,$value);
        }
    }

    public function setSchoolYear(?string $school_year){
        $this->school_year = $school_year;
    }

    public function getSchoolYear():?string{
        return $this->school_year;
    }

    public function getIsNewStudent(int $student_id):bool{
        $in_roster = $this->database->getResultByKey($this->db_table,['id'=>$student_id],'in_sport_roster');
        return $in_roster?false:true;
    }

    public function setIsNewStudent(int $student_id, ?int $v = 1){
        $insert = ['id'=>$student_id,'in_sport_roster'=>$v];
        $this->database->insertArray($this->db_table,$insert,'id');
    }

    public function getStudent(?int $id = null, ?array $DATA = array()):Student{
        $Student = $this->getItem($id, $DATA);
        $school_year = $this->getSchoolYear();
        if(!$this->batch_enrollment_init && !empty($Student->getID()) && !empty($school_year)){
            $enrollment_data = $this->database->getArrayByKey($this->db_enrollment_table,['student_id'=>$Student->getID(), 'school_year'=>$school_year]);
            if(!empty($enrollment_data)){
                $Student->initializeEnrollment($enrollment_data);
            }else{
                $Student->setEnrollmentSchoolYear($school_year);
            }
        }elseif(empty($Student->getID()) && !empty($school_year)){
            $Student->initializeEnrollment(['school_year'=>$school_year]);
        }
        return $Student;
    }

    /**
     * Summary of getStudentsByIds
     * @param array $ids
     * @return Student[]
     */
    public function getStudentsByIds(array $ids):array{
        $filter = array('id'=>array('IN'=>$ids));
        $students_data = $this->database->getArrayListByKey($this->db_table, $filter,['lastname','firstname']);
        $students_enrollment_data_by_id = $this->getStudentEnrollmentDATAByStudentID($ids);
        $this->batch_enrollment_init = true;
        $Students = array();
        foreach($students_data AS $DATA){
            $Student = $this->getStudent(null, $DATA);
            $enrollment_data = $students_enrollment_data_by_id[$Student->getID()]??[];
            if(!empty($enrollment_data)){
                $Student->initializeEnrollment($enrollment_data);
            }
            $Students[] = $Student;
        }
        $this->batch_enrollment_init = false;
        return $Students;
    }

    /**
     * Summary of getStudentsBySchoolID
     * @param int $school_id
     * @return Student[]
     */
    public function getStudentsBySchoolID(int $school_id):array{
        $students_enrollment_data_by_id = $this->getStudentEnrollmentDATABySchoolID($school_id);
        $student_ids = array_keys($students_enrollment_data_by_id);
        $filter = array('school_id'=>$school_id,'id'=>['IN'=>$student_ids]);
        $students_data = $this->database->getArrayListByKey($this->db_table, $filter,['lastname','firstname']);        
        $this->batch_enrollment_init = true;
        //$school_year = $this->getSchoolYear();
        //$this->setSchoolYear($school_year);
        $Students = array();
        foreach($students_data AS $DATA){
            $Student = $this->getStudent(null, $DATA);
            $enrollment_data = $students_enrollment_data_by_id[$Student->getID()]??[];
            if(!empty($enrollment_data)){
                $Student->initializeEnrollment($enrollment_data);
            }
            $Students[] = $Student;
        }
        $this->batch_enrollment_init = false;
        //$this->setSchoolYear($school_year);
        return $Students;
    }


    public function getStudentEnrollmentDATAByStudentID(array $student_ids):array{
        $students_enrollment_data_by_id = [];
        $school_year = $this->getSchoolYear();
        if(!empty($school_year)){
            $students_enrollment_data = $this->database->getArrayListByKey($this->db_enrollment_table,['school_year'=>$school_year,'student_id'=>['IN'=>$student_ids]]);
            foreach($students_enrollment_data AS $data){
                $student_id = $data['student_id'];
                $students_enrollment_data_by_id[$student_id] = $data;
            }
        }
        return $students_enrollment_data_by_id;
    }


    public function getStudentEnrollmentDATABySchoolID(int $school_id):array{
        $students_enrollment_data_by_id = [];
        $school_year = $this->getSchoolYear();
        if(!empty($school_year)){
            $students_enrollment_data = $this->database->getArrayListByKey($this->db_enrollment_table,['school_year'=>$school_year,'school_id'=>$school_id]);
            foreach($students_enrollment_data AS $data){
                $student_id = $data['student_id'];
                $students_enrollment_data_by_id[$student_id] = $data;
            }
        }
        return $students_enrollment_data_by_id;
    }


    public function getEnrollmentYears(int $school_id):array{
        $school_years = $this->database->getResultList('SELECT DISTINCT school_year FROM '.$this->db_enrollment_table.' WHERE school_id = :school_id ORDER BY school_year', array(':school_id'=>$school_id),'school_year');
        //asort($school_years);
        return $school_years;
    }

    public function saveStudent(Student $Student, Array $DATA):bool{
        $DATA['school_id'] = $Student->getSchoolID();
        if(!$Student->getID()){
            $DATA['date_created'] = date('Y-m-d');
        }
        $change_type = $Student->getID()?'ALTER':'INSERT';
        $original_dob = $Student->getDateofBirth('m/d/Y');
        $original_name = $Student->getFullName();
        $original_entered9th = $Student->getYearEntered9th();
        if(array_key_exists('ignore_conflict', $DATA)){
            if(!is_string($DATA['ignore_conflict'])){
                $DATA['ignore_conflict'] = json_encode($DATA['ignore_conflict']);
            }
        }
        $insert = $this->saveItem($DATA, $Student->getID());
        $Student->initialize($insert);
        if($change_type == 'INSERT'){
            $change_value = $Student->getFullName().' Record: DOB: '.$Student->getDateofBirth('m/d/Y');
            $this->addChangeLogRecord('Student Records',$change_type,$Student->getID(),$change_value);
        }else{
            $new_dob = $Student->getDateofBirth('m/d/Y');
            $new_name = $Student->getFullName();
            $new_entered9th = $Student->getYearEntered9th();
            if($new_name !== $original_name){
                $this->addChangeLogRecord('Student Records',$change_type, $Student->getID(),$new_name.' Student Record: Name changed from '.$original_name);
            }
            if($new_dob !== $original_dob){
                $this->addChangeLogRecord('Student Records',$change_type,$Student->getID(),$new_name.' Student  Record: DOB Changed from '.$original_dob.' to '.$new_dob );
            }
            if($new_entered9th !== $original_entered9th){
                $this->addChangeLogRecord('Student Records',$change_type,$Student->getID(),$new_name.' Student Record: Year Entered 9th Changed from '.$original_entered9th.' to '.$new_entered9th);
            }
        }
        
        
        return true;
    }

    public function saveEnrollmentRecord(Student $Student, array $DATA){
        $enrollment_record_id = $Student->getEnrollmentRecordID();
        $current_status = $Student->getStatus();
        $current_grade = $Student->getGrade();
        $enrollment_year = $this->getSchoolYear();

        $new_grade = $DATA['grade']?? 0;
        if($new_grade == 'PreK'){
            $new_grade = 0;
        }elseif($new_grade == 'Graduate'){
            $new_grade = 13;
        }

        $enrollment_data = [
            'id'=>$enrollment_record_id,
            'student_id'=>$Student->getID(),
            'school_id'=>$Student->getSchoolID(),
            'school_year'=>$enrollment_year,
            'status'=>$DATA['status'],
        ];
        switch($DATA['grade']){
            case 'kindergarten':
                $enrollment_data['grade'] = 0;
            break;
            case 'Graduate':
                $enrollment_data['grade'] = 13;
            break;
            default:
                $enrollment_data['grade'] = intval($DATA['grade']);
        }
        
        $this->database->insertArray($this->db_enrollment_table,$enrollment_data,'id');
        $Student->initializeEnrollment($enrollment_data);
        
        if(!empty($enrollment_record_id)){
            $change_type = 'ALTER';
            if($current_grade != $enrollment_data['grade']){
                $this->addChangeLogRecord('Enrollment Records',$change_type,$enrollment_data['id'],$Student->getFullName().' Enrollment Record Changed: Grade changed to '.$enrollment_data['grade'].' from '.$current_grade.' for '.$enrollment_year);
            }
            if($enrollment_data['status'] != $current_status){
                $this->addChangeLogRecord('Enrollment Records',$change_type,$enrollment_data['id'],$Student->getFullName().' Enrollment Record Changed: Status changed to '.$DATA['status'].' for '.$enrollment_year);
            }
        }else{
            $change_type = 'INSERT';
            $change_value = $Student->getFullName().' Enrollment Record Created: Grade '.$enrollment_data['grade'].' '.$enrollment_year.' '.$enrollment_data['status'];
            $this->addChangeLogRecord('Enrollment Records',$change_type,$enrollment_data['id'],$change_value);
        }
    }


    public function deleteStudent(Student $Student):bool{
        $id = $Student->getID();
        $success = $this->deleteItem($id);
        if($success){
            $success = $this->database->deleteByKey($this->db_enrollment_table,['student_id'=>$id]);
            $this->addChangeLogRecord('Student Records','DELETE',$id,$Student->getFullName().' Student Record Deleted');
        }
        return $success;
    }

}