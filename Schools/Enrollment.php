<?php
namespace ElevenFingersCore\GAPPS\Schools;
use ElevenFingersCore\Database\DatabaseConnectorPDO;
use ElevenFingersCore\Utilities\MessageTrait;
use ElevenFingersCore\Utilities\SortableObject;


class Enrollment{
    use MessageTrait;

    protected $database;

    protected $school_id = 0;

    protected $school_year = '';
    protected $School;

    protected $Students;

    protected $enrollment_list = array();

    static $db_table = 'school_enrollment';

    function __construct(DatabaseConnectorPDO $database, ?int $school_id = 0, ?string $school_year = ''){
        $this->database = $database;
        $this->school_id = $school_id;
        $this->school_year = $school_year;
    }

    public function getEnrollmentList():array{
        if(empty($this->enrollment_list)){
            $enrollment_list = array();
            $Students = $this->getStudents();
            $Sortable = new SortableObject('getFullName');
            $Sortable->Sort($Students);
            foreach($Students AS $Student){
                $grade = $Student->getGrade();
                if(empty($enrollment_list[$grade])){
                    $enrollment_list[$grade] = array('grade'=>'grade-'.$grade,'students'=>array());
                }
                $enrollment_list[$grade]['students'][] = $Student->getDATA();
            }
            ksort($enrollment_list);
            $this->enrollment_list = $enrollment_list;
        }
        return $this->enrollment_list;
    }

    /** @return EnrollmentStudent[] */
    public function getStudents():array{
        if(empty($this->Students)){
            $School = $this->getSchool();
            $school_year = $this->getSchoolYear();
            $keys = array('school_id'=>$School->getID(),'school_year'=>$school_year);
            $student_info = $this->database->getArrayListByKey(static::$db_table, $keys, 'grade');
        
        
            $info_by_id = array();
            $student_ids = array();
            foreach($student_info AS $info){
                $student_ids[] = $info['student_id'];
                $info_by_id[$info['student_id']] = $info;
            }
            $Students = EnrollmentStudent::getStudents($this->database, array('id'=>array('IN'=>$student_ids)));
            /** @var EnrollmentStudent $Student */
            foreach($Students AS $Student){
                $info = $info_by_id[$Student->getID()];
                $Student->setAge($info['age']);
                $Student->setGrade($info['grade']);
                $Student->setEnrollmentYear($school_year);
            }
            $this->Students = $Students;
        }
        return $this->Students;
    }

    public function getEnrollmentYears():array{
        $School = $this->getSchool();
        $school_id = $School->getID();
        $school_years = $this->database->getResultList('SELECT DISTINCT school_year FROM '.static::$db_table.' WHERE school_id = :school_id ORDER BY school_year', array(':school_id'=>$school_id),'school_year');
        //asort($school_years);
        return $school_years;
    }

    public function getSchoolYear():string{
        return $this->school_year;
    }


    public function setSchoolYear(string $school_year){
        $this->school_year = $school_year;
    }

    public function getSchool():School{
        if(empty($this->School)){
            $this->School = new School($this->database, $this->school_id);
        }
        return $this->School;
    }
    public function setSchool(School $school){
        $this->School = $school;
    }

}

