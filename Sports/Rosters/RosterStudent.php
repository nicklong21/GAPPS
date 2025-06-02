<?php
namespace ElevenFingersCore\GAPPS\Sports\Rosters;

use DateTimeImmutable;
use ElevenFingersCore\Database\DatabaseConnectorPDO;
use ElevenFingersCore\Utilities\MessageTrait;
use ElevenFingersCore\GAPPS\InitializeTrait;
use ElevenFingersCore\GAPPS\Schools\Student;
use ElevenFingersCore\GAPPS\Schools\StudentFactory;
use ElevenFingersCore\GAPPS\Sports\Rosters\RosterStudentDependencies\RosterStudentDependency;

class RosterStudent
{
    use MessageTrait;
    use InitializeTrait;
    protected $Student;
    protected $StudentFactory;

    protected $dependency_list = [];

    function __construct(array $DATA)
    {
        $this->initialize($DATA);
    }

    public function setDATA(array $DATA){
        $this->DATA = array_merge($this->DATA, $DATA);
    }

    public function getDataValue($key){
        $value = $this->DATA[$key]??null;
        return $value;
    }

    public function getStudentID():int{
        
        return $this->DATA['student_id']??0;
    }

    public function getRosterID():int{
        return $this->DATA['roster_id']??0;
    }

    public function getStatus(): ?string
    {
        return $this->DATA['status'];
    }

    public function isAES():bool{
        return $this->DATA['is_aes'] == 'No'?false:true;
    }

    public function getName(){
        $name = $this->DATA['lastname'].', '.$this->DATA['firstname'];
        return $name;
    }

    public function getDependencyList():array{
        return $this->dependency_list;
    }

    public function setDependency(string $type, RosterStudentDependency $Dependency){
        
    }

    public function initializeFromStudent(Student $Student){
        
        $DATA = array(
            'student_id'=>$Student->getID(),
            'lastname'=>$Student->getLastname(),
            'firstname'=>$Student->getFirstname(),
            'age'=>$Student->getCurrentAge(),
            'gender'=>$Student->getGender(),
            'grade'=>$Student->getGrade(),
            'is_aes'=>$Student->isAES()?'AES':'No',
            'date_added'=>date('Y-m-d H:i:s'),
        );
        $this->initialize($DATA);
    }


}
