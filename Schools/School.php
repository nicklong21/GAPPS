<?php
namespace ElevenFingersCore\GAPPS\Schools;

use ElevenFingersCore\Database\DatabaseConnectorPDO;
use ElevenFingersCore\Utilities\MessageTrait;
use ElevenFingersCore\GAPPS\InitializeTrait;
use ElevenFingersCore\GAPPS\Sports\Seasons\SeasonFactory;
use ElevenFingersCore\Utilities\SortableObject;
use ElevenFingersCore\Utilities\UtilityFunctions;

class School{
    use MessageTrait;
    use InitializeTrait;

    protected $school_year;

    protected $Staff;
    protected $Students = array();

    protected $StudentFactory;

    protected $Coaches = array();
    protected $Administrators = array();


    function __construct(array $DATA){
        
        $this->initialize($DATA);
    }

    function setSchoolYear(string $school_year){
        $this->school_year = $school_year;
    }

    function getSchoolYear():?string{
        if(empty($this->school_year)){
            $this->school_year = UtilityFunctions::formatSchoolYear();
        }
        return $this->school_year;
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

    public function getWebsite():string{
        return $this->DATA['website'];
    }

    public function getFormattedAddress():string{
        $address = $this->DATA['address1'].'<br/>';
        if(!empty($this->DATA['address2'])){
            $address .= $this->DATA['address2'].'<br/>';
        }
        $address .= $this->DATA['city'].', '.(!empty($this->DATA['county'])?$this->DATA['county'].' ':'').'GA, '.$this->DATA['zip'];
        return $address;
    }

    public function getPhone():string{
        return $this->DATA['phone'];
    }

    public function getStatus():?string{
        return $this->DATA['status'];
    }
    /** @return Student[] */
    public function getEnrollment(?string $grade = null, ?string $status = null):array{
        if(empty($this->Students)){
            $id = $this->getID();
            $Students = $this->getStudentFactory()->getStudentsBySchoolID($id);
            $Sortable = new SortableObject('getGrade');
            $Sortable->Sort($Students);
            $this->Students = $Students;
        }
        $Students = $this->Students;
        if(!empty($grade)){
            $Filtered = array();
            foreach($Students AS $i=>$Student){
                if($Student->getGrade() == $grade){
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
        return $Students;
    }

    /** @return Coach[] */
    public function getStaff(?string $type = null):array{
        /*
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
            $Staff = $this->Staff;
        }
        return $Staff;
        */
        return array();
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

    public function getEnrollmentYears():array{
        return $this->getStudentFactory()->getEnrollmentYears($this->getID());
    }

    public function setStudentFactory(StudentFactory $studentFactory){
        $this->StudentFactory = $studentFactory;
    }
    public function getStudentFactory():StudentFactory{
        $this->StudentFactory->setSchoolYear($this->getSchoolYear());
        return $this->StudentFactory;
    }

}


