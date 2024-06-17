<?php
namespace ElevenFingersCore\GAPPS\Schools;

use ElevenFingersCore\Database\DatabaseConnectorPDO;
use ElevenFingersCore\Utilities\MessageTrait;
use ElevenFingersCore\Utilities\InitializeTrait;
use ElevenFingersCore\Utilities\SortableObject;

class School{
    use MessageTrait;
    use InitializeTrait;
    protected $database;
    protected $id = 0;
    protected $DATA;

    protected $Staff;
    protected $Students = array();
    protected $Coaches = array();
    protected $Administrators = array();
    static $db_table = 'schools';
    static $db_staff_xref = 'school_staff_xref';
    static $template = array('id'=>0,
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
                );

    function __construct(DatabaseConnectorPDO $DB, ?int $id = 0, ?array $DATA = array()){
        $this->database = $DB;
        $this->initialize($id,$DATA);
    }

    public function getID():int{
        return $this->id;
    }

    public function getTitle():string{  
        return $this->DATA['title'];
    }

    public function getDATA():array{
        return $this->DATA;
    }

    public function getStatus():?string{
        return $this->DATA['status'];
    }
    /** @return Student[] */
    public function getEnrollment(?string $year = null, ?string $status = null):array{
        if(empty($this->Students)){
            $Students = Student::getStudents($this->database, array('school_id'=>$this->id));
            $Sortable = new SortableObject('getFullName');
            $Sortable->Sort($Students);
            $Sortable->setSortBy('getYearEntered9th');
            $Sortable->Sort($Students);
            $this->Students = $Students;
        }
        $Students = $this->Students;
        if(!empty($year)){
            $Filtered = array();
            foreach($Students AS $i=>$Student){
                if($Student->getYearEntered9th() == $year){
                    $Filtered[] = $Student;
                }
            }
            $Students = $Filtered;
        }
        if(!empty($status)){
            $Filtered = array();
            foreach($Students AS $i=>$Student){
                if($Student->getStatus() == $status){
                    $Filtered[] = $Student;
                }
            }
            $Students = $Filtered;
        }
        return $this->Students;
    }

    /** @return Coach[] */
    public function getStaff(?string $type = null):array{
        if(empty($this->Staff)){
            $this->Staff = Coach::getSchoolStaff($this->database,$this->getID(), array('status'=>'Active'));
        }
        $Staff = array();
        if(!empty($type)){
            foreach($this->Staff AS $Coach){
                if($type == $Coach->getAccountTypeName()){
                    $Staff[] = $Coach;
                }
            }
        }else{
            $Staff[] = $this->Staff;
        }
        return $Staff;
    }

    /** @return mixed[Student[]] */
    public function getEnrollmentByGrade(bool $include_graduates = false):array{
        $Enrollment = $this->getEnrollment();
        $EnrollmentByGrade = array();
        foreach($Enrollment AS $Student){
            $grade = $Student->getGrade();
            if($include_graduates || $grade != 'Graduate'){
                if(empty($EnrollmentByGrade[$grade])){
                    $EnrollmentByGrade[$grade] = array();
                }
                $EnrollmentByGrade[$grade][] = $Student;
            }
        }
        return $EnrollmentByGrade;
    }

    /** @return School[] */
    public static function getSchools(DatabaseConnectorPDO $DB, ?array $filter = array(), null|string|array $order_by = null):array{
        $data = $DB->getArrayListByKey(static::$db_table,$filter, $order_by);
        $Schools = array();
        foreach($data AS $d){
            $Schools[] = new static($DB, null, $d);
        }
        return $Schools;
    }

}


