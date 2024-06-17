<?php
namespace ElevenFingersCore\GAPPS\Sports;

use DateTimeImmutable;
use ElevenFingersCore\Database\DatabaseConnectorPDO;
use ElevenFingersCore\Utilities\MessageTrait;
use ElevenFingersCore\Utilities\InitializeTrait;
use ElevenFingersCore\GAPPS\Schools\Student;

class RosterStudent
{
    use MessageTrait;
    use InitializeTrait;
    protected $database;
    protected $id = 0;
    protected $DATA;
    protected $Student;
    static $db_table = 'rosters_students';
    static $template = array(
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
    );

    function __construct(DatabaseConnectorPDO $DB, ?int $id = 0, ?array $DATA = array())
    {
        $this->database = $DB;
        $this->initialize($id, $DATA);
    }

    public function getID(): int
    {
        return $this->id;
    }

    public function getDATA():array{
        return $this->DATA;
    }

    public function setDATA(array $DATA){
        $this->DATA = array_merge($this->DATA, $DATA);
    }
    

    public function getStudentInfo():array{
        $data = $this->DATA;
        
        
        return $data;
    }

    public function getStudentID():int{
        $Student = $this->getStudent();
        return $Student->getID();
    }

    public function getStatus(): ?string
    {
        return $this->DATA['status'];
    }

    public function isAES():bool{
        return $this->DATA['is_aes'] == 'No'?false:true;
    }

    public function isNewStudent():bool{
        $Student = $this->getStudent();
        return $Student->isInRoster()?false:true;
    }

    public function getName(){
        $Student = $this->getStudent();
        $name = $Student->getFullName();
        return $name;
    }

    public function setStudent(Student $Student){
        $this->Student = $Student;
    }

    public function getStudent():Student{
        if(empty($this->Student)){
            $student_id = $this->DATA['student_id'];
            $this->Student = new Student($this->database, $student_id);
        }
        return $this->Student;
    }

    public function initializeFromStudent(?DateTimeImmutable $RosterDate = null){
        if(empty($RosterDate)){
            $RosterDate = new DateTimeImmutable();
        }
        $Student = $this->getStudent();
        $DATA = array(
            'student_id'=>$Student->getID(),
            'lastname'=>$Student->getLastname(),
            'firstname'=>$Student->getFirstname(),
            'age'=>$Student->getCurrentAge($RosterDate),
            'gender'=>$Student->getGender(),
            'grade'=>$Student->getGrade(),
            'is_aes'=>$Student->isAES()?'AES':'No',
            'date_added'=>date('Y-m-d H:i:s'),
        );
        $this->setDATA($DATA);
    }


    /** @return static[] */
    public static function getRosterStudents(DatabaseConnectorPDO $DB, int $roster_id):array{
        $data = $DB->getArrayListByKey(static::$db_table,array('roster_id'=>$roster_id));
        $RosterStudents = array();
        foreach($data AS $DATA){
            $RosterStudents[] = new static($DB, null,  $DATA);
        }
        return $RosterStudents;

    }

    public static function findRosterStudent(DatabaseConnectorPDO $DB, int $roster_id, int $student_id):RosterStudent{
        $data = $DB->getArrayByKey(static::$db_table,array('roster_id'=>$roster_id,'student_id'=>$student_id));
        $RosterStudent = new static($DB, null, $data);
        $RosterStudent->setDATA(array('student_id'=>$student_id,'roster_id'=>$roster_id));
        return $RosterStudent;
    }

}
