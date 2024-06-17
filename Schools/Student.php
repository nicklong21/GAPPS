<?php
namespace ElevenFingersCore\GAPPS\Schools;

use DateTimeImmutable;
use ElevenFingersCore\Database\DatabaseConnectorPDO;
use ElevenFingersCore\Utilities\MessageTrait;
use ElevenFingersCore\Utilities\InitializeTrait;

class Student{
    use MessageTrait;
    use InitializeTrait;
    protected $database;
    protected $id = 0;
    protected $DATA;
    protected $Students = array();
    static $db_table = 'students';
    static $template = array('id'=>0,
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

    function __construct(DatabaseConnectorPDO $DB, ?int $id = 0, ?array $DATA = array()){
        $this->database = $DB;
        $this->initialize($id,$DATA);
    }

    public function getID():int{
        return $this->id;
    }

    public function getStatus():?string{
        return $this->DATA['status'];
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
        $grade = null;
        $year_entered_9th = $this->DATA['entered9th'];
        if(!empty($year_entered_9th)){
            $this_year = date('Y');
            $this_month = date('n');
            if($this_month <= 6){
                $this_year = $this_year-1;
            }
            $grade = 9 - ($year_entered_9th - $this_year);
            if($grade < 1){
                $grade = 'PreK';
            }else if($grade > 12){
                $grade = 'Graduate';
            }
        }
        return $grade;
    }

    public function getYearEntered9th():int{
        return $this->DATA['entered9th'];
    }

    public function getGender():string{
        return $this->DATA['gender'];
    }

    public function getCurrentAge(?DateTimeImmutable $Today = null):?int{
        $age = null;
        $dob = $this->DATA['dob'];
        if(!empty($dob)){
            try{
                $BirthDate = new \DateTimeImmutable($dob);
                if(empty($Today)){
                    $Today = new \DateTimeImmutable();
                }
                $AgeInterval = $Today->diff($BirthDate);
                $age = $AgeInterval->format('%y');
            }catch(\Exception $e){
                $this->addErrorMsg('Invalid Format for Date of Birth','Error');
            }
        }
        return $age;
    }



    public function isAES():bool{

        return ($this->DATA['is_aes'] == 'AES' || $this->DATA['is_aes']=='Yes')?true:false;
    }

    public function isInRoster():bool{
        global $logger;
        $logger->debug('Student::isInRoster()',$this->DATA);
        return $this->DATA['in_sport_roster']?true:false;
    }

    /** @return Student[] */
    public static function getStudents(DatabaseConnectorPDO $DB, ?Array $filter = array()):array{
        $Students = array();
        $data = $DB->getArrayListByKey(static::$db_table,$filter);
        foreach($data AS $student_data){
            $Students[] = new static($DB, null, $student_data);
        }
        return $Students;
    }
    
}


?>