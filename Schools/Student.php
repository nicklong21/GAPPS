<?php
namespace ElevenFingersCore\GAPPS\Schools;

use DateTimeImmutable;
use ElevenFingersCore\Database\DatabaseConnectorPDO;
use ElevenFingersCore\Utilities\MessageTrait;
use ElevenFingersCore\Utilities\UtilityFunctions;
use ElevenFingersCore\GAPPS\InitializeTrait;

class Student{
    use MessageTrait;
    use InitializeTrait;
    protected $grade;

    protected $school_id;
    protected $EnrollmentDate;

    protected $enrollment_status;

    protected $enrollment_record_id;


    function __construct($DATA){
        $this->initialize($DATA);
        $this->school_id = $this->DATA['school_id']??0;
    }

    public function initialize(array $DATA)
    {
        
        $this->DATA = array_merge($this->DATA,$DATA);
        $this->id = $this->DATA['id']??0;

        if(!empty($this->DATA['ignore_conflict'])){
            if(is_string($this->DATA['ignore_conflict'])){
                $this->DATA['ignore_conflict'] = json_decode($this->DATA['ignore_conflict']);
            }elseif(is_array($DATA['ignore_conflict'])){
                //
            }else{
                $this->DATA['ignore_conflict'] = [];
            }
        }else{
            $this->DATA['ignore_conflict'] = [];
        }
    }

    public function initializeEnrollment(array $DATA){
        $school_year = $DATA['school_year'];
        $this->setEnrollmentSchoolYear($school_year);
        
        if(!empty($DATA['grade'])){
            $grade = $DATA['grade'];
            $this->setGrade($grade);
        }
        
        if(!empty($DATA['status'])){
            $status = $DATA['status'];
            $this->setStatus($status);
        }
        $this->enrollment_record_id = $DATA['id']??null;
        
    }

    public function getDATA():array{
        $DATA = $this->DATA;
        $DATA['gender'] = $this->getGender();
        return $DATA;
    }

    public function getID():int{
        return $this->id;
    }

    public function setStatus(?string $status){
        $this->enrollment_status = $status;
    }

    public function getStatus():?string{
        if(!empty($this->enrollment_status)){
            $status = $this->enrollment_status;
        }else{
            $status = $this->DATA['status'];
        }
        return $status;
    }

    public function getFullName():string{
        return $this->DATA['lastname'].', '.$this->DATA['firstname'];
    }

    public function getFirstname():string{
        return $this->DATA['firstname'];
    }
    public function getLastname():string{
        return $this->DATA['lastname'];
    }

    public function getGrade():?string{
        if(empty($this->grade)){
            $grade = null;
            $year_entered_9th = $this->getYearEntered9th();
            if(!empty($year_entered_9th)){
                if(empty($Today)){
                    $Today = $this->getEnrollmentDate();
                }
                $this_year = $Today->format('Y');
                $this_month = $Today->format('n');
                if($this_month < 6){
                    $this_year = $this_year-1;
                }
                $this->grade = 9 - ($year_entered_9th - $this_year);
            }
        }
        $grade = $this->grade;
        if($grade < 1){
            $grade = 'PreK';
        }else if($grade > 12){
            $grade = 'Graduate';
        }
        return $grade;
    }

    public function setGrade(int $grade){
        $this->grade = $grade;
    }

    public function getYearEntered9th():int{
        return $this->DATA['entered9th'];
    }

    public function getGender():string{
        $gender = $this->DATA['gender']??null;
        if(is_string($gender) && strlen($gender) > 1){
            $gender = substr($gender,0,1);
        }
        return $gender;
    }

    public function getDateofBirth(?string $format = null):null|string|DateTimeImmutable{
        global $logger;
        $dob = $this->DATA['dob'];
        $BirthDate = null;
        if(!empty($dob)){
            $BirthDate = new DateTimeImmutable($dob);
            try{
                $BirthDate = new DateTimeImmutable($dob);
                if(!empty($format)){
                    $BirthDate = $BirthDate->format($format);
                }
            }catch(\Exception $e){
                $this->addErrorMsg('Invalid Format for Date of Birth','Error',array('student_id'=>$this->id, 'dob'=>$dob));
                $logger->debug('Invalid Format for Date of Birth',['student_id'=>$this->id, 'dob'=>$dob]);
            }
        }
        return $BirthDate;
    }

    public function getCurrentAge(?DateTimeImmutable $Today = null):?int{
        $age = null;
        $BirthDate = $this->getDateofBirth();
        if($BirthDate){
            if(empty($Today)){
                $Today = $this->getEnrollmentDate();
            }
            $AgeInterval = $Today->diff($BirthDate);
            $age = $AgeInterval->format('%y');
        }
        return $age;
    }

    public function getSchoolID():int{
        return $this->school_id;
    }

    public function setSchoolID(int $id){
        $this->school_id = $id;
    }

    public function setEnrollmentSchoolYear(string $school_year){
        $year = substr($school_year,0,4);
        $this->EnrollmentDate = new DateTimeImmutable($year.'-09-01');
    }

    public function setEnrollmentDate(DateTimeImmutable $Date){
        $this->EnrollmentDate = $Date;
    }

    /**
     * Summary of getDefaultAgeDate
     * September 1 of Current School Year
     * @return \DateTimeImmutable
     */
    public function getEnrollmentDate():DateTimeImmutable{
        if(empty($this->EnrollmentDate)){
            $date_cutoff = '06-30';
            $Date = new DateTimeImmutable('now');
            $year = $Date->format('Y');
            $Cutoff = new DateTimeImmutable($year.'-'.$date_cutoff);
            if($Date >= $Cutoff){
                $Y = $Date->format('Y');
            }else{
                $Past = $Date->modify('-1 Year');
                $Y = $Past->format('Y');
            }
            $this->EnrollmentDate = new DateTimeImmutable($Y.'-09-01');
        }
        return $this->EnrollmentDate;
    }


    public function getEnrollmentRecordID():?int{
        return $this->enrollment_record_id??null;
    }



    public function isAES():bool{

        return ($this->DATA['is_aes'] == 'AES' || $this->DATA['is_aes']=='Yes')?true:false;
    }

    public function isInRoster():bool{
        return $this->DATA['in_sport_roster']?true:false;
    }
}


