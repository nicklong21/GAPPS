<?php
namespace ElevenFingersCore\GAPPS\Schools;

use DateTimeImmutable;
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

    protected $schema  = array(
        'id'=>0,
        'student_id_hash'=>'',
        'school_id'=>0,
        'firstname'=>'',
        'lastname'=>0,
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
        $insert = $this->saveItem($DATA, $Student->getID());
        $Student->initialize($insert);
        return true;
    }

    public function saveEnrollmentRecord(Student $Student, array $DATA, ?int $enrollment_record_id = 0){
        $student_id = $Student->getID();
        $enrollment_year = isset($DATA['school_year'])?$DATA['school_year']:null;
        $grade = $DATA['grade']?? 0;
        if($grade == 'PreK'){
            $grade = 0;
        }elseif($grade = 'Graduate'){
            $grade = 13;
        }
        $enrollment_data = [];
        if(!empty($enrollment_year) && empty($enrollment_record_id)){
            $enrollment_data = $this->database->getArrayByKey($this->db_enrollment_table,['school_year'=>$enrollment_year,'student_id'=>$student_id]);
        }else{
            $enrollment_year = $this->getSchoolYear();
        }
        if(empty($enrollment_data)){
            $enrollment_data['id'] = $enrollment_record_id;
            $enrollment_data['student_id'] = $student_id;
            $enrollment_data['school_id'] = $Student->getSchoolID();
            $enrollment_data['school_year'] = $enrollment_year;
        }
        $enrollment_data['grade'] = $DATA['grade'];
        $enrollment_data['status'] = $DATA['status'];
        $this->database->insertArray($this->db_enrollment_table,$enrollment_data,'id');
        $Student->initializeEnrollment($enrollment_data);
    }

    public function deleteStudent(Student $Student):bool{
        $id = $Student->getID();
        $success = $this->deleteItem($id);
        if($success){
            $success = $this->database->deleteByKey($this->db_enrollment_table,['student_id'=>$id]);
        }
        return $success;
    }

}