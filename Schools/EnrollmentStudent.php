<?php
namespace ElevenFingersCore\GAPPS\Schools;
use ElevenFingersCore\Database\DatabaseConnectorPDO;
use ElevenFingersCore\Utilities\MessageTrait;


class EnrollmentStudent extends Student{

    protected $age;
    protected $grade;
    protected $enrollment_year;

    public function getDATA():array{
        $data = parent::getDATA();
        $data['age'] = $this->getCurrentAge();
        $data['grade'] = $this->getGrade();
        $data['enrollment_year'] = $this->getEnrollmentYear();
        return $data;
    }

    public function getCurrentAge(\DateTimeImmutable|null $Today = null): int|null{
        return $this->age;
    }
    public function getGrade(\DateTimeImmutable|null $Today = null): string|null
    {
        return $this->grade;
    }

    public function getEnrollmentYear():string{
        return $this->enrollment_year;
    }
    public function setAge(int $age){
        $this->age = $age;
    }

    public function setGrade(int $grade){
        $this->grade = $grade;
    }

    public function setEnrollmentYear(string $enrollmentYear){
        $this->enrollment_year = $enrollmentYear;
    }

}


