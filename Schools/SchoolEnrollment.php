<?php
namespace ElevenFingersCore\GAPPS\Schools;

use DateTimeImmutable;
use ElevenFingersCore\Utilities\MessageTrait;
use ElevenFingersCore\Utilities\XLSImport;
use Exception;

class SchoolEnrollment{
    use MessageTrait;
    
    protected $school_id;
    protected $StudentFactory;


    function __construct(int $school_id, StudentFactory $StudentFactory){
        $this->school_id = $school_id;
        $this->StudentFactory = $StudentFactory;
    }

    function previewEnrollment(string $school_year):array{

        $previous_year = static::decrementSchoolYear($school_year);
        $Students = $this->getEnrolledStudents($previous_year);
        $enrollment_by_grade = [
            1=>['grade'=>'grade-1','students'=>[]],
            2=>['grade'=>'grade-2','students'=>[]],
            3=>['grade'=>'grade-3','students'=>[]],
            4=>['grade'=>'grade-4','students'=>[]],
            5=>['grade'=>'grade-5','students'=>[]],
            6=>['grade'=>'grade-6','students'=>[]],
            7=>['grade'=>'grade-7','students'=>[]],
            8=>['grade'=>'grade-8','students'=>[]],
            9=>['grade'=>'grade-9','students'=>[]],
            10=>['grade'=>'grade-10','students'=>[]],
            11=>['grade'=>'grade-11','students'=>[]],
            12=>['grade'=>'grade-12','students'=>[]],
            13=>['grade'=>'grade-13','students'=>[]],
        ];
        foreach($Students AS $Student){
            if($Student->isAES()){
                continue;
            }
            $student_data = $Student->getDATA();
            $age = $Student->getCurrentAge();
            $ageA = $age;
            $student_data['age'] = (int)$age + 1;

            $grade = $Student->getGrade();
            if($grade == 'kindergarten'){
                $grade = 1;
            }elseif($grade == 'Graduate'){
                $grade = 13;
            }elseif(is_numeric($grade)){
                $grade++;
            }
            $student_data['grade'] = $grade;
            $student_data['status'] = $Student->getStatus();
            if(empty($enrollment_by_grade[$grade])){
                $enrollment_by_grade[$grade] = ['grade'=>'grade-'.$grade,'students'=>[]];
            }
            $enrollment_by_grade[$grade]['students'][] = $student_data;
        }
        return $enrollment_by_grade;
    }

    /**
     * updates $school_year enrollment
     * @param string $school_year
     * @param array $updated_students
     * @return void
     */
    public function updateEnrollment(string $school_year, array $updated_students = []){
        $this->StudentFactory->setSchoolYear($school_year);
        $updated_student_ids = [];
        foreach($updated_students AS $student_data){
            $student_id = $student_data['id']??0;
            if($student_id && is_numeric($student_id)){
                $updated_student_ids[] = $student_id;
            }
        }
        $StudentsByID = [];
        if(!empty($updated_student_ids)){
            $Students = $this->StudentFactory->getStudentsByIds($updated_student_ids);
            foreach($Students AS $Student){
                $StudentsByID[$Student->getID()] = $Student;
            }
        }

        foreach($updated_students AS $student_data){   
            $student_id = (!empty($student_data['id']) && is_numeric($student_data['id']))?$student_data['id']:0;
            if(isset($StudentsByID[$student_id])){
                $Student = $StudentsByID[$student_id];
            }else{
                $Student = $this->StudentFactory->getStudent($student_id);
            }
            $student_data['school_id'] = $this->school_id;
            if($Student->getID() || $student_data['status'] != 'REMOVED'){
                $this->StudentFactory->saveStudent($Student,$student_data);
                $this->StudentFactory->saveEnrollmentRecord($Student,$student_data);
            }
        }
    }

    /**
     * copies previous from $school_year enrollment with changes and additions
     * @param string $school_year
     * @param array $updated_students
     * @return void
     */
    /*
    public function confirmEnrollment(string $school_year, array $updated_students = []){
        $previous_year = static::decrementSchoolYear($school_year);
        $Students = $this->getEnrolledStudents($previous_year);
        return $this->saveEnrollment($school_year, $Students,$updated_students);
    }
    */

    /**
     * Summary of saveEnrollment
     * @param string $school_year
     * @param Student[] $Students
     * @param array $updated_students
     * @return void
     */
    /*
    private function saveEnrollment(string $school_year, array $Students, array $updated_students){

        $this->StudentFactory->setSchoolYear($school_year);
        $current_enrollment_data = $this->StudentFactory->getStudentEnrollmentDATABySchoolID($this->school_id);
        foreach($Students AS $Student){
            if($Student->isAES()){
                continue;
            }
            $student_id = $Student->getID();
            if(isset($updated_students[$student_id])){
                $student_data = $updated_students[$student_id];
                unset($updated_students[$student_id]);
                if(!empty($current_enrollment_data[$student_id])){
                    $enrollment_record_id = $current_enrollment_data[$student_id]['id'];
                }else{
                    $enrollment_record_id = 0;
                }
                if($student_data['status'] !== 'REMOVED'){
                    $this->StudentFactory->saveStudent($Student,$student_data);
                    $this->StudentFactory->saveEnrollmentRecord($Student,$student_data, $enrollment_record_id);
                }
            }else{
                $grade = $Student->getGrade();
                $status = $Student->getStatus();
                if($status != 'REMOVED'){
                    $DATA = ['school_year'=>$school_year,'grade'=>(int)$grade+1, 'status'=>$status];
                    if(!empty($current_enrollment_data[$student_id])){
                        $enrollment_record_id = $current_enrollment_data[$student_id]['id'];
                    }else{
                        $enrollment_record_id = 0;
                    }
                    $this->StudentFactory->saveEnrollmentRecord($Student,$DATA, $enrollment_record_id);
                }
            }
        }
        if(!empty($updated_students)){
            foreach($updated_students AS $student_data){
                if($student_data['status'] !== 'REMOVED'){
                    $Student = $this->StudentFactory->getStudent();
                    $Student->setSchoolID($this->school_id);
                    $this->StudentFactory->saveStudent($Student,$student_data);
                    $this->StudentFactory->saveEnrollmentRecord($Student,$student_data);
                }
            }
        }
    }
    */
    

    /**
     * Summary of getEnrolledStudents
     * @param string $school_year
     * @return Student[]
     */
    public function getEnrolledStudents(string $school_year):array{
        $this->StudentFactory->setSchoolYear($school_year);
        $Students = $this->StudentFactory->getStudentsBySchoolID($this->school_id);
        return $Students;
    }

    public function importEnrollmentFromXLS(string $filename, string $school_year){
        global $logger;
        $logger->debug('DATA',['POST'=>$_POST,'FILES'=>$_FILES]);
        $fields = array('student_id','lastname','firstname','gender','dob','is_aes','status','entered9th');
        $Importer = new XLSImport($fields);
        $file = $_FILES[$filename]['tmp_name'];
        if(!empty($file)){
            $data = $Importer->Import($file);
            $student_data = [];
            foreach($data AS $row){
                if($row['lastname'] == 'LASTNAME'){ continue;}
                if(empty($row['lastname'])){ continue;}
                
                if(!empty($row['student_id'])){
                    $row['student_id_hash'] = $this->hashStudentID($row['student_id'],$row['lastname']);
                }
                $student_data[] = $row;
            }
            return $this->previewEnrollmentImport($school_year, $student_data);
        }
    }

    private function hashStudentID($student_id, $salt){
        return hash('sha256', $student_id.$salt);
    }

    private function previewEnrollmentImport(string $school_year, array $DATA){
        global $debug, $logger;

        $this->StudentFactory->setSchoolYear($school_year);
        $previous_year = static::decrementSchoolYear($school_year);
        $Students = $this->getEnrolledStudents($previous_year);
        $StudentsByLastname = [];
        foreach($Students AS $Student){
            $lastname = $Student->getLastname();
            if(empty($StudentsByLastname[$lastname])){
                $StudentsByLastname[$lastname] = array();
            }
            $StudentsByLastname[$lastname][] = $Student;
        }
        $studentDataLookup = [];
        foreach($DATA AS $row){
            $NewStudent = $this->StudentFactory->getStudent(null, $row);
            $NewStudent->setSchoolID($this->school_id);
            $studentDataLookup[$row['lastname'].', '.$row['firstname']] = $NewStudent;
        }
        
        $enrollment = [];
        foreach($studentDataLookup AS $full_name=>$Student){
            $dob = $Student->getDateofBirth();
            if(empty($dob)){
                $student_row = $this->getStudentRow($Student);
                $logger->debug('BAD DOB',['student'=>$student_row]);
                $student_row['status'] = 'ERROR';
                $student_row['error'] = 'dob';
                unset($studentDataLookup[$full_name]);
            }
        }

        foreach($Students AS $Student){
            $student_data = $this->getStudentRow($Student);
            $full_name = $Student->getFullName();
            if(isset($studentDataLookup[$full_name])){
                $Conflict = $studentDataLookup[$full_name];
                $conflict_dob = $Conflict->getDateofBirth('Y-m-d');
                $conflict_entered_9 = $Conflict->getYearEntered9th();
                if($student_data['dob'] != $conflict_dob ||
                    $student_data['entered9th'] != $conflict_entered_9){
                        $conflict_student_data = $this->getStudentRow($Conflict);
                        $conflict_student_data['status'] = 'CONFLICT';
                        $conflict_student_data['id'] = $student_data['id'];
                        $enrollment[] = $conflict_student_data;
                        $student_data['status'] = 'CONFLICT';
                        $debug[] = 'DATA Conflict';
                }
                unset($studentDataLookup[$full_name]);
            }
            $enrollment[] = $student_data;
        }
        foreach($studentDataLookup AS $Student){
            
            $student_data = $this->getStudentRow($Student);
            $student_data['row_status'] = 'updated';
            $enrollment[] = $student_data;
        }
        return $this->getEnrollmentByGrade($enrollment);

    }

    private function getStudentRow(Student $Student):array{
        global $logger;
        $student_data = $Student->getDATA();
        $student_data['status'] = $Student->getStatus();
        $age = $Student->getCurrentAge();
        $student_data['age'] = (int) $age + 1;
        if($age > 21 || $age < 5){
            $student_data['status'] = 'ERROR';
            $logger->debug('BAD AGE',['student'=>$student_data]);
        }
        $grade = $Student->getGrade();
        if($grade == 'kindergarten'){
            $grade = 1;
        }elseif($grade == 'Graduate'){
            $grade = 13;
        }elseif(is_numeric($grade)){
            $grade++;
        }
        $student_data['grade'] = $grade;
        $student_data['dob'] = $Student->getDateofBirth('Y-m-d');
        if(empty($student_data['id'])){
            $student_data['id'] = 'NEW-'.rand();
        }
        return $student_data;
    }

    private function getEnrollmentByGrade(array $enrollment):array{
        $enrollment_by_grade = [
            1=>['grade'=>'grade-1','students'=>[]],
            2=>['grade'=>'grade-2','students'=>[]],
            3=>['grade'=>'grade-3','students'=>[]],
            4=>['grade'=>'grade-4','students'=>[]],
            5=>['grade'=>'grade-5','students'=>[]],
            6=>['grade'=>'grade-6','students'=>[]],
            7=>['grade'=>'grade-7','students'=>[]],
            8=>['grade'=>'grade-8','students'=>[]],
            9=>['grade'=>'grade-9','students'=>[]],
            10=>['grade'=>'grade-10','students'=>[]],
            11=>['grade'=>'grade-11','students'=>[]],
            12=>['grade'=>'grade-12','students'=>[]],
            13=>['grade'=>'grade-13','students'=>[]],
        ];

        usort($enrollment, function($a, $b) {
            // Compare by lastname first
            $lastnameComparison = strcmp($a['lastname'], $b['lastname']);
            if ($lastnameComparison === 0) {
                // If lastnames are the same, compare by firstname
                return strcmp($a['firstname'], $b['firstname']);
            }
            return $lastnameComparison;
        });

        foreach($enrollment AS $student_data){
            $grade = $student_data['grade'];
            $enrollment_by_grade[$grade]['students'][] = $student_data;
        }
        return $enrollment_by_grade;
    }


    public static function getDateTimeFromSchoolYear(string $school_year, string $date_cutoff = '09-01'):DateTimeImmutable{
        $year = substr($school_year,0,4);
        return new DateTimeImmutable($year.'-'.$date_cutoff);
    }

    public static function incrementSchoolYear(string $school_year):string{
        list($startYear, $endYear) = explode('-', $school_year);
        $newStartYear = (int)$startYear + 1;
        $newEndYear = (int)$endYear + 1;
        return $newStartYear . '-' . substr($newEndYear, -2);
    }

    public static function decrementSchoolYear(string $school_year):string{
        list($startYear, $endYear) = explode('-', $school_year);
        $newStartYear = (int)$startYear - 1;
        $newEndYear = (int)$endYear - 1;
        return $newStartYear . '-' . substr($newEndYear, -2);
    }

    public static function formatSchoolYear(null|string|DateTimeImmutable $date='now',?string $date_cutoff = '06-30'):string{
        if(is_string($date)){
            $Date = new DateTimeImmutable($date);
        }elseif(is_a($date,DateTimeImmutable::class)){
            $Date = $date;
        }else{
            $Date = new DateTimeImmutable('now');
        }
        
        $year = $Date->format('Y');
        $Cutoff = new DateTimeImmutable($year.'-'.$date_cutoff);
        if($Date >= $Cutoff){
            $Y = $Date->format('Y');
            $Next = $Date->modify('+1 Year');
            $y = $Next->format('y');
        }else{
            $Past = $Date->modify('-1 Year');
            $Y = $Past->format('Y');
            $y = $Date->format('y');
        }
        $school_year = $Y.'-'.$y;
        return $school_year;
    }

}